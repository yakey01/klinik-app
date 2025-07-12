<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemSettingsResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class SystemSettingsResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'System Settings';
    
    protected static ?string $modelLabel = 'Setting';
    
    protected static ?string $pluralModelLabel = 'System Settings';
    
    protected static ?string $navigationGroup = 'Admin Settings';
    
    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') || auth()->user()?->role?->name === 'admin';
    }

    // We'll use a fake model for this resource since it's more of a settings page
    protected static ?string $model = User::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('system_settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Application')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Application Settings')
                                    ->schema([
                                        Forms\Components\TextInput::make('app_name')
                                            ->label('Application Name')
                                            ->default(config('app.name'))
                                            ->required(),
                                        
                                        Forms\Components\TextInput::make('app_url')
                                            ->label('Application URL')
                                            ->url()
                                            ->default(config('app.url'))
                                            ->required(),
                                        
                                        Forms\Components\Select::make('app_env')
                                            ->label('Environment')
                                            ->options([
                                                'local' => 'Local',
                                                'staging' => 'Staging',
                                                'production' => 'Production',
                                            ])
                                            ->default(config('app.env'))
                                            ->required(),
                                        
                                        Forms\Components\Toggle::make('app_debug')
                                            ->label('Debug Mode')
                                            ->default(config('app.debug'))
                                            ->helperText('Should be disabled in production'),
                                        
                                        Forms\Components\Select::make('app_timezone')
                                            ->label('Timezone')
                                            ->options([
                                                'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
                                                'Asia/Makassar' => 'Asia/Makassar (WITA)',
                                                'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
                                                'UTC' => 'UTC',
                                            ])
                                            ->default(config('app.timezone'))
                                            ->required(),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Database')
                            ->icon('heroicon-o-circle-stack')
                            ->schema([
                                Forms\Components\Section::make('Database Configuration')
                                    ->schema([
                                        Forms\Components\Select::make('db_connection')
                                            ->label('Database Connection')
                                            ->options([
                                                'sqlite' => 'SQLite',
                                                'mysql' => 'MySQL',
                                                'pgsql' => 'PostgreSQL',
                                            ])
                                            ->default(config('database.default')),
                                        
                                        Forms\Components\TextInput::make('db_host')
                                            ->label('Database Host')
                                            ->default(config('database.connections.mysql.host'))
                                            ->visible(fn (callable $get) => $get('db_connection') !== 'sqlite'),
                                        
                                        Forms\Components\TextInput::make('db_port')
                                            ->label('Database Port')
                                            ->numeric()
                                            ->default(config('database.connections.mysql.port'))
                                            ->visible(fn (callable $get) => $get('db_connection') !== 'sqlite'),
                                        
                                        Forms\Components\TextInput::make('db_database')
                                            ->label('Database Name')
                                            ->default(config('database.connections.mysql.database'))
                                            ->visible(fn (callable $get) => $get('db_connection') !== 'sqlite'),
                                        
                                        Forms\Components\TextInput::make('db_username')
                                            ->label('Database Username')
                                            ->default(config('database.connections.mysql.username'))
                                            ->visible(fn (callable $get) => $get('db_connection') !== 'sqlite'),
                                        
                                        Forms\Components\TextInput::make('db_password')
                                            ->label('Database Password')
                                            ->password()
                                            ->visible(fn (callable $get) => $get('db_connection') !== 'sqlite'),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Mail')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\Section::make('Mail Configuration')
                                    ->schema([
                                        Forms\Components\Select::make('mail_mailer')
                                            ->label('Mail Driver')
                                            ->options([
                                                'smtp' => 'SMTP',
                                                'sendmail' => 'Sendmail',
                                                'mailgun' => 'Mailgun',
                                                'ses' => 'Amazon SES',
                                                'log' => 'Log (Development)',
                                            ])
                                            ->default(config('mail.default')),
                                        
                                        Forms\Components\TextInput::make('mail_host')
                                            ->label('SMTP Host')
                                            ->default(config('mail.mailers.smtp.host'))
                                            ->visible(fn (callable $get) => $get('mail_mailer') === 'smtp'),
                                        
                                        Forms\Components\TextInput::make('mail_port')
                                            ->label('SMTP Port')
                                            ->numeric()
                                            ->default(config('mail.mailers.smtp.port'))
                                            ->visible(fn (callable $get) => $get('mail_mailer') === 'smtp'),
                                        
                                        Forms\Components\TextInput::make('mail_username')
                                            ->label('SMTP Username')
                                            ->default(config('mail.mailers.smtp.username'))
                                            ->visible(fn (callable $get) => $get('mail_mailer') === 'smtp'),
                                        
                                        Forms\Components\TextInput::make('mail_password')
                                            ->label('SMTP Password')
                                            ->password()
                                            ->visible(fn (callable $get) => $get('mail_mailer') === 'smtp'),
                                        
                                        Forms\Components\Select::make('mail_encryption')
                                            ->label('Encryption')
                                            ->options([
                                                'tls' => 'TLS',
                                                'ssl' => 'SSL',
                                                '' => 'None',
                                            ])
                                            ->default(config('mail.mailers.smtp.encryption'))
                                            ->visible(fn (callable $get) => $get('mail_mailer') === 'smtp'),
                                        
                                        Forms\Components\TextInput::make('mail_from_address')
                                            ->label('From Address')
                                            ->email()
                                            ->default(config('mail.from.address'))
                                            ->required(),
                                        
                                        Forms\Components\TextInput::make('mail_from_name')
                                            ->label('From Name')
                                            ->default(config('mail.from.name'))
                                            ->required(),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Security')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Section::make('Security Settings')
                                    ->schema([
                                        Forms\Components\Toggle::make('force_https')
                                            ->label('Force HTTPS')
                                            ->helperText('Redirect all HTTP requests to HTTPS'),
                                        
                                        Forms\Components\TextInput::make('session_lifetime')
                                            ->label('Session Lifetime (minutes)')
                                            ->numeric()
                                            ->default(config('session.lifetime'))
                                            ->required(),
                                        
                                        Forms\Components\Toggle::make('password_reset_enabled')
                                            ->label('Enable Password Reset')
                                            ->default(true),
                                        
                                        Forms\Components\TextInput::make('max_login_attempts')
                                            ->label('Max Login Attempts')
                                            ->numeric()
                                            ->default(5)
                                            ->helperText('Number of failed attempts before lockout'),
                                        
                                        Forms\Components\TextInput::make('lockout_duration')
                                            ->label('Lockout Duration (minutes)')
                                            ->numeric()
                                            ->default(15),
                                        
                                        Forms\Components\Toggle::make('two_factor_enabled')
                                            ->label('Enable Two-Factor Authentication')
                                            ->helperText('Require 2FA for admin users'),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Cache')
                            ->icon('heroicon-o-bolt')
                            ->schema([
                                Forms\Components\Section::make('Cache Management')
                                    ->schema([
                                        Forms\Components\Select::make('cache_driver')
                                            ->label('Cache Driver')
                                            ->options([
                                                'file' => 'File',
                                                'database' => 'Database',
                                                'redis' => 'Redis',
                                                'memcached' => 'Memcached',
                                            ])
                                            ->default(config('cache.default')),
                                        
                                        Forms\Components\Select::make('queue_driver')
                                            ->label('Queue Driver')
                                            ->options([
                                                'sync' => 'Sync',
                                                'database' => 'Database',
                                                'redis' => 'Redis',
                                                'sqs' => 'Amazon SQS',
                                            ])
                                            ->default(config('queue.default')),
                                        
                                        Forms\Components\TextInput::make('cache_ttl')
                                            ->label('Default Cache TTL (seconds)')
                                            ->numeric()
                                            ->default(3600),
                                        
                                        Forms\Components\Toggle::make('enable_query_cache')
                                            ->label('Enable Query Caching')
                                            ->default(true),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Backup')
                            ->icon('heroicon-o-archive-box')
                            ->schema([
                                Forms\Components\Section::make('Backup Settings')
                                    ->schema([
                                        Forms\Components\Toggle::make('backup_enabled')
                                            ->label('Enable Automatic Backups')
                                            ->default(true),
                                        
                                        Forms\Components\Select::make('backup_frequency')
                                            ->label('Backup Frequency')
                                            ->options([
                                                'daily' => 'Daily',
                                                'weekly' => 'Weekly',
                                                'monthly' => 'Monthly',
                                            ])
                                            ->default('daily'),
                                        
                                        Forms\Components\TextInput::make('backup_retention_days')
                                            ->label('Retention Period (days)')
                                            ->numeric()
                                            ->default(30),
                                        
                                        Forms\Components\TextInput::make('backup_path')
                                            ->label('Backup Storage Path')
                                            ->default('storage/app/backups'),
                                        
                                        Forms\Components\Toggle::make('backup_compress')
                                            ->label('Compress Backups')
                                            ->default(true),
                                        
                                        Forms\Components\Toggle::make('backup_cloud_storage')
                                            ->label('Store in Cloud')
                                            ->helperText('Upload backups to cloud storage'),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // This won't be used since we're using a custom page
        return $table->query(User::query()->whereRaw('1 = 0')); // Empty query
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSystemSettings::route('/'),
        ];
    }
}