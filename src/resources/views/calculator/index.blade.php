@extends('web::layouts.grids.12')

@section('title', 'Calculator — Industry Manager')
@section('page_header', 'Production Calculator')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=1">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calculator mr-2"></i> Production Calculator</h3>
            </div>
            <div class="card-body">
                <p class="im-text-muted">{{ trans('industry-manager::common.coming_soon') }} (Sprint 1).</p>
                <p class="im-text-muted">Inputs: blueprint, ME, TE, quantity. Outputs: materials needed, raw + structure-adjusted time, and a best-structure picker ranked by ME% saved (rig-bonus aware). No ISK column in v1 — that lands in v1.1 when MC's PricingService is integrated.</p>
            </div>
        </div>
    </div>
</div>
@stop
