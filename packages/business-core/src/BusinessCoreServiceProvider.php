<?php

namespace BusinessCore;

use BusinessCore\Contracts\AuditRecorder;
use BusinessCore\Contracts\StatementParser;
use BusinessCore\Services\CsvStatementParser;
use BusinessCore\Services\DatabaseAuditRecorder;
use BusinessCore\Support\CurrentBusiness;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BusinessCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/business-core.php', 'business-core');

        $this->app->scoped(CurrentBusiness::class);
        $this->app->bind(AuditRecorder::class, DatabaseAuditRecorder::class);
        $this->app->bind(StatementParser::class, CsvStatementParser::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'business-core');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // @bizcan('finance') ... @endbizcan
        Blade::if('bizcan', fn (string $ability) => app(CurrentBusiness::class)->can($ability));
        // @money($amount)
        Blade::directive('money', fn ($expression) => "<?php echo \\BusinessCore\\Support\\Format::money($expression); ?>");

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/business-core.php' => config_path('business-core.php')], 'business-core-config');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/business-core')], 'business-core-views');
        }
    }
}
