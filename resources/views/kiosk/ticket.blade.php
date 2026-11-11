@extends('layouts.turnos')
@section('title', 'Turno ' . $ticket->code)
@php
    use App\Support\QrCode;
    $trackUrl = route('tickets.show', $ticket);
@endphp

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
                    @if ($ticket->priority)
                        <span>Turno <b>preferencial</b></span>
                    @endif
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

            <div class="ticket__qr">
                <div class="qr" role="img" aria-label="Código QR con el enlace para seguir este turno en el celular">
                    {!! QrCode::svg($trackUrl, 160, 1) !!}
                </div>
                <p class="qr__hint">Siga su turno en el celular</p>
            </div>
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
@php
    $initial = [
        'status' => $ticket->status,
        'position' => $position,
        'counter' => $ticket->counter?->name,
        'code' => $ticket->code,
    ];
@endphp
const initial = @json($initial);
const statusUrl = @json(route('tickets.status', $ticket));
const code = @json($ticket->code);
const baseTitle = @json('Turno ' . $ticket->code);
const box = document.getElementById('ticket'), state = document.getElementById('state');
const steps = Array.from(document.querySelectorAll('#steps li'));

const ORDER = ['waiting', 'calling', 'serving', 'done'];
let lastStatus = initial.status;
let interacted = false;   // el navegador solo permite audio tras un gesto del usuario
let audioCtx = null;

// ---------------------------------------------------------------- Audio
function getCtx() {
    if (!audioCtx) {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return null;
        audioCtx = new Ctx();
    }
    if (audioCtx.state === 'suspended') audioCtx.resume().catch(() => {});
    return audioCtx;
}

/** Timbre corto y ascendente al estilo del mostrador (más breve que el de la TV). */
function chime() {
    const ctx = getCtx();
    if (!ctx) return;
    const now = ctx.currentTime;
    [[988, 0], [1319, 0.16]].forEach(([freq, offset]) => {
        const start = now + offset;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, start);
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(0.22, start + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.28);
        osc.connect(gain).connect(ctx.destination);
        osc.start(start);
        osc.stop(start + 0.3);
    });
}

// ---------------------------------------------------------------- Render
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

    // Solo al TRANSICIÓN a "calling" (no en cada refresco).
    if (d.status === 'calling' && lastStatus !== 'calling') {
        document.title = `¡Es tu turno! ${d.code ?? code}`;
        if (navigator.vibrate) navigator.vibrate([300, 150, 300]);
        if (interacted) chime();
    } else if (d.status !== 'calling') {
        document.title = baseTitle;
    }

    lastStatus = d.status;
}

render(initial);

// ---------------------------------------------------------------- Interacción
document.getElementById('print').addEventListener('click', () => window.print());

// Cualquier toque del usuario habilita el audio (política de autoplay).
['pointerdown', 'keydown', 'touchstart'].forEach(evt =>
    document.addEventListener(evt, () => { interacted = true; getCtx(); }, { once: true, passive: true }));

document.addEventListener('DOMContentLoaded', () => {
    const refresh = () => fetch(statusUrl).then(r => r.json()).then(render);
    window.onQueueUpdate(refresh);
    setInterval(refresh, 30000); // respaldo si se cae el WebSocket
});
</script>
@endpush
