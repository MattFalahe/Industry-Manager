<?php

namespace IndustryManager\Helpers;

/**
 * RigAttributes — the dogma attribute IDs that hold an industry rig's ME / TE /
 * cost bonus magnitudes, plus the security-class multiplier that scales them.
 *
 * THE ATTRIBUTE IDS ARE NOT YET CONFIRMED ON THIS INSTALL.
 * SeAT v5 does not seed dgmAttributeTypes (the human-readable name lookup), so
 * the correct attribute IDs must be discovered against the live SDE. Run:
 *     Industry Manager > Diagnostic > Attribute Discovery (Step 5)
 * identify the ME / TE / cost / category-restriction attributes, then fill the
 * constants below. Until ME_BONUS_ATTRIBUTE is non-null, isConfigured() returns
 * false and the Structures page shows fitted rigs WITHOUT computed bonus
 * magnitudes (it still shows the structure, its rigs, and the security
 * multiplier, all of which are known without the attribute IDs).
 *
 * This gate is deliberate: shipping a guessed attribute ID would silently
 * produce wrong "best structure" numbers, which is the exact trust-eroding
 * failure mode the discovery tool exists to prevent.
 */
class RigAttributes
{
    /** Fill these from the discovery tool. null = not yet calibrated. */
    public const ME_BONUS_ATTRIBUTE = null;
    public const TE_BONUS_ATTRIBUTE = null;
    public const COST_BONUS_ATTRIBUTE = null;

    /**
     * Optional: the attribute holding the rig's category/group restriction.
     * When known, lets us check a rig only applies to a product's category.
     */
    public const CATEGORY_RESTRICTION_ATTRIBUTE = null;

    public static function isConfigured(): bool
    {
        return self::ME_BONUS_ATTRIBUTE !== null;
    }

    /**
     * Security-class multiplier on rig bonuses:
     *   high-sec 1.0x, low-sec 1.9x, null/WH 2.1x.
     * Uses SeAT's raw truesec (mapDenormalize.security): >= 0.45 is high-sec
     * (CCP rounds 0.45+ up to a displayed 0.5).
     */
    public static function securityMultiplier(?float $security): float
    {
        if ($security === null) {
            return 1.0;
        }
        if ($security >= 0.45) {
            return 1.0;
        }
        if ($security > 0.0) {
            return 1.9;
        }

        return 2.1;
    }

    public static function securityClass(?float $security): string
    {
        if ($security === null) {
            return 'Unknown';
        }
        if ($security >= 0.45) {
            return 'High-sec';
        }
        if ($security > 0.0) {
            return 'Low-sec';
        }

        return 'Null / WH';
    }
}
