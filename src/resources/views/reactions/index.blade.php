@extends('web::layouts.grids.12')

@section('title', 'Reactions — Industry Manager')
@section('page_header', 'Reaction Blueprints')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=1">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-atom mr-2"></i> Reaction Blueprints</h3>
            </div>
            <div class="card-body">
                <p class="im-text-muted">{{ trans('industry-manager::common.coming_soon') }} (Sprint 3).</p>
                <p class="im-text-muted">Athanor &amp; Tatara reaction formulas: inputs, output, base + rig-adjusted time. Reaction rigs are a separate set from manufacturing rigs — same calc shape, different attribute IDs.</p>
            </div>
        </div>
    </div>
</div>
@stop
