<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ValidateMigrationDependencies extends Command
{
    protected $signature = 'migrate:validate-dependencies {--fix : Attempt to fix issues}';
    protected $description = 'Validate migration dependencies and identify issues';

    private array $migrations = [];
    private array $tables = [];
    private array $foreignKeys = [];
    private array $issues = [];

    public function handle()
    {
        $this->info('Analyzing migration dependencies...');
        
        $this->loadMigrations();
        $this->analyzeDependencies();
        $this->detectIssues();
        
        $this->displayResults();
        
        if ($this->option('fix')) {
            $this->attemptFixes();
        }
        
        return $this->issues ? 1 : 0;
    }

    private function loadMigrations()
    {
        $migrationPath = database_path('migrations');
        $files = File::files($migrationPath);
        
        foreach ($files as $file) {
            $content = File::get($file->getPathname());
            $filename = $file->getFilename();
            
            $migration = [
                'file' => $filename,
                'timestamp' => substr($filename, 0, 17),
                'creates' => [],
                'modifies' => [],
                'foreign_keys' => [],
                'depends_on' => [],
            ];
            
            // Extract table creations
            preg_match_all('/Schema::create\([\'"](\w+)[\'"]/', $content, $creates);
            $migration['creates'] = $creates[1] ?? [];
            
            // Extract table modifications
            preg_match_all('/Schema::table\([\'"](\w+)[\'"]/', $content, $modifies);
            $migration['modifies'] = $modifies[1] ?? [];
            
            // Extract foreign keys
            $this->extractForeignKeys($content, $migration);
            
            $this->migrations[$filename] = $migration;
        }
        
        // Sort by timestamp
        ksort($this->migrations);
    }

    private function extractForeignKeys($content, &$migration)
    {
        // Pattern for foreignId()->constrained()
        preg_match_all('/\$table->foreignId\([\'"](\w+)[\'"]\)->constrained\([\'"]?(\w+)?[\'".]/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $column = $matches[1][$i];
            $table = $matches[2][$i] ?: $this->guessForeignTable($column);
            $migration['foreign_keys'][$column] = $table;
            $migration['depends_on'][] = $table;
        }
        
        // Pattern for foreign()->references()
        preg_match_all('/\$table->foreign\([\'"](\w+)[\'"]\)->references\([\'"](\w+)[\'"]\)->on\([\'"](\w+)[\'"]/', $content, $matches);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $column = $matches[1][$i];
            $table = $matches[3][$i];
            $migration['foreign_keys'][$column] = $table;
            $migration['depends_on'][] = $table;
        }
        
        $migration['depends_on'] = array_unique($migration['depends_on']);
    }

    private function guessForeignTable($column)
    {
        // Remove _id suffix and pluralize
        $table = Str::plural(str_replace('_id', '', $column));
        return $table;
    }

    private function analyzeDependencies()
    {
        $createdTables = [];
        
        foreach ($this->migrations as $filename => &$migration) {
            // Track created tables
            foreach ($migration['creates'] as $table) {
                $createdTables[$table] = $filename;
                $this->tables[$table] = [
                    'created_by' => $filename,
                    'modified_by' => [],
                    'foreign_keys_to' => [],
                    'foreign_keys_from' => [],
                ];
            }
        }
        
        // Second pass: check dependencies
        foreach ($this->migrations as $filename => &$migration) {
            $availableTables = [];
            
            // Find tables available at this point
            foreach ($this->migrations as $otherFile => $otherMigration) {
                if ($otherFile === $filename) break;
                $availableTables = array_merge($availableTables, $otherMigration['creates']);
            }
            
            // Check if dependencies are met
            foreach ($migration['depends_on'] as $dependency) {
                if (!in_array($dependency, $availableTables)) {
                    $migration['missing_dependencies'][] = $dependency;
                }
            }
            
            // Track modifications
            foreach ($migration['modifies'] as $table) {
                if (isset($this->tables[$table])) {
                    $this->tables[$table]['modified_by'][] = $filename;
                }
            }
            
            // Track foreign key relationships
            foreach ($migration['foreign_keys'] as $column => $targetTable) {
                $sourceTable = $migration['creates'][0] ?? $migration['modifies'][0] ?? null;
                if ($sourceTable && isset($this->tables[$sourceTable])) {
                    $this->tables[$sourceTable]['foreign_keys_to'][] = $targetTable;
                }
                if (isset($this->tables[$targetTable])) {
                    $this->tables[$targetTable]['foreign_keys_from'][] = $sourceTable;
                }
            }
        }
    }

    private function detectIssues()
    {
        // Check for circular dependencies
        foreach ($this->tables as $table => $info) {
            $this->detectCircularDependency($table, $table, [$table]);
        }
        
        // Check for timestamp conflicts
        $timestamps = [];
        foreach ($this->migrations as $filename => $migration) {
            $timestamp = $migration['timestamp'];
            if (!isset($timestamps[$timestamp])) {
                $timestamps[$timestamp] = [];
            }
            $timestamps[$timestamp][] = $filename;
        }
        
        foreach ($timestamps as $timestamp => $files) {
            if (count($files) > 1) {
                $this->issues[] = [
                    'type' => 'timestamp_conflict',
                    'severity' => 'high',
                    'timestamp' => $timestamp,
                    'files' => $files,
                ];
            }
        }
        
        // Check for missing dependencies
        foreach ($this->migrations as $filename => $migration) {
            if (!empty($migration['missing_dependencies'])) {
                $this->issues[] = [
                    'type' => 'missing_dependency',
                    'severity' => 'critical',
                    'file' => $filename,
                    'missing' => $migration['missing_dependencies'],
                ];
            }
        }
    }

    private function detectCircularDependency($startTable, $currentTable, $path)
    {
        if (!isset($this->tables[$currentTable])) return;
        
        foreach ($this->tables[$currentTable]['foreign_keys_to'] as $targetTable) {
            if ($targetTable === $startTable && count($path) > 1) {
                $this->issues[] = [
                    'type' => 'circular_dependency',
                    'severity' => 'critical',
                    'path' => array_merge($path, [$targetTable]),
                ];
                return;
            }
            
            if (!in_array($targetTable, $path)) {
                $this->detectCircularDependency($startTable, $targetTable, array_merge($path, [$targetTable]));
            }
        }
    }

    private function displayResults()
    {
        if (empty($this->issues)) {
            $this->info('✓ No dependency issues found!');
            return;
        }
        
        $this->error('Found ' . count($this->issues) . ' dependency issues:');
        $this->newLine();
        
        foreach ($this->issues as $issue) {
            switch ($issue['type']) {
                case 'circular_dependency':
                    $this->error('CIRCULAR DEPENDENCY (Critical):');
                    $this->line('  Path: ' . implode(' → ', $issue['path']));
                    break;
                    
                case 'timestamp_conflict':
                    $this->warn('TIMESTAMP CONFLICT (High):');
                    $this->line('  Timestamp: ' . $issue['timestamp']);
                    $this->line('  Files:');
                    foreach ($issue['files'] as $file) {
                        $this->line('    - ' . $file);
                    }
                    break;
                    
                case 'missing_dependency':
                    $this->error('MISSING DEPENDENCY (Critical):');
                    $this->line('  File: ' . $issue['file']);
                    $this->line('  Missing tables: ' . implode(', ', $issue['missing']));
                    break;
            }
            $this->newLine();
        }
        
        // Show suggested migration order
        $this->info('Suggested migration order:');
        $order = $this->calculateMigrationOrder();
        foreach ($order as $index => $filename) {
            $this->line(sprintf('%3d. %s', $index + 1, $filename));
        }
    }

    private function calculateMigrationOrder()
    {
        $order = [];
        $processed = [];
        $processing = [];
        
        foreach ($this->migrations as $filename => $migration) {
            $this->addToOrder($filename, $order, $processed, $processing);
        }
        
        return $order;
    }

    private function addToOrder($filename, &$order, &$processed, &$processing)
    {
        if (in_array($filename, $processed)) return;
        if (in_array($filename, $processing)) return; // Skip circular dependencies
        
        $processing[] = $filename;
        
        $migration = $this->migrations[$filename];
        
        // Process dependencies first
        foreach ($migration['depends_on'] as $dependency) {
            // Find migration that creates this table
            foreach ($this->migrations as $depFile => $depMigration) {
                if (in_array($dependency, $depMigration['creates'])) {
                    $this->addToOrder($depFile, $order, $processed, $processing);
                    break;
                }
            }
        }
        
        $order[] = $filename;
        $processed[] = $filename;
        array_pop($processing);
    }

    private function attemptFixes()
    {
        $this->info('Attempting to fix issues...');
        
        foreach ($this->issues as $issue) {
            if ($issue['type'] === 'timestamp_conflict') {
                $this->fixTimestampConflict($issue);
            }
        }
    }

    private function fixTimestampConflict($issue)
    {
        $this->warn('Fixing timestamp conflicts for: ' . $issue['timestamp']);
        
        $baseTimestamp = strtotime(str_replace('_', '-', substr($issue['timestamp'], 0, 10)));
        
        foreach ($issue['files'] as $index => $file) {
            if ($index === 0) continue; // Keep first file as is
            
            $newTimestamp = date('Y_m_d_His', $baseTimestamp + $index);
            $newFilename = $newTimestamp . substr($file, 17);
            
            $oldPath = database_path('migrations/' . $file);
            $newPath = database_path('migrations/' . $newFilename);
            
            if (File::exists($oldPath)) {
                File::move($oldPath, $newPath);
                $this->info("  Renamed: $file → $newFilename");
            }
        }
    }
}