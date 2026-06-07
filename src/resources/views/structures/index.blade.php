@extends('web::layouts.grids.12')

@section('title', 'Structures — Industry Manager')
@section('page_header', 'Industry Structures')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/industry-manager/css/industry-manager.css') }}?v=4">
@endpush

@section('full')
<div class="industry-manager-wrapper">
    <div class="industry-manager">

        @if(!$rigsCalibrated && auth()->user() && auth()->user()->can('industry-manager.admin'))
            <div class="im-sde-notice" style="border-left-color: var(--im-info); background-color: rgba(23,162,184,0.08); border-color: rgba(23,162,184,0.25);">
                <h4 style="color: var(--im-info);"><i class="fas fa-screwdriver-wrench mr-2"></i> Rig bonus magnitudes not calibrated yet</h4>
                <p>Structures and their fitted rigs are shown below, along with the security-class multiplier. The exact ME/TE/cost <strong>bonus percentages</strong> need the rig dogma attribute IDs, which differ per SDE version and aren't human-named in SeAT.</p>
                <p class="im-text-muted">Admin: open <a href="{{ route('industry-manager.diagnostic') }}">Diagnostic &rarr; Attribute Discovery</a>, identify the ME/TE/cost attributes (Step 5), and they get wired into <code>RigAttributes</code>. Until then this page shows everything that's knowable without them.</p>
            </div>
        @endif

        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-building mr-2"></i> Industry Structures</h3>
            </div>
            <div class="card-body">
                <p class="im-text-muted">Your corporation's Engineering Complexes and Refineries, with fitted rigs. The security multiplier (high-sec 1.0&times; / low-sec 1.9&times; / null&amp;WH 2.1&times;) scales every rig's bonus.</p>

                @if($structures->isEmpty())
                    <div class="im-empty-state">
                        <i class="fas fa-building"></i>
                        <p>No industry structures found for your corporations.</p>
                        <p class="im-text-muted">Only Engineering Complexes (Raitaru/Azbel/Sotiyo) and Refineries (Athanor/Tatara) are shown. SeAT must have structure + asset data synced for your corp.</p>
                    </div>
                @else
                    <div class="im-structure-grid">
                        @foreach($structures as $st)
                            <div class="im-structure-card">
                                <div class="im-structure-head">
                                    <div>
                                        <span class="im-structure-name">{{ $st['name'] }}</span>
                                        <div class="im-structure-sub">{{ $st['type_name'] }} · {{ $st['class'] }}</div>
                                    </div>
                                    <span class="im-badge im-badge-size">{{ $st['size'] }}</span>
                                </div>

                                <div class="im-structure-meta">
                                    <span><i class="fas fa-location-dot mr-1"></i>{{ $st['system_name'] }}</span>
                                    @if($st['security'] !== null)
                                        <span class="im-sec im-sec-{{ \Illuminate\Support\Str::slug($st['security_class']) }}">{{ number_format($st['security'], 1) }} {{ $st['security_class'] }}</span>
                                    @endif
                                    <span class="im-text-muted">rig &times;{{ $st['security_multiplier'] }}</span>
                                </div>

                                <div class="im-structure-rigs">
                                    <div class="im-result-label mb-1">Fitted rigs ({{ count($st['rigs']) }})</div>
                                    @if(empty($st['rigs']))
                                        <div class="im-text-muted">No rigs fitted.</div>
                                    @else
                                        @foreach($st['rigs'] as $rig)
                                            <div class="im-rig-row">
                                                <span class="im-rig-name">{{ $rig['name'] }}</span>
                                                @if(isset($rig['me_bonus']) && $rig['me_bonus'] !== null)
                                                    <span class="im-rig-bonus" title="Material Efficiency bonus (security-adjusted)">-{{ $rig['me_bonus'] }}% ME</span>
                                                @endif
                                                @if(isset($rig['te_bonus']) && $rig['te_bonus'] !== null)
                                                    <span class="im-rig-bonus im-rig-te" title="Time Efficiency bonus (security-adjusted)">-{{ $rig['te_bonus'] }}% TE</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
