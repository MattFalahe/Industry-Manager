<?php

namespace IndustryManager\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * IndustryData — runtime guard + canonical table/constant registry for the
 * industry-recipe tables this plugin consumes.
 *
 * SDE IMPORT HAS BEEN REMOVED FOR NOW. Nothing currently populates these
 * tables; the consuming code (ProductionCalculator, PiSchematicService) reads
 * them when present. isInstalled()/isPiInstalled() therefore check for actual
 * ROWS, not just table existence — so whether the tables are absent OR present
 * but empty, every recipe-powered page degrades to a neutral "recipe data not
 * loaded" notice rather than 500 or a misleading "no recipe" everywhere.
 *
 * A direct importer into plugin-owned tables can be re-introduced later.
 *
 * No ESI. Reads SeAT's live synced tables + (when populated) these recipe tables.
 */
class IndustryData
{
    /**
     * Flat recipe table names (SDE-canonical CamelCase so joins read naturally).
     * Not currently populated — see class docblock.
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
     * Is the manufacturing recipe data present AND populated? Checks the
     * materials table exists and has at least one row, so an absent table or an
     * empty (created-but-not-imported) table both read as "not loaded".
     */
    public static function isInstalled(): bool
    {
        if (self::$installedMemo !== null) {
            return self::$installedMemo;
        }

        try {
            self::$installedMemo = Schema::hasTable(self::TABLE_MATERIALS)
                && Schema::hasTable(self::TABLE_PRODUCTS)
                && DB::table(self::TABLE_MATERIALS)->exists();
        } catch (\Throwable $e) {
            self::$installedMemo = false;
        }

        return self::$installedMemo;
    }

    /**
     * Is the Planetary Industry schematic data present AND populated? The live
     * colony data (character_planet_*) is synced by SeAT core and read
     * separately — this only gates the schematic recipe lookups (factory output
     * names, the schematic tree).
     */
    public static function isPiInstalled(): bool
    {
        if (self::$piInstalledMemo !== null) {
            return self::$piInstalledMemo;
        }

        try {
            self::$piInstalledMemo = Schema::hasTable(self::TABLE_PI_SCHEMATICS)
                && Schema::hasTable(self::TABLE_PI_TYPEMAP)
                && DB::table(self::TABLE_PI_TYPEMAP)->exists();
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
     * Cache-busting token for recipe caches. A future importer can stamp
     * `industry_manager_sde_version` to invalidate every cached recipe on
     * re-import; until then this falls back to the core SDE version, then a
     * constant. (Kept now so the cache layer needs no change when import
     * returns.)
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
