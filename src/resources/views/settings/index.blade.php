@extends('web::layouts.grids.12')

@section('title', 'Settings — Industry Manager')
@section('page_header', 'Settings')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=1">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i> Settings</h3>
            </div>
            <div class="card-body">
                <p class="im-text-muted">{{ trans('industry-manager::common.coming_soon') }} (Sprint 2).</p>
                <p class="im-text-muted">No persisted settings yet. Future: default ME tax assumption per corp, default decryptor preference, future Notification Routing Map (v1.1 when MC EventBus pings are wired).</p>
            </div>
        </div>
    </div>
</div>
@stop
