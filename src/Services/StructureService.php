<?php

namespace IndustryManager\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use IndustryManager\Helpers\RigAttributes;
use IndustryManager\Helpers\StructureTypes;

/**
 * StructureService — the user's corporation industry structures (Engineering
 * Complexes + Refineries) with their fitted rigs and (once RigAttributes is
 * calibrated) the rig bonus magnitudes.
 *
 * Rigs are read with ONE targeted query against corporation_assets filtered to
 * RigSlot location_flags — NOT by eager-loading every asset at each structure
 * (which would pull fuel, ammo, etc.). This mirrors what SeAT's own structure
 * detail view surfaces via CorporationStructure->rig_slots, but stays cheap.
 */
class StructureService
{
    private CharacterResolver $resolver;

    public function __construct(?CharacterResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new CharacterResolver();
    }

    /**
     * @return Collection<int,array>
     */
    public function forUser(): Collection
    {
        $corpIds = $this->resolver->ownCorporationIds();
        if (empty($corpIds)) {
            return collect();
        }

        $structures = DB::table('corporation_structures as s')
            ->leftJoin('invTypes as t', 't.typeID', '=', 's.type_id')
            ->leftJoin('universe_structures as u', 'u.structure_id', '=', 's.structure_id')
            ->leftJoin('mapDenormalize as m', 'm.itemID', '=', 's.system_id')
            ->whereIn('s.corporation_id', $corpIds)
            ->whereIn('s.type_id', StructureTypes::INDUSTRY)
            ->get([
                's.structure_id',
                's.type_id',
                't.typeName as type_name',
                'u.name as struct_name',
                'm.itemName as system_name',
                'm.security',
            ]);

        if ($structures->isEmpty()) {
            return collect();
        }

        $structureIds = $structures->pluck('structure_id')->all();

        $rigRows = DB::table('corporation_assets as a')
            ->leftJoin('invTypes as t', 't.typeID', '=', 'a.type_id')
            ->whereIn('a.location_id', $structureIds)
            ->where('a.location_flag', 'like', 'RigSlot%')
            ->get(['a.location_id', 'a.type_id', 't.typeName']);

        $rigsByStructure = $rigRows->groupBy('location_id');
        $configured = RigAttributes::isConfigured();

        // When calibrated, preload bonus attributes for all fitted rig types in
        // one query rather than per-rig.
        $bonusByRigType = [];
        if ($configured) {
            $rigTypeIds = $rigRows->pluck('type_id')->unique()->filter()->all();
            $bonusByRigType = $this->loadRigBonuses($rigTypeIds);
        }

        return $structures->map(function ($s) use ($rigsByStructure, $configured, $bonusByRigType) {
            $security = $s->security !== null ? (float) $s->security : null;

            $rigs = ($rigsByStructure[$s->structure_id] ?? collect())->map(function ($r) use ($configured, $bonusByRigType, $security) {
                $rig = [
                    'type_id' => (int) $r->type_id,
                    'name' => $r->typeName ?? ('Rig #' . $r->type_id),
                ];

                if ($configured && isset($bonusByRigType[(int) $r->type_id])) {
                    $raw = $bonusByRigType[(int) $r->type_id];
                    $mult = RigAttributes::securityMultiplier($security);
                    $rig['me_bonus'] = $raw['me'] !== null ? round(abs($raw['me']) * $mult, 2) : null;
                    $rig['te_bonus'] = $raw['te'] !== null ? round(abs($raw['te']) * $mult, 2) : null;
                    $rig['cost_bonus'] = $raw['cost'] !== null ? round(abs($raw['cost']) * $mult, 2) : null;
                }

                return $rig;
            })->values()->all();

            return [
                'structure_id' => (int) $s->structure_id,
                'name' => $s->struct_name ?: ('Structure ' . $s->structure_id),
                'type_id' => (int) $s->type_id,
                'type_name' => $s->type_name ?: StructureTypes::name((int) $s->type_id),
                'class' => StructureTypes::className((int) $s->type_id),
                'size' => StructureTypes::sizeClass((int) $s->type_id),
                'system_name' => $s->system_name ?: 'Unknown',
                'security' => $security !== null ? round($security, 1) : null,
                'security_class' => RigAttributes::securityClass($security),
                'security_multiplier' => RigAttributes::securityMultiplier($security),
                'rigs' => $rigs,
            ];
        })->sortBy('name')->values();
    }

    /**
     * Load ME/TE/cost dogma attribute values for a set of rig type IDs in one
     * query. Only called when RigAttributes is calibrated.
     *
     * @param  int[]  $rigTypeIds
     * @return array<int,array{me:?float,te:?float,cost:?float}>
     */
    private function loadRigBonuses(array $rigTypeIds): array
    {
        $attrIds = array_values(array_filter([
            RigAttributes::ME_BONUS_ATTRIBUTE,
            RigAttributes::TE_BONUS_ATTRIBUTE,
            RigAttributes::COST_BONUS_ATTRIBUTE,
        ], fn ($v) => $v !== null));

        if (empty($rigTypeIds) || empty($attrIds)) {
            return [];
        }

        $rows = DB::table('dgmTypeAttributes')
            ->whereIn('typeID', $rigTypeIds)
            ->whereIn('attributeID', $attrIds)
            ->get(['typeID', 'attributeID', 'valueFloat', 'valueInt']);

        $out = [];
        foreach ($rows as $row) {
            $tid = (int) $row->typeID;
            $val = $row->valueFloat;
            if ($val === null) {
                $val = $row->valueInt;
            }

            if (! isset($out[$tid])) {
                $out[$tid] = ['me' => null, 'te' => null, 'cost' => null];
            }

            if ((int) $row->attributeID === RigAttributes::ME_BONUS_ATTRIBUTE) {
                $out[$tid]['me'] = $val;
            } elseif ((int) $row->attributeID === RigAttributes::TE_BONUS_ATTRIBUTE) {
                $out[$tid]['te'] = $val;
            } elseif ((int) $row->attributeID === RigAttributes::COST_BONUS_ATTRIBUTE) {
                $out[$tid]['cost'] = $val;
            }
        }

        return $out;
    }
}
