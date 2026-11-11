@extends('layouts.turnos')
@section('title', $counter->name)

@section('content')
<header class="bar">
    <div class="bar__inner agent-head">
        <div class="brand">
            <span class="brand__mark" aria-hidden="true">T</span>
            <div class="brand__text">
                <h1 class="brand__name">{{ $counter->name }}</h1>
                <span class="brand__sub">{{ $counter->service->name }}</span>
            </div>
        </div>
        <div class="bar__meta">
            <span class="agent-user"><a class="agent-user__link" href="{{ route('agent.reports') }}">Reportes</a> · Sesión de <strong>{{ auth()->user()->name }}</strong></span>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn btn--on-dark" type="submit">Salir</button>
            </form>
        </div>
    </div>
</header>

<main class="wrap" id="contenido">
    @if (session('error'))<div class="flash" role="alert">{{ session('error') }}</div>@endif
    @if (session('status'))<div class="flash flash--ok" role="status">{{ session('status') }}</div>@endif

    <div class="panel">
        <section class="box" aria-labelledby="current-title">
            <div class="box__head">
                <h2 id="current-title">Turno actual</h2>
                @if ($ticket)
                    <span class="tag {{ $ticket->status }}">{{ $ticket->status === 'calling' ? 'Llamando' : 'En atención' }}</span>
                @endif
            </div>

            @if ($ticket)
                <div class="current">
                    <p class="num">{{ $ticket->code }}</p>
                    <div class="current__meta">
                        @if ($ticket->document)
                            <span>Documento {{ $ticket->document }}</span>
                        @endif
                        <span class="current__timer">
                            <span class="timer__label">Tiempo de atención</span>
                            <span class="timer" id="timer" data-since="{{ ($ticket->started_at ?? $ticket->called_at)?->toIso8601String() }}">00:00</span>
                        </span>
                    </div>
                </div>

                <div class="actions">
                    @if ($ticket->status === 'calling')
                        <form method="POST" action="{{ route('agent.start') }}">@csrf
                            <button class="btn btn--civic btn--primary" type="submit">Iniciar atención</button>
                        </form>
                        <form method="POST" action="{{ route('agent.recall') }}">@csrf
                            <button class="btn btn--ghost" type="submit">Llamar de nuevo</button>
                        </form>
                        <form method="POST" action="{{ route('agent.skip') }}" data-confirm="¿Marcar este turno como no presentado? Se cerrará y no volverá a la cola.">@csrf
                            <button class="btn btn--danger" type="submit">No se presentó</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('agent.finish') }}">@csrf
                            <button class="btn btn--civic btn--primary" type="submit">Finalizar atención</button>
                        </form>
                    @endif
                </div>
            @else
                <div class="idle">
                    <p>No tienes un turno en curso. Cuando estés listo, llama al siguiente de la cola.</p>
                </div>
                <div class="actions actions--lead">
                    <form method="POST" action="{{ route('agent.next') }}">@csrf
                        <button class="btn btn--signal btn--primary" type="submit" @disabled($waiting === 0) id="next">Llamar siguiente</button>
                    </form>
                </div>
            @endif
        </section>

        <section class="box" aria-labelledby="summary-title">
            <div class="box__head">
                <h2 id="summary-title">Resumen del día</h2>
            </div>
            <div class="kpis">
                <div class="kpi">
                    <b id="waiting">{{ $waiting }}</b>
                    <span>en espera</span>
                </div>
                <div class="kpi">
                    <b>{{ $served }}</b>
                    <span>atendidos</span>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Confirmación para acciones destructivas
    document.querySelectorAll('form[data-confirm]').forEach(f => {
        f.addEventListener('submit', e => {
            if (!confirm(f.dataset.confirm)) e.preventDefault();
        });
    });

    // Cronómetro de atención en vivo
    const timer = document.getElementById('timer');
    if (timer && timer.dataset.since) {
        const since = new Date(timer.dataset.since).getTime();
        const pad = n => String(n).padStart(2, '0');
        const tick = () => {
            const s = Math.max(0, Math.floor((Date.now() - since) / 1000));
            timer.textContent = `${pad(Math.floor(s / 60))}:${pad(s % 60)}`;
        };
        tick();
        setInterval(tick, 1000);
    }

    // Cola en tiempo real
    window.onQueueUpdate(({ snapshot }) => {
        const w = snapshot.waiting.find(x => x.id === {{ $counter->service_id }});
        if (!w) return;
        document.getElementById('waiting').textContent = w.waiting;
        const next = document.getElementById('next');
        if (next) next.disabled = w.waiting === 0;
    });
});
</script>
@endpush
