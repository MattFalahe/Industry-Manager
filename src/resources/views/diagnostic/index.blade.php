@extends('web::layouts.grids.12')

@section('title', 'Diagnostic — Industry Manager')
@section('page_header', 'Industry Manager Diagnostic')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=2">
<style>
    /* Discovery-page specific chrome — kept here so the canonical
       CSS file stays clean. Promote to the .css file once the page
       structure firms up. */
    .im-discovery .step-section {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background-color: rgba(0, 0, 0, 0.15);
        border: 1px solid var(--im-border);
        border-radius: var(--im-radius-md);
    }
    .im-discovery .step-section h4 {
        color: var(--im-text-white);
        margin-bottom: 0.5rem;
    }
    .im-discovery table {
        width: 100%;
        margin-bottom: 0;
        color: var(--im-text-light);
    }
    .im-discovery table th {
        background-color: var(--im-dark-card);
        color: var(--im-text-white);
        padding: 0.5rem;
        border-bottom: 1px solid var(--im-border);
        text-align: left;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .im-discovery table td {
        padding: 0.4rem 0.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        font-size: 0.85rem;
    }
    .im-discovery table tr:hover td {
        background-color: rgba(102, 126, 234, 0.05);
    }
    .im-discovery .attr-hint {
        color: var(--im-warning);
        font-style: italic;
        font-size: 0.85rem;
    }
    .im-discovery .attr-id-pill {
        display: inline-block;
        background: linear-gradient(135deg, var(--im-primary-start) 0%, var(--im-primary-end) 100%);
        color: var(--im-text-white);
        padding: 0.15rem 0.5rem;
        border-radius: var(--im-radius-md);
        font-weight: 600;
        font-family: monospace;
    }
    .im-discovery .value-cluster {
        font-family: monospace;
        background-color: rgba(255,255,255,0.05);
        padding: 0.1rem 0.4rem;
        border-radius: var(--im-radius-sm);
        margin-right: 0.25rem;
        display: inline-block;
        margin-bottom: 0.2rem;
    }
    .im-discovery details summary {
        cursor: pointer;
        color: var(--im-text-light);
        padding: 0.25rem 0;
    }
    .im-discovery details summary:hover {
        color: var(--im-primary-start);
    }
    .im-discovery .empty-state {
        color: var(--im-text-muted);
        font-style: italic;
        padding: 0.5rem 0;
    }
</style>
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager im-discovery">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-stethoscope mr-2"></i> Industry Manager — Diagnostic</h3>
            </div>
            <div class="card-body">

                <div class="diag-tab-intro">
                    <h4>What this page does</h4>
                    <p><strong>Sprint 0 attribute-ID discovery tool.</strong> Walks your SeAT SDE step by step to surface the dogma attribute IDs used by industry rigs — the ones that encode ME / TE / cost bonuses, the security-class multiplier (1.0 / 1.9 / 2.1), and category restrictions. These IDs differ between SDE versions and are NOT human-readable (SeAT v5 doesn't seed <code>dgmAttributeTypes</code>), so this is the only reliable way to confirm them on your install.</p>

                    <h4>When to use</h4>
                    <p>Once, before the Sprint 1 calc layer is built. Identify the attribute IDs below, paste them back in chat (e.g. "ME=2593, TE=2594, COST=2595, SECURITY=2596"), and I'll bake them into a confirmed <code>const</code> block the calculator imports.</p>

                    <h4>What to look for</h4>
                    <p>Scroll to <strong>Step 5</strong> (cross-reference). Attributes are sorted by how many rigs they appear on — the load-bearing bonuses sit at the top. The <em>hint</em> column auto-flags likely matches (security multiplier, T1/T2 magnitude, category restriction) by their value patterns. Confirm by checking the rigs they apply to.</p>
                </div>

                @if($error)
                    <div class="alert alert-danger" role="alert">
                        <strong>Discovery failed:</strong> {{ $error }}
                        <br><small>Most common cause: SDE tables not yet seeded in this install. Run <code>php artisan eveapi:update:sde</code> inside the SeAT container and reload.</small>
                    </div>
                @else

                <!-- ============ Step 1: Categories ============ -->
                <div class="step-section">
                    <h4>Step 1 &mdash; Structure-related categories ({{ count($categories) }})</h4>
                    <p class="im-text-muted">Sanity check: the SDE should have at least one category whose name contains "Structure" (typically <code>categoryID=66</code>, "Structure Module"). If this is empty your SDE is not seeded.</p>
                    @if(empty($categories))
                        <div class="empty-state">No Structure-related categories found.</div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th>categoryID</th>
                                    <th>categoryName</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $c)
                                    <tr>
                                        <td><code>{{ $c['categoryID'] }}</code></td>
                                        <td>{{ $c['categoryName'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <!-- ============ Step 2: Rig Groups ============ -->
                <div class="step-section">
                    <h4>Step 2 &mdash; Rig-bearing groups inside Structure categories ({{ count($rigGroups) }})</h4>
                    <p class="im-text-muted">Groups under Structure categories whose name contains "Rig". <code>member_count</code> = how many published rig types belong to that group.</p>
                    @if(empty($rigGroups))
                        <div class="empty-state">No rig groups found.</div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th>groupID</th>
                                    <th>groupName</th>
                                    <th>categoryName</th>
                                    <th>member_count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rigGroups as $g)
                                    <tr>
                                        <td><code>{{ $g['groupID'] }}</code></td>
                                        <td>{{ $g['groupName'] }}</td>
                                        <td>{{ $g['categoryName'] }}</td>
                                        <td>{{ $g['member_count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <!-- ============ Step 3: Rig Types ============ -->
                <div class="step-section">
                    <h4>Step 3 &mdash; Industry rig types ({{ count($rigTypes) }})</h4>
                    <p class="im-text-muted">Standup-prefixed published rig types whose name suggests an industry purpose (Material/Time Efficiency, Job Cost, Reaction, Research, Invention, Reprocessing).</p>
                    @if(empty($rigTypes))
                        <div class="empty-state">No industry rig types found.</div>
                    @else
                        <details>
                            <summary>Show all {{ count($rigTypes) }} rig types</summary>
                            <table>
                                <thead>
                                    <tr>
                                        <th>typeID</th>
                                        <th>typeName</th>
                                        <th>groupName</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rigTypes as $t)
                                        <tr>
                                            <td><code>{{ $t['typeID'] }}</code></td>
                                            <td>{{ $t['typeName'] }}</td>
                                            <td>{{ $t['groupName'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </details>
                    @endif
                </div>

                <!-- ============ Step 4: Per-Type Attribute Dump (collapsed) ============ -->
                <div class="step-section">
                    <h4>Step 4 &mdash; Per-rig attribute dumps</h4>
                    <p class="im-text-muted">All <code>dgmTypeAttributes</code> rows for each industry rig. Collapsed by default; click to expand. Most useful as a reference once you've identified candidates in Step 5.</p>
                    @if(empty($attrDump))
                        <div class="empty-state">No attribute rows for the rig types listed above.</div>
                    @else
                        @foreach($rigTypes as $t)
                            @php $rows = $attrDump[$t['typeID']] ?? []; @endphp
                            <details>
                                <summary>{{ $t['typeName'] }} (typeID {{ $t['typeID'] }}) &mdash; {{ count($rows) }} attributes</summary>
                                @if(empty($rows))
                                    <div class="empty-state">No attribute rows.</div>
                                @else
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>attributeID</th>
                                                <th>valueInt</th>
                                                <th>valueFloat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rows as $r)
                                                <tr>
                                                    <td><code>{{ $r['attributeID'] }}</code></td>
                                                    <td><code>{{ $r['valueInt'] ?? '-' }}</code></td>
                                                    <td><code>{{ $r['valueFloat'] ?? '-' }}</code></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </details>
                        @endforeach
                    @endif
                </div>

                <!-- ============ Step 5: Cross-Reference (the payoff) ============ -->
                <div class="step-section">
                    <h4>Step 5 &mdash; Cross-reference (the payoff)</h4>
                    <p class="im-text-muted">Every attribute ID that appears on any industry rig, sorted by how widely it's used. Distinct value count and sample values let you spot patterns at a glance. <em>Hint</em> column flags likely roles based on value clustering.</p>
                    @if(empty($crossRef))
                        <div class="empty-state">No attributes to cross-reference.</div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th>attributeID</th>
                                    <th>appears_on</th>
                                    <th>distinct values</th>
                                    <th>sample values</th>
                                    <th>hint</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($crossRef as $aid => $info)
                                    <tr>
                                        <td><span class="attr-id-pill">{{ $info['attributeID'] }}</span></td>
                                        <td>{{ $info['appears_on_count'] }}</td>
                                        <td>{{ $info['distinct_value_count'] }}</td>
                                        <td>
                                            @foreach($info['sample_values'] as $v)
                                                <span class="value-cluster">{{ $v }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if($info['hint'])
                                                <span class="attr-hint"><i class="fas fa-lightbulb mr-1"></i>{{ $info['hint'] }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <!-- ============ How to report findings ============ -->
                <div class="step-section">
                    <h4>How to report your findings</h4>
                    <p class="im-text-muted">Once you've identified the load-bearing attributes from Step 5 (confirmed against the in-game industry window if possible), paste them back like this:</p>
                    <pre style="background-color: rgba(0,0,0,0.3); padding: 0.75rem; border-radius: 0.25rem; color: var(--im-text-light);">
ME_BONUS_ATTR_ID        = ???
TE_BONUS_ATTR_ID        = ???
COST_BONUS_ATTR_ID      = ???
SECURITY_MULTIPLIER_ATTR_ID = ???
CATEGORY_RESTRICTION_ATTR_ID = ???
(optional, if found:)
RESEARCH_ME_ATTR_ID     = ???
RESEARCH_TE_ATTR_ID     = ???
INVENTION_TIME_ATTR_ID  = ???
INVENTION_CHANCE_ATTR_ID = ???
REACTION_ME_ATTR_ID     = ???
REACTION_TE_ATTR_ID     = ???
REACTION_COST_ATTR_ID   = ???
                    </pre>
                    <p class="im-text-muted">Confirmed IDs go into <code>src/Helpers/RigAttributes.php</code> as a <code>const</code> block — that's the only piece blocking the Sprint 1 calc layer.</p>
                </div>

                @endif

            </div>
        </div>
    </div>
</div>
@stop
