<?php

namespace IndustryManager\Helpers;

use Illuminate\Support\Facades\Schema;

/**
 * IndustryData — runtime guard + canonical table/constant registry for the
 * industry-recipe SDE tables this plugin depends on.
 *
 * These tables are downloaded by our own importer command
 * (`php artisan industry-manager:import-sde`, see ImportSdeCommand), which
 * pulls only our tables from Fuzzwork's `latest/` dump — independent of SeAT's
 * core `eve:update:sde`. Until the operator runs the importer, the tables do
 * not exist — so every read path MUST gate on isInstalled()/isPiInstalled()
 * and the UI must show the "import recipe data" notice rather than 500.
 *
 * No ESI. Pure SDE + synced-table consumer.
 */
class IndustryData
{
    /**
     * SDE tables we ask SeAT to download. Names are Fuzzwork/CCP canonical
     * (CamelCase, matching the rest of the SDE: invTypes, dgmTypeAttributes…).
     */
    public const TABLE_ACTIVITY = 'industryActivity';
    public const TABLE_MATERIALS = 'industryActivityMaterials';
    public const TABLE_PRODUCTS = 'industryActivityProducts';
    public const TABLE_SKILLS = 'industryActivitySkills';
    public const TABLE_PROBABILITIES = 'industryActivityProbabilities';
    public const TABLE_BLUEPRINTS = 'industryBlueprints';

    /** Planetary Industry schematic SDE (factory recipes). */
    public const TABLE_PI_SCHEMATICS = 'planetSchematics';
    public const TABLE_PI_TYPEMAP = 'planetSchematicsTypeMap';

    /**
     * The manufacturing/invention/reaction set registered with
     * config('seat.sde.tables').
     */
    public const TABLES = [
        self::TABLE_ACTIVITY,
        self::TABLE_MATERIALS,
        self::TABLE_PRODUCTS,
        self::TABLE_SKILLS,
        self::TABLE_PROBABILITIES,
        self::TABLE_BLUEPRINTS,
    ];

    /**
     * The Planetary Industry set (registered separately so the PI page can be
     * absent without affecting the core industry pages, and vice versa).
     */
    public const PI_TABLES = [
        self::TABLE_PI_SCHEMATICS,
        self::TABLE_PI_TYPEMAP,
    ];

    /**
     * Per-request memos so repeated isInstalled() calls don't re-hit the
     * schema inspector (which queries information_schema on MySQL).
     */
    private static ?bool $installedMemo = null;

    private static ?bool $piInstalledMemo = null;

    /**
     * Is the recipe data present? We treat the two load-bearing tables
     * (materials + products) as the signal; the others are optional
     * enrichment that individual features guard for themselves.
     */
    public static function isInstalled(): bool
    {
        if (self::$installedMemo !== null) {
            return self::$installedMemo;
        }

        try {
            self::$installedMemo = Schema::hasTable(self::TABLE_MATERIALS)
                && Schema::hasTable(self::TABLE_PRODUCTS);
        } catch (\Throwable $e) {
            self::$installedMemo = false;
        }

        return self::$installedMemo;
    }

    /**
     * Is the Planetary Industry schematic data present? Both schematic tables
     * are needed to resolve factory recipes. The live colony data
     * (character_planet_*) is synced by SeAT core and checked separately.
     */
    public static function isPiInstalled(): bool
    {
        if (self::$piInstalledMemo !== null) {
            return self::$piInstalledMemo;
        }

        try {
            self::$piInstalledMemo = Schema::hasTable(self::TABLE_PI_SCHEMATICS)
                && Schema::hasTable(self::TABLE_PI_TYPEMAP);
        } catch (\Throwable $e) {
            self::$piInstalledMemo = false;
        }

        return self::$piInstalledMemo;
    }

    /**
     * Fine-grained presence check for an individual table, memo-free.
     * Used by features that need an optional table (e.g. probabilities for
     * invention) and want to degrade just that panel.
     */
    public static function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Reset the memo. Only needed in tests or right after a programmatic
     * SDE import within the same process.
     */
    public static function flush(): void
    {
        self::$installedMemo = null;
        self::$piInstalledMemo = null;
    }

    /**
     * Cache-busting token for recipe caches. Our importer
     * (industry-manager:import-sde) stamps `industry_manager_sde_version` on
     * each successful run, so re-importing after an EVE patch invalidates every
     * cached recipe. Falls back to the core SDE version, then a constant.
     */
    public static function recipeVersion(): string
    {
        try {
            $v = setting('industry_manager_sde_version', true);
            if ($v) {
                return (string) $v;
            }
            $core = setting('installed_sde', true);

            return $core ? (string) $core : 'none';
        } catch (\Throwable $e) {
            return 'none';
        }
    }
}
