<?php

namespace IndustryManager\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Attribute-ID Discovery (Sprint 0).
 *
 * The single highest-leverage tool in the whole Industry Manager plan.
 *
 * Problem it solves:
 *   SeAT v5 seeds dgmTypeAttributes (values) but NOT dgmAttributeTypes
 *   (the human-readable name lookup). That means we can't grep for
 *   "materialEfficiencyBonus" to find the attribute ID we need —
 *   we have to identify them by their values across rig variants.
 *   Matt hit this same wall with PosFuelCalculator and worked around
 *   it via a hardcoded type_id -> modifier map. This tool surfaces
 *   the same information for industry rigs in one shot, so the calc
 *   layer can import a confirmed `const` block instead of guessing.
 *
 * How to use it:
 *   Browse to /industry-manager/diagnostic (admin-only). The page
 *   walks the SDE step by step:
 *     1. Find Structure-related categories
 *     2. Find rig-bearing groups inside them
 *     3. List the actual Standup industry rig types
 *     4. Dump every dogma attribute on each rig
 *     5. Cross-reference: group attributes by ID and show the value
 *        distribution across all rigs, so the operator can spot
 *        patterns (e.g. an attribute valued -2.0 on T1 and -2.4 on T2
 *        of the same rig family is the ME bonus magnitude).
 *
 * Output gets pasted back into a confirmed-IDs constant block that
 * the Sprint 1 calc layer imports.
 *
 * Pure SDE reads — no ESI, no Eloquent, just DB facade for speed.
 */
