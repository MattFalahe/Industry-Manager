@extends('web::layouts.grids.12')

@section('title', 'Invention — Industry Manager')
@section('page_header', 'Invention Chains')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=1">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-flask mr-2"></i> Invention Chains</h3>
            </div>
            <div class="card-body">
                <p class="im-text-muted">{{ trans('industry-manager::common.coming_soon') }} (Sprint 3).</p>
                <p class="im-text-muted">T1 BPC + datacores + optional decryptor &rarr; T2 BPC with expected runs. Recursive across components (a T2 capital pulls T2 component BPCs which each have their own invention chain). No ISK math in v1.</p>
            </div>
        </div>
    </div>
</div>
@stop
