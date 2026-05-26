@extends('web::layouts.grids.12')

@section('title', 'Diagnostic — Industry Manager')
@section('page_header', 'Industry Manager Diagnostic')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=1">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-stethoscope mr-2"></i> Diagnostic</h3>
            </div>
            <div class="card-body">
                <div class="diag-tab-intro">
                    <h4>What this page does</h4>
                    <p>Admin-only troubleshooting + plugin internals. Not in the sidebar by design (URL access only). Once the standard 6-tab Diagnostic layout is built (Health / Master Test / System Validation / Settings Health / Data Integrity / Industry Trace), this page will mirror Structure Manager's reference implementation.</p>
                    <h4>When to use</h4>
                    <p>If a calculator output disagrees with the in-game industry window, the Industry Trace tab will walk the calc step-by-step (BP base &rarr; ME applied &rarr; structure base bonus &rarr; rig bonus &rarr; security multiplier &rarr; final qty) so you can pinpoint which step diverged.</p>
                    <h4>Heads up</h4>
                    <p>The first feature here will be the <strong>Sprint 0 attribute-ID discovery tool</strong>: a one-shot scan of <code>dgmTypeAttributes</code> for industry rig types that surfaces the likely ME / TE / cost / security-multiplier attribute IDs for you to confirm against in-game numbers. That output drives a constants block the calc layer imports. This is the single highest-leverage step in the whole plan — see <code>project_industry_manager_plan.md</code> in memory.</p>
                </div>

                <hr>
                <p class="im-text-muted">{{ trans('industry-manager::common.coming_soon') }} (Sprint 0).</p>
            </div>
        </div>
    </div>
</div>
@stop
