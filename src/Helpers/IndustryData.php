<?php

namespace IndustryManager\Helpers;

use Illuminate\Support\Facades\Schema;

/**
 * IndustryData — runtime guard + canonical table/constant registry for the
 * industry-recipe SDE tables this plugin depends on.
 *
 * These tables are downloaded by SeAT's own `eve:update:sde` once the plugin
 * registers them (see IndustryManagerServiceProvider::register ->
 * registerSdeTables). Until the operator runs that update, the tables do not
 * exist — so every read path MUST gate on isInstalled() and the UI must show
 * the "run eve:update:sde" notice rather than 500.
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

    /**
     * The full set registered with config('seat.sde.tables').
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
     * Per-request memo so repeated isInstalled() calls don't re-hit the
     * schema inspector (which queries information_schema on MySQL).
     */
    private static ?bool $installedMemo = null;

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
    }
}
