@extends('web::layouts.grids.12')

@section('title', 'Structures — Industry Manager')
@section('page_header', 'Industry Structures')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=1">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building mr-2"></i> Industry Structures</h3>
            </div>
            <div class="card-body">
                <p class="im-text-muted">{{ trans('industry-manager::common.coming_soon') }} (Sprint 1).</p>
                <p class="im-text-muted">Lists corp-owned Upwell structures eligible for industry (Raitaru / Azbel / Sotiyo / Athanor / Tatara) with their fitted rigs and the resolved ME / TE / cost bonuses per category. Source: <code>CorporationStructure-&gt;rig_slots</code> + <code>dgmTypeAttributes</code>. Security class from <code>mapDenormalize.security</code> drives the 1.0 / 1.9 / 2.1 multiplier.</p>
            </div>
        </div>
    </div>
</div>
@stop
