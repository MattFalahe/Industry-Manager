<?php

namespace IndustryManager\Http\Controllers;

use Illuminate\Routing\Controller;
use IndustryManager\Helpers\AttributeDiscovery;

/**
 * Industry Manager — primary controller.
 *
 * Each method returns a placeholder view that the canonical design
 * system CSS picks up. Real logic lands sprint-by-sprint:
 *   Sprint 0  -> diagnostic() hosts the attribute-ID discovery tool   (SHIPPED)
 *   Sprint 1  -> blueprints(), blueprintDetail(), calculator(), structures()
 *   Sprint 2  -> jobs() (consumes corporation_industry_jobs)
 *   Sprint 3  -> invention(), reactions()
 */
class IndustryManagerController extends Controller
{
    public function index()
    {
        return view('industry-manager::index');
    }

    public function blueprints()
    {
        return view('industry-manager::blueprints.index');
    }

    public function blueprintDetail($type_id)
    {
        return view('industry-manager::blueprints.detail', [
            'type_id' => (int) $type_id,
        ]);
    }

    public function calculator()
    {
        return view('industry-manager::calculator.index');
    }

    public function structures()
    {
        return view('industry-manager::structures.index');
    }

    public function jobs()
    {
        return view('industry-manager::jobs.index');
    }

    public function invention()
    {
        return view('industry-manager::invention.index');
    }

    public function reactions()
    {
        return view('industry-manager::reactions.index');
    }

    public function settings()
    {
        return view('industry-manager::settings.index');
    }

    public function help()
    {
        return view('industry-manager::help.index');
    }

    /**
     * Diagnostic page. Sprint 0 ships the attribute-ID discovery tool
     * here — see Helpers\AttributeDiscovery for the rationale.
     *
     * Wrapped in try/catch because any of the SDE tables could be
     * missing on weird installs and we want the page to still load
     * (with a clear error) rather than 500.
     */
    public function diagnostic()
    {
        $error = null;
        $categories = [];
        $rigGroups = [];
        $rigTypes = [];
        $attrDump = [];
        $crossRef = [];

        try {
            $categories = AttributeDiscovery::findStructureCategories();
            $rigGroups = AttributeDiscovery::findRigGroups();
            $rigTypes = AttributeDiscovery::findIndustryRigTypes();

            $typeIDs = array_column($rigTypes, 'typeID');
            $attrDump = AttributeDiscovery::dumpAttributesForTypes($typeIDs);
            $crossRef = AttributeDiscovery::crossReferenceAttributes($rigTypes, $attrDump);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            \Illuminate\Support\Facades\Log::warning(
                '[Industry Manager] Diagnostic discovery query failed: ' . $e->getMessage()
            );
        }

        return view('industry-manager::diagnostic.index', [
            'error' => $error,
            'categories' => $categories,
            'rigGroups' => $rigGroups,
            'rigTypes' => $rigTypes,
            'attrDump' => $attrDump,
            'crossRef' => $crossRef,
        ]);
    }
}
