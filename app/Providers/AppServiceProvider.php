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
