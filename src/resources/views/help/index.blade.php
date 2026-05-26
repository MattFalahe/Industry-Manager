@extends('web::layouts.grids.12')

@section('title', 'Help — Industry Manager')
@section('page_header', 'Help & Documentation')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=1">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i> Help &amp; Documentation</h3>
            </div>
            <div class="card-body">
                <h4>Getting Started</h4>
                <p class="im-text-muted">Industry Manager is a read-only plugin: it does not make any ESI calls of its own. All data comes from tables SeAT already syncs (industry jobs, blueprints, structures, assets) plus the SDE.</p>
                <p class="im-text-muted">If pages appear empty, SeAT may still be syncing your corp's industry data — check the SeAT Status &amp; Jobs page rather than this plugin. Pages will populate as SeAT's hourly industry job + blueprint pollers run.</p>

                <h4>What is shipped (current state)</h4>
                <ul class="im-text-muted">
                    <li>Scaffold only — sidebar registration, placeholder pages, CSS chrome.</li>
                    <li>Real features land sprint by sprint. See plugin status on the project's repo.</li>
                </ul>

                <h4>Architectural rules (for plugin admins)</h4>
                <ul class="im-text-muted">
                    <li><strong>No ESI calls</strong> from this plugin. SeAT does the syncing; we just read.</li>
                    <li><strong>Rig detection</strong> mirrors what SeAT's own corp structure detail view shows (via the <code>rig_slots</code> accessor on <code>CorporationStructure</code>).</li>
                    <li><strong>No ISK in v1.</strong> Material quantities and times are surfaced, but pricing waits for the v1.1 Manager Core integration.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@stop
