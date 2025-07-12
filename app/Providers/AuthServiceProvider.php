<?php

namespace App\Providers;

use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use App\Models\User;
use App\Policies\PasienPolicy;
use App\Policies\TindakanPolicy;
use App\Policies\PendapatanPolicy;
use App\Policies\JaspelPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Pasien::class => PasienPolicy::class,
        Tindakan::class => TindakanPolicy::class,
        Pendapatan::class => PendapatanPolicy::class,
        Jaspel::class => JaspelPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates if needed
        Gate::define('manage-system', function (User $user) {
            return $user->hasRole('admin');
        });

        Gate::define('approve-transactions', function (User $user) {
            return $user->hasRole(['admin', 'manajer']);
        });

        Gate::define('view-reports', function (User $user) {
            return $user->can('view-reports');
        });

        Gate::define('manage-users', function (User $user) {
            return $user->can('manage-roles');
        });
    }
}