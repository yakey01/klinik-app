<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExportUsersSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:export-seeder {--exclude-admin : Exclude admin users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export existing users to a seeder file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = \App\Models\User::with('roles')->get();
        
        if ($this->option('exclude-admin')) {
            $users = $users->filter(fn($user) => !$user->hasRole('admin'));
        }

        $seederContent = "<?php\n\nnamespace Database\\Seeders;\n\nuse Illuminate\\Database\\Seeder;\nuse App\\Models\\User;\n\nclass ExportedUsersSeeder extends Seeder\n{\n    public function run(): void\n    {\n        \$users = [\n";

        foreach ($users as $user) {
            $roles = $user->roles->pluck('name')->toArray();
            $seederContent .= "            [\n";
            $seederContent .= "                'name' => '{$user->name}',\n";
            $seederContent .= "                'email' => '{$user->email}',\n";
            $seederContent .= "                'nip' => '{$user->nip}',\n";
            $seederContent .= "                'no_telepon' => '{$user->no_telepon}',\n";
            $seederContent .= "                'password' => bcrypt('password123'), // Change this!\n";
            $seederContent .= "                'email_verified_at' => now(),\n";
            $seederContent .= "                'roles' => " . json_encode($roles) . ",\n";
            $seederContent .= "            ],\n";
        }

        $seederContent .= "        ];\n\n        foreach (\$users as \$userData) {\n            \$roles = \$userData['roles'];\n            unset(\$userData['roles']);\n            \$user = User::create(\$userData);\n            \$user->assignRole(\$roles);\n        }\n    }\n}";

        $filePath = database_path('seeders/ExportedUsersSeeder.php');
        file_put_contents($filePath, $seederContent);

        $this->info("Users exported to: {$filePath}");
        $this->info("Exported {$users->count()} users");
    }
}
