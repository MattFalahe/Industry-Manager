@extends('web::layouts.grids.12')

@section('title', 'Help — Industry Manager')
@section('page_header', 'Help & Documentation')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=3">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i> Help &amp; Documentation</h3>
            </div>
            <div class="card-body">
                <h4>One-time setup: import the recipes</h4>
                <p class="im-text-muted">Industry Manager needs EVE's industry &amp; planetary recipes (what each blueprint or schematic consumes, produces, and how long it takes). These aren't part of SeAT's core Static Data Export, so the plugin imports them itself. An administrator runs this once on the SeAT server:</p>
                <pre style="background-color: rgba(0,0,0,0.3); padding: 0.75rem; border-radius: 0.25rem; color: #8fe388;">php artisan industry-manager:import-sde</pre>
                <p class="im-text-muted">This reads EVE's official CCP Static Data Export (<code>blueprints.jsonl</code> + <code>planetSchematics.jsonl</code>) and flattens just the industry &amp; planetary recipes into Industry Manager's own tables. It re-uses the SDE files SeAT already extracted when present (zero download), otherwise fetches CCP's latest SDE. It does <strong>not</strong> modify SeAT's core SDE, and uses no ESI, API keys, or external accounts. Re-run it after an EVE patch to refresh; the recipe cache invalidates automatically on each import.</p>

                <h4>What's in v1.0.0</h4>
                <ul class="im-text-muted">
                    <li><strong>Blueprint Library</strong> — your characters' and corporations' blueprints, grouped by type, with originals/copies and best ME/TE.</li>
                    <li><strong>Production Calculator</strong> — pick a blueprint, set ME and runs, and get the exact material list plus a full recursive build tree down to base materials. The "Base Materials" panel is your shopping list.</li>
                    <li><strong>Production Tree</strong> — expand/collapse each buildable sub-component to see the whole manufacturing chain.</li>
                </ul>

                <h4>How material quantities are calculated</h4>
                <p class="im-text-muted">Per material, Industry Manager uses EVE's formula: <code>required = max(runs, ceil(round(baseQuantity &times; runs &times; (1 - ME/100), 2)))</code>. Deeper components in the tree use the "Sub-build ME" you choose (default 0). Structure and rig bonuses are not yet applied in v1.0.0 (see below).</p>

                <h4>Coming next</h4>
                <ul class="im-text-muted">
                    <li><strong>Structures &amp; rig bonuses</strong> — your corporation's engineering complexes, their fitted rigs, and the ME/TE/cost bonuses they grant (with the security-class multiplier).</li>
                    <li><strong>Invention &amp; Reactions</strong> — T2 invention chains and reaction formulas.</li>
                    <li><strong>ISK valuation</strong> — material cost, product value, and build-vs-buy, via Manager Core pricing (v1.1).</li>
                </ul>

                <h4>For plugin administrators</h4>
                <ul class="im-text-muted">
                    <li><strong>No ESI calls</strong> from this plugin. SeAT does the syncing; Industry Manager only reads.</li>
                    <li><strong>Blueprints</strong> come from SeAT's <code>character_blueprints</code> / <code>corporation_blueprints</code>. If a page is empty, SeAT may still be syncing — check SeAT's status page.</li>
                    <li><strong>Diagnostic</strong> lives at <code>/industry-manager/diagnostic</code> (admin-only, not in the sidebar). It hosts the rig attribute-ID discovery tool used to validate structure-bonus math.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@stop
