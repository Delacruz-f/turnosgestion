@extends('layouts.turnos')
@section('title', 'Reportes')
@php
    $minutes = fn ($seconds) => $seconds === null ? '—' : ((int) round($seconds / 60)) . ' min';
    $maxHour = max(1, $hourly->max('total'));
@endphp

@section('content')
<header class="bar">
    <div class="bar__inner agent-head">
        <div class="brand">
            <span class="brand__mark" aria-hidden="true">T</span>
            <div class="brand__text">
                <h1 class="brand__name">Reportes</h1>
                <span class="brand__sub">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l j \d\e F \d\e Y') }}</span>
            </div>
        </div>
        <div class="bar__meta">
            <a class="bar__link" href="{{ route('agent.panel') }}">Volver a ventanilla</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn btn--on-dark" type="submit">Salir</button>
            </form>
        </div>
    </div>
</header>

<main class="wrap" id="contenido">
    <form method="GET" action="{{ route('agent.reports') }}" class="report-filter">
        <div class="report-filter__field">
            <label class="field__label" for="date">Fecha del reporte</label>
            <div class="report-filter__row">
                <input type="date" id="date" name="date" value="{{ $date }}" max="{{ \Illuminate\Support\Carbon::today()->toDateString() }}">
                <button class="btn" type="submit">Ver</button>
                <a class="report-filter__today" href="{{ route('agent.reports') }}">Hoy</a>
            </div>
        </div>
    </form>

    <section class="box" aria-labelledby="totals-title">
        <div class="box__head">
            <h2 id="totals-title">Resumen del día</h2>
        </div>
        <div class="kpis kpis--4">
            <div class="kpi">
                <b>{{ $totals->issued ?? 0 }}</b>
                <span>turnos emitidos</span>
            </div>
            <div class="kpi">
                <b>{{ $totals->served ?? 0 }}</b>
                <span>atendidos</span>
            </div>
            <div class="kpi">
                <b>{{ $totals->skipped ?? 0 }}</b>
                <span>ausentes</span>
            </div>
            <div class="kpi">
                <b>{{ $totals->priority_total ?? 0 }}</b>
                <span>preferenciales</span>
            </div>
        </div>

        <dl class="averages">
            <div>
                <dt>Espera promedio</dt>
                <dd>{{ $minutes($averages->wait_seconds ?? null) }}</dd>
            </div>
            <div>
                <dt>Atención promedio</dt>
                <dd>{{ $minutes($averages->serve_seconds ?? null) }}</dd>
            </div>
        </dl>
    </section>

    <section class="box" aria-labelledby="hourly-title">
        <div class="box__head">
            <h2 id="hourly-title">Turnos emitidos por hora</h2>
            <span class="box__note">Máximo {{ $maxHour }} en una hora</span>
        </div>

        <div class="chart" role="img" aria-label="Gráfico de barras de turnos emitidos por hora, de {{ $hourly->first()['hour'] }} a {{ $hourly->last()['hour'] }} horas">
            @foreach ($hourly as $h)
                @php $pct = round($h['total'] / $maxHour * 100); @endphp
                <div class="chart__col" title="{{ $h['hour'] }}:00 — {{ $h['total'] }} turnos">
                    <span class="chart__value">{{ $h['total'] ?: '' }}</span>
                    <span class="chart__bar" style="--h: {{ $h['total'] ? max($pct, 3) : 0 }}%"></span>
                    <span class="chart__label">{{ str_pad($h['hour'], 2, '0', STR_PAD_LEFT) }}</span>
                </div>
            @endforeach
        </div>

        <table class="sr-only">
            <caption>Turnos emitidos por hora</caption>
            <thead><tr><th scope="col">Hora</th><th scope="col">Turnos</th></tr></thead>
            <tbody>
                @foreach ($hourly as $h)
                    <tr><td>{{ $h['hour'] }}:00</td><td>{{ $h['total'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="box" aria-labelledby="service-title">
        <div class="box__head">
            <h2 id="service-title">Detalle por servicio</h2>
        </div>

        @if ($byService->isEmpty())
            <p class="idle">No se emitieron turnos en esta fecha.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <caption class="sr-only">Turnos y tiempos promedio por servicio</caption>
                    <thead>
                        <tr>
                            <th scope="col">Servicio</th>
                            <th scope="col">Emitidos</th>
                            <th scope="col">Atendidos</th>
                            <th scope="col">Ausentes</th>
                            <th scope="col">Espera media</th>
                            <th scope="col">Atención media</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byService as $row)
                            <tr>
                                <th scope="row">{{ $row->service }}</th>
                                <td>{{ $row->issued }}</td>
                                <td>{{ $row->served }}</td>
                                <td>{{ $row->skipped }}</td>
                                <td>{{ $minutes($row->wait_seconds) }}</td>
                                <td>{{ $minutes($row->serve_seconds) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</main>
@endsection
