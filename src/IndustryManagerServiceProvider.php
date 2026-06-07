<?php

namespace IndustryManager;

use Seat\Services\AbstractSeatPlugin;

/**
 * Industry Manager — service provider.
 *
 * Architectural rules locked at 2026-05-26:
 *   1. NO ESI calls from this plugin. Pure read-only consumer of tables
 *      SeAT already syncs (corporation_industry_jobs, corporation_blueprints,
 *      corporation_structures + corporation_assets for rigs, dgmTypeAttributes,
 *      SDE tables).
 *   2. Rig detection uses CorporationStructure->rig_slots (already implemented
 *      by SeAT core; same data the corp structure detail view renders).
 *   3. No pricing/ISK in v1. All price logic lands in v1.1 via MC PricingService.
 */
class IndustryManagerServiceProvider extends AbstractSeatPlugin
{
    public function boot()
    {
        if (! $this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }

        $this->loadTranslationsFrom(__DIR__ . '/resources/lang/', 'industry-manager');
        $this->loadViewsFrom(__DIR__ . '/resources/views/', 'industry-manager');

        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations/');

        // Command registration uses the dual-path pattern from SM
        // (`commands()` for CLI bootstrap, `app->resolving(Kernel)` as the
        // resolve-time fallback for web-invoked `Artisan::call(...)`). The
        // command list is empty at scaffold time; populate as features land.
        $imCommands = [
            // e.g. \IndustryManager\Console\Commands\SeedIndustryActivitiesCommand::class,
        ];

        if (! empty($imCommands)) {
            $this->commands($imCommands);

            $this->app->resolving(\Illuminate\Contracts\Console\Kernel::class, function ($kernel) use ($imCommands) {
                foreach ($imCommands as $cmd) {
                    try {
                        $kernel->registerCommand($this->app->make($cmd));
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning(
                            'IndustryManager: failed to register command ' . $cmd . ': ' . $e->getMessage()
                        );
                    }
                }
            });
        }

        $this->add_publications();
    }

    private function add_publications()
    {
        $this->publishes([
            __DIR__ . '/Config/industry-manager.config.php' => config_path('industry-manager.php'),
        ], ['config', 'seat']);

        $this->publishes([
            __DIR__ . '/resources/assets' => public_path('vendor/industry-manager'),
        ], ['public', 'seat']);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/Menu/package.sidebar.php', 'package.sidebar');

        $this->registerPermissions(__DIR__ . '/Config/Permissions/industry-manager.permissions.php', 'industry-manager');

        $this->mergeConfigFrom(__DIR__ . '/Config/industry-manager.config.php', 'industry-manager');

        // Register the industry-recipe SDE tables with SeAT's own SDE updater.
        // These tables (industryActivity*) are NOT seeded by SeAT core, but
        // Fuzzwork publishes them in the exact same dump format SeAT already
        // consumes. registerSdeTables() appends them to config('seat.sde.tables'),
        // so the next `php artisan eve:update:sde --force` downloads + imports
        // them automatically alongside the core SDE. This keeps the recipe data
        // patch-current with zero bundled payload and zero ESI calls.
        //
        // See \IndustryManager\Helpers\IndustryData::TABLES for the canonical list
        // and IndustryData::isInstalled() for the runtime guard the UI uses to
        // degrade gracefully when the operator hasn't run the SDE update yet.
        $this->registerSdeTables(\IndustryManager\Helpers\IndustryData::TABLES);
    }

    public function getName(): string
    {
        return 'Industry Manager';
    }

    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/MattFalahe/Industry-Manager';
    }

    public function getPackagistPackageName(): string
    {
        return 'industry-manager';
    }

    public function getPackagistVendorName(): string
    {
        return 'mattfalahe';
    }
}
