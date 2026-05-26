<?php

namespace IndustryManager\Http\Controllers;

use Illuminate\Routing\Controller;

/**
 * Industry Manager — primary controller.
 *
 * Scaffold-only at the moment. Each method returns a placeholder
 * view that the canonical design system CSS picks up. Real logic
 * lands sprint-by-sprint:
 *   Sprint 0  -> diagnostic() hosts the attribute-ID discovery tool
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

    public function diagnostic()
    {
        return view('industry-manager::diagnostic.index');
    }
}
