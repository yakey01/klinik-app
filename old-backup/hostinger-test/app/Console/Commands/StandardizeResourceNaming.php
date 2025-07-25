<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\NamingStandardsService;

class StandardizeResourceNaming extends Command
{
    protected $signature = 'dokterku:standardize-naming {--dry-run : Show what would be changed without making changes}';
    protected $description = 'Standardize resource naming conventions across the application';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $changes = [];

        $this->info('🔍 Scanning for naming inconsistencies...');
        $this->newLine();

        // Find all resource files
        $resourceFiles = $this->findResourceFiles();
        
        foreach ($resourceFiles as $file) {
            $analysis = $this->analyzeResourceFile($file);
            if (!empty($analysis['issues'])) {
                $changes[] = $analysis;
            }
        }

        if (empty($changes)) {
            $this->info('✅ No naming inconsistencies found!');
            return;
        }

        $this->displayChanges($changes);

        if (!$isDryRun) {
            if ($this->confirm('Apply these standardizations?')) {
                $this->applyChanges($changes);
                $this->info('✅ Naming standardizations applied successfully!');
            } else {
                $this->info('❌ Standardization cancelled.');
            }
        }
    }

    protected function findResourceFiles(): array
    {
        $files = [];
        $directories = [
            app_path('Filament'),
        ];

        foreach ($directories as $directory) {
            if (File::exists($directory)) {
                $files = array_merge($files, File::allFiles($directory));
            }
        }

        return collect($files)
            ->filter(fn($file) => str_ends_with($file->getFilename(), 'Resource.php'))
            ->toArray();
    }

    protected function analyzeResourceFile($file): array
    {
        $content = File::get($file->getPathname());
        $issues = [];
        $suggestions = [];

        // Extract current values
        $navigationGroup = $this->extractValue($content, 'navigationGroup');
        $navigationLabel = $this->extractValue($content, 'navigationLabel');
        $modelLabel = $this->extractValue($content, 'modelLabel');
        $pluralModelLabel = $this->extractValue($content, 'pluralModelLabel');
        $navigationIcon = $this->extractValue($content, 'navigationIcon');

        // Extract model name
        $modelName = $this->extractModelName($content);
        if (!$modelName) {
            return ['file' => $file->getPathname(), 'issues' => []];
        }

        // Get standard configuration
        $standard = NamingStandardsService::generateResourceConfig($modelName);

        // Check for inconsistencies
        if ($navigationGroup && $navigationGroup !== $standard['navigationGroup']) {
            $issues[] = "Navigation group: '$navigationGroup' should be '{$standard['navigationGroup']}'";
            $suggestions['navigationGroup'] = $standard['navigationGroup'];
        }

        if ($navigationLabel && $navigationLabel !== $standard['navigationLabel']) {
            $issues[] = "Navigation label: '$navigationLabel' should be '{$standard['navigationLabel']}'";
            $suggestions['navigationLabel'] = $standard['navigationLabel'];
        }

        if ($modelLabel && $modelLabel !== $standard['modelLabel']) {
            $issues[] = "Model label: '$modelLabel' should be '{$standard['modelLabel']}'";
            $suggestions['modelLabel'] = $standard['modelLabel'];
        }

        if ($pluralModelLabel && $pluralModelLabel !== $standard['pluralModelLabel']) {
            $issues[] = "Plural model label: '$pluralModelLabel' should be '{$standard['pluralModelLabel']}'";
            $suggestions['pluralModelLabel'] = $standard['pluralModelLabel'];
        }

        if ($navigationIcon && $navigationIcon !== $standard['navigationIcon']) {
            $issues[] = "Navigation icon: '$navigationIcon' should be '{$standard['navigationIcon']}'";
            $suggestions['navigationIcon'] = $standard['navigationIcon'];
        }

        return [
            'file' => $file->getPathname(),
            'model' => $modelName,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'content' => $content,
        ];
    }

    protected function extractValue(string $content, string $property): ?string
    {
        if (preg_match("/protected static \?\string \\\${$property} = '([^']+)';/", $content, $matches)) {
            return $matches[1];
        }
        if (preg_match("/protected static \?\string \\\${$property} = \"([^\"]+)\";/", $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function extractModelName(string $content): ?string
    {
        if (preg_match('/protected static \?\string \$model = ([^:]+)::class;/', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    protected function displayChanges(array $changes): void
    {
        $this->info("📋 Found " . count($changes) . " files with naming inconsistencies:");
        $this->newLine();

        foreach ($changes as $change) {
            $this->line("📄 <fg=yellow>" . str_replace(base_path(), '', $change['file']) . "</fg=yellow>");
            $this->line("   Model: <fg=cyan>{$change['model']}</fg=cyan>");
            
            foreach ($change['issues'] as $issue) {
                $this->line("   ⚠️  $issue");
            }
            $this->newLine();
        }
    }

    protected function applyChanges(array $changes): void
    {
        $this->info('🔧 Applying changes...');
        $this->newLine();

        foreach ($changes as $change) {
            $content = $change['content'];
            $updated = false;

            foreach ($change['suggestions'] as $property => $newValue) {
                $pattern = "/protected static \?\string \\\${$property} = ['\"][^'\"]*['\"]/";
                $replacement = "protected static ?string \${$property} = '$newValue'";
                
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $replacement, $content);
                    $updated = true;
                }
            }

            if ($updated) {
                File::put($change['file'], $content);
                $this->line("✅ Updated: " . str_replace(base_path(), '', $change['file']));
            }
        }
    }
}