@extends('web::layouts.grids.12')

@section('title', trans('industry-manager::common.industry_manager'))
@section('page_header', trans('industry-manager::common.industry_manager'))

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=1">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-industry mr-2"></i>
                    Industry Manager — Dashboard
                </h3>
            </div>
            <div class="card-body">
                <div class="im-scaffold-notice">
                    <p class="im-text-muted">
                        <strong>{{ trans('industry-manager::common.scaffold_notice_title') }}.</strong>
                        {{ trans('industry-manager::common.scaffold_notice_body') }}
                    </p>
                    <p class="im-text-muted">
                        This dashboard will surface the active-jobs board, structure status, and at-a-glance counters once Sprint 2 lands. For now use the sidebar to confirm each placeholder route loads.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
