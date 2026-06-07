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
                <h4>One-time setup: load the industry recipes</h4>
                <p class="im-text-muted">Industry Manager reads EVE's industry recipes (what each blueprint consumes, produces, and how long it takes) from SeAT's Static Data Export. SeAT core does not ship those particular tables, so the plugin asks SeAT to download them. An administrator runs this once on the SeAT server:</p>
                <pre style="background-color: rgba(0,0,0,0.3); padding: 0.75rem; border-radius: 0.25rem; color: #8fe388;">php artisan eve:update:sde --force</pre>
                <p class="im-text-muted">This pulls the industry tables (<code>industryActivityMaterials</code>, <code>industryActivityProducts</code>, and friends) alongside SeAT's normal static data, in the format SeAT already uses. No ESI, API keys, or external accounts. Re-run it whenever you update the SDE for a new EVE patch; the recipe cache refreshes automatically.</p>

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
