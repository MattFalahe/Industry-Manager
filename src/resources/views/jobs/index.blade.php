@extends('web::layouts.grids.12')

@section('title', 'Jobs — Industry Manager')
@section('page_header', 'Active Industry Jobs')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=1">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs mr-2"></i> Active &amp; Recent Jobs</h3>
            </div>
            <div class="card-body">
                <p class="im-text-muted">{{ trans('industry-manager::common.coming_soon') }} (Sprint 2).</p>
                <p class="im-text-muted">Reads from SeAT's <code>corporation_industry_jobs</code> table (already polled hourly by core). Columns: blueprint, product, installer, runs, status, ETA. ETA countdowns will use EVE-to-local auto-conversion per the standard.</p>
            </div>
        </div>
    </div>
</div>
@stop