class AttributeDiscovery
{
    /**
     * Step 1 — find all Structure-related categories. Useful when the
     * exact categoryID isn't known on this install (CCP sometimes
     * renumbers, and some Fuzzwork dumps drift).
     */
    public static function findStructureCategories(): array
    {
        return DB::table('invCategories')
            ->where('categoryName', 'LIKE', '%Structure%')
            ->orderBy('categoryName')
            ->get(['categoryID', 'categoryName'])
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Step 2 — find rig-bearing groups inside Structure categories.
     * The LIKE filter is intentionally broad; we want to surface every
     * candidate so the operator can spot anything that should also
     * be considered (e.g. drilling rigs, reprocessing rigs, etc).
     */
    public static function findRigGroups(): array
    {
        return DB::table('invGroups as g')
            ->join('invCategories as c', 'c.categoryID', '=', 'g.categoryID')
            ->leftJoin('invTypes as t', function ($join) {
                $join->on('t.groupID', '=', 'g.groupID')
                    ->where('t.published', '=', 1);
            })
            ->where('c.categoryName', 'LIKE', '%Structure%')
            ->where('g.groupName', 'LIKE', '%Rig%')
            ->groupBy('g.groupID', 'g.groupName', 'c.categoryName')
            ->orderBy('g.groupName')
            ->get([
                'g.groupID',
                'g.groupName',
                'c.categoryName',
                DB::raw('COUNT(t.typeID) AS member_count'),
            ])
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Step 3 — list every Standup industry rig type. Filter by
     * typeName prefix `Standup` (the canonical Upwell module
     * naming) AND a purpose suffix (Material/Time/Job Cost/
     * Reaction/Research/Invention/Reprocessing). published=1
     * to exclude beta/test types.
     */
    public static function findIndustryRigTypes(): array
    {
        $purposePatterns = [
            '%Material Efficiency%',
            '%Time Efficiency%',
            '%Job Cost%',
            '%Reaction%',
            '%Research%',
            '%Invention%',
            '%Reprocessing%',
        ];

        $query = DB::table('invTypes as t')
            ->join('invGroups as g', 'g.groupID', '=', 't.groupID')
            ->join('invCategories as c', 'c.categoryID', '=', 'g.categoryID')
            ->where('c.categoryName', 'LIKE', '%Structure%')
            ->where('t.typeName', 'LIKE', 'Standup %')
            ->where('t.published', '=', 1)
            ->where(function ($q) use ($purposePatterns) {
                foreach ($purposePatterns as $p) {
                    $q->orWhere('t.typeName', 'LIKE', $p);
                }
            })
            ->orderBy('g.groupName')
            ->orderBy('t.typeName');

        return $query->get([
            't.typeID',
            't.typeName',
            'g.groupID',
            'g.groupName',
        ])->map(fn ($r) => (array) $r)->all();
    }

    /**
     * Step 4 — for a given set of rig typeIDs, dump every dogma
     * attribute row. Returns a nested map keyed by typeID so the
     * view can render per-rig blocks easily.
     *
     *   [
     *     12345 => [
     *       ['attributeID' => 9, 'valueInt' => 100, 'valueFloat' => null],
     *       ...
     *     ],
     *     ...
     *   ]
     */
    public static function dumpAttributesForTypes(array $typeIDs): array
    {
        if (empty($typeIDs)) {
            return [];
        }

        $rows = DB::table('dgmTypeAttributes')
            ->whereIn('typeID', $typeIDs)
            ->orderBy('typeID')
            ->orderBy('attributeID')
            ->get(['typeID', 'attributeID', 'valueInt', 'valueFloat'])
            ->map(fn ($r) => (array) $r)
            ->all();

        $byType = [];
        foreach ($rows as $r) {
            $byType[$r['typeID']][] = $r;
        }

        return $byType;
    }

    /**
     * Step 5 — cross-reference. Pivots the attribute dump so each
     * attributeID maps to the distinct values it takes across all
     * rigs, plus which rigs hold each value. This is where patterns
     * jump out:
     *
     *   - attributeID with values clustering at {1.0, 1.9, 2.1}
     *     across many rig types -> almost certainly the security
     *     multiplier
     *   - attributeID with values {-2.0, -2.4} where the lower value
     *     correlates with rig names containing "II" -> the T1/T2
     *     bonus magnitude
     *   - attributeID that references typeIDs in 1000+ range (group
     *     IDs) and varies by rig family -> the category/group
     *     restriction
     *
     * Output:
     *   [
     *     attributeID => [
     *       'distinct_value_count' => N,
     *       'sample_values' => [v1, v2, ...],
     *       'value_to_types' => [
     *         '2.0'  => [['typeID'=>X,'typeName'=>'...'], ...],
     *         '2.4'  => [...],
     *       ],
     *       'appears_on_count' => N,
     *     ],
     *     ...
     *   ]
     */
    public static function crossReferenceAttributes(array $rigTypes, array $attrDumpByType): array
    {
        // typeID -> typeName lookup
        $nameByType = [];
        foreach ($rigTypes as $rig) {
            $nameByType[$rig['typeID']] = $rig['typeName'];
        }

        $byAttr = [];

        foreach ($attrDumpByType as $typeID => $rows) {
            foreach ($rows as $row) {
                $aid = $row['attributeID'];

                // Use valueFloat if present, otherwise valueInt. EVE
                // stores most bonus values as floats; the int column
                // is rarely populated for the attributes we care about.
                $value = $row['valueFloat'];
                if ($value === null) {
                    $value = $row['valueInt'];
                }

                if (! isset($byAttr[$aid])) {
                    $byAttr[$aid] = [
                        'value_to_types' => [],
                        'appears_on' => [],
                    ];
                }

                $key = (string) $value;
                $byAttr[$aid]['value_to_types'][$key][] = [
                    'typeID' => $typeID,
                    'typeName' => $nameByType[$typeID] ?? '(unknown)',
                ];
                $byAttr[$aid]['appears_on'][$typeID] = true;
            }
        }

        // Reshape + summarize.
        $result = [];
        foreach ($byAttr as $aid => $info) {
            $distinctValues = array_keys($info['value_to_types']);
            sort($distinctValues, SORT_NATURAL);

            $result[$aid] = [
                'attributeID' => $aid,
                'distinct_value_count' => count($distinctValues),
                'sample_values' => array_slice($distinctValues, 0, 8),
                'value_to_types' => $info['value_to_types'],
                'appears_on_count' => count($info['appears_on']),
                'hint' => self::hintForAttributePattern($distinctValues, $info['appears_on']),
            ];
        }

        // Sort by appears_on_count desc — the attributes used on the
        // most rigs are the most likely to be load-bearing bonuses.
        uasort($result, fn ($a, $b) => $b['appears_on_count'] <=> $a['appears_on_count']);

        return $result;
    }

    /**
     * Tiny pattern-matcher to label likely roles. Pure heuristics —
     * the operator confirms by checking against the in-game industry
     * window. False positives are FINE here; the operator filters.
     */
    private static function hintForAttributePattern(array $distinctValues, array $appearsOn): ?string
    {
        $n = count($distinctValues);
        $reach = count($appearsOn);

        // Security multiplier signature: tight cluster around 1.0,
        // 1.9, 2.1 (with possible variation around 1.0 for highsec
        // and 2.0-2.1 for null/WH).
        $numeric = array_map('floatval', $distinctValues);
        $hasSecValues = false;
        foreach ($numeric as $v) {
            if (abs($v - 1.0) < 0.01 || abs($v - 1.9) < 0.05 || abs($v - 2.1) < 0.05 || abs($v - 2.0) < 0.05) {
                $hasSecValues = true;
            }
        }
        if ($hasSecValues && $reach > 5 && $n <= 4) {
            return 'Possible security multiplier (values cluster at ~1.0 / ~1.9 / ~2.1)';
        }

        // T1/T2 bonus magnitude signature: exactly 2 distinct values
        // where the larger is ~1.15-1.25x the smaller (T2 is +20% over T1).
        if ($n === 2) {
            $a = $numeric[0];
            $b = $numeric[1];
            if ($a != 0 && $b != 0) {
                $ratio = max(abs($a), abs($b)) / min(abs($a), abs($b));
                if ($ratio >= 1.10 && $ratio <= 1.30) {
                    return 'Possible T1/T2 bonus magnitude (ratio ~' . round($ratio, 2) . 'x, matches the +20% T2 step)';
                }
            }
        }

        // Category/group restriction signature: values are large
        // integers that look like typeIDs (>1000) and many distinct.
        if ($n > 3) {
            $allLargeInts = true;
            foreach ($numeric as $v) {
                if ($v < 100 || floor($v) != $v) {
                    $allLargeInts = false;
                    break;
                }
            }
            if ($allLargeInts) {
                return 'Possible category/group restriction (values look like invCategories or invGroups IDs)';
            }
        }

        // Reach hint — useful even without a value-pattern match.
        if ($reach >= 20) {
            return 'Appears on many rigs (' . $reach . ') — likely structural attribute, worth investigating';
        }

        return null;
    }
}
