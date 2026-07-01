<?php

namespace App\Providers;

use App\Models\CompanyPolicy;
use App\Models\TriageQuestion;
use App\Policies\CompanyPolicyPolicy;
use App\Policies\TriageQuestionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(CompanyPolicy::class, CompanyPolicyPolicy::class);
        Gate::policy(TriageQuestion::class, TriageQuestionPolicy::class);

        /*
         * Rate Limiter "widget": defensa multicapa para el Trust Widget.
         *
         * Aplica tres límites simultáneos por cada petición al widget:
         * - Por Visitante: 10 req/min (clave: visitor:{visitor_uuid})
         * - Por Empresa:   60 req/min (clave: company:{company_public_uuid})
         * - Por IP:         5 req/min (clave: ip:{ip})
         *
         * Si cualquiera de los tres límites se excede, Laravel retorna HTTP 429.
         * Los valores son configurables via .env (RATE_LIMIT_WIDGET_*).
         */
        RateLimiter::for('widget', function (Request $request) {
            return [
                Limit::perMinute((int) config('rate_limits.widget.visitor'))
                    ->by('visitor:'.$request->input('visitor_uuid')),
                Limit::perMinute((int) config('rate_limits.widget.company'))
                    ->by('company:'.$request->input('company_public_uuid')),
                Limit::perMinute((int) config('rate_limits.widget.ip'))
                    ->by('ip:'.$request->ip()),
            ];
        });
    }
}
