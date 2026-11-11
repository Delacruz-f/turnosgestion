@extends('layouts.turnos')
@section('title', 'Pantalla de turnos')
@section('body', 'tv')

@section('content')
<div class="tv-shell">
    <header class="tv-head">
        <div class="brand">
            <span class="brand__mark" aria-hidden="true">T</span>
            <span class="brand__text">
                <span class="brand__name">{{ config('app.name') }}</span>
                <span class="brand__sub">Sala de espera</span>
            </span>
        </div>
        <div class="clock">
            <span class="clock__time" id="clock-time">--:--</span>
            <span class="clock__date" id="clock-date"></span>
        </div>
    </header>

    <main class="tv-grid" id="contenido">
        <section class="now" id="now" aria-live="assertive" aria-atomic="true">
            <div class="now__label">Llamando</div>
            <p class="num" id="now-code">—</p>
            <div class="now__where" id="now-where"></div>
        </section>

        <aside class="recent" aria-label="Últimos llamados">
            <h2>Últimos llamados</h2>
            <div class="recent__list" id="recent"></div>
        </aside>
    </main>

    <footer class="foot" id="foot"></footer>
</div>

<button class="btn btn--on-dark voice" id="voice" type="button">Activar voz</button>
@endsection

@push('scripts')
<script>
let voiceOn = false, lastKey = null;
const $ = id => document.getElementById(id);

// Reloj de cabecera
const fmtTime = new Intl.DateTimeFormat('es-PE', { hour: '2-digit', minute: '2-digit' });
const fmtDate = new Intl.DateTimeFormat('es-PE', { weekday: 'long', day: 'numeric', month: 'long' });
const tick = () => {
    const now = new Date();
    $('clock-time').textContent = fmtTime.format(now);
    $('clock-date').textContent = fmtDate.format(now);
};
tick();
setInterval(tick, 15000);

function render(s) {
    const [cur, ...rest] = s.calls;
    $('now').dataset.idle = cur ? 'false' : 'true';
    $('now-code').textContent = cur ? cur.code : '—';
    $('now-where').textContent = cur ? (cur.counter ?? '') : 'Sin turnos en atención';
    $('recent').innerHTML = rest.length
        ? rest.map(c =>
            `<div class="row row--${c.status}"><span class="num">${c.code}</span><span class="row__where">${c.counter ?? ''}</span></div>`).join('')
        : '<p class="recent__empty">Aún no hay más llamados hoy.</p>';
    $('foot').innerHTML = s.waiting.map(w =>
        `<span class="foot__item">${w.name}: <b>${w.waiting}</b> en espera</span>`).join('');
}

function announce(code, counter) {
    const box = $('now'); box.classList.remove('flash-call'); void box.offsetWidth; box.classList.add('flash-call');
    if (!voiceOn || !('speechSynthesis' in window)) return;
    const u = new SpeechSynthesisUtterance(`Turno ${code.split('').join(' ')}, ${counter}`);
    u.lang = 'es-PE'; u.rate = .9;
    speechSynthesis.cancel(); speechSynthesis.speak(u);
}

render(@json($snapshot));

$('voice').addEventListener('click', e => { voiceOn = true; e.target.remove(); });

document.addEventListener('DOMContentLoaded', () => {
    window.onQueueUpdate(({ snapshot, announce: code, counter }) => {
        render(snapshot);
        if (code) announce(code, counter);
    });
    setInterval(() => fetch(@json(route('display.data'))).then(r => r.json()).then(render), 30000);
});
</script>
@endpush
