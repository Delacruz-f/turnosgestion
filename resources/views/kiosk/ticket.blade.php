@extends('layouts.turnos')
@section('title', 'Turno ' . $ticket->code)

@section('content')
<header class="bar">
    <div class="bar__inner">
        <div class="brand">
            <span class="brand__mark" aria-hidden="true">T</span>
            <span class="brand__text">
                <span class="brand__name">{{ config('app.name') }}</span>
                <span class="brand__sub">Atención al ciudadano</span>
            </span>
        </div>
        <div class="bar__meta">
            <a class="bar__link" href="{{ route('kiosk') }}">Pedir otro turno</a>
        </div>
    </div>
</header>

<main class="wrap wrap--narrow" id="contenido">
    <div class="ticket-page">
        <article class="ticket {{ $ticket->status }}" id="ticket">
            <div class="ticket__head">
                <p class="ticket__org">{{ config('app.name') }}</p>
                <p class="ticket__service">{{ $ticket->service->name }}</p>
            </div>

            <div class="ticket__body">
                <h1 class="ticket__label">Tu turno <span class="sr-only">{{ $ticket->code }}</span></h1>
                <p class="code" aria-hidden="true">{{ $ticket->code }}</p>
                <div class="ticket__meta">
                    <span>Emitido a las <b>{{ $ticket->created_at->format('H:i') }}</b></span>
                    @if ($ticket->document)
                        <span>Documento <b>{{ $ticket->document }}</b></span>
                    @endif
                </div>
            </div>

            <ol class="steps" id="steps" aria-label="Estado del turno">
                <li data-step="waiting">Espera</li>
                <li data-step="calling">Llamado</li>
                <li data-step="serving">En atención</li>
                <li data-step="done">Finalizado</li>
            </ol>

            <div class="state" id="state" role="status" aria-live="polite"></div>
        </article>

        <div class="ticket-actions">
            <button type="button" class="btn btn--ghost" id="print">Imprimir comprobante</button>
            <a class="btn" href="{{ route('kiosk') }}">Pedir otro turno</a>
        </div>

        <p class="ticket-note">Guarda esta página: se actualiza sola cuando te llamen.</p>
    </div>
</main>
@endsection

@push('scripts')
<script>
const initial = @json(['status' => $ticket->status, 'position' => $position, 'counter' => $ticket->counter?->name]);
const statusUrl = @json(route('tickets.status', $ticket));
const box = document.getElementById('ticket'), state = document.getElementById('state');
const steps = Array.from(document.querySelectorAll('#steps li'));

const ORDER = ['waiting', 'calling', 'serving', 'done'];

function render(d) {
    box.className = 'ticket ' + d.status;
    const p = d.position;
    state.className = 'state' + (d.status === 'calling' ? ' pulse' : '');
    state.textContent = ({
        waiting: p === 0 ? 'Eres el siguiente' : `Hay ${p} ${p === 1 ? 'persona' : 'personas'} antes que tú`,
        calling: `Es tu turno: acércate a ${d.counter ?? 'la ventanilla'}`,
        serving: `Te atienden en ${d.counter ?? 'ventanilla'}`,
        done: 'Atención finalizada',
        skipped: 'Turno cerrado por ausencia. Solicita uno nuevo.',
    })[d.status];

    // Barra de 4 pasos: un turno ausente se marca como cerrado en el paso "Llamado".
    const reached = d.status === 'skipped' ? 1 : ORDER.indexOf(d.status);
    steps.forEach((li, i) => {
        li.classList.toggle('is-done', i < reached);
        li.classList.toggle('is-current', i === reached);
    });

    if (d.status === 'calling' && navigator.vibrate) navigator.vibrate([300, 150, 300]);
}
render(initial);

document.getElementById('print').addEventListener('click', () => window.print());

document.addEventListener('DOMContentLoaded', () => {
    const refresh = () => fetch(statusUrl).then(r => r.json()).then(render);
    window.onQueueUpdate(refresh);
    setInterval(refresh, 30000); // respaldo si se cae el WebSocket
});
</script>
@endpush
