@extends('web::layouts.grids.12')

@section('title', 'Blueprints — Industry Manager')
@section('page_header', 'Blueprint Library')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=1">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-scroll mr-2"></i> Blueprint Library</h3>
            </div>
            <div class="card-body">
                <p class="im-text-muted">{{ trans('industry-manager::common.coming_soon') }} (Sprint 1).</p>
                <p class="im-text-muted">Will read from <code>corporation_blueprints</code> + <code>character_blueprints</code> (SeAT-synced) and join against the SDE for typeName / category / activity. ME/TE filter, search, and per-row drill-down to the detail page.</p>
            </div>
        </div>
    </div>
</div>
@stop
