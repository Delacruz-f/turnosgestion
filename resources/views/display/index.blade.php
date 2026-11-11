@extends('layouts.turnos')
@section('title', 'Pantalla de turnos')
@section('body', 'tv')

@section('content')
<div class="tv-shell">
    <header class="tv-head">
        <div class="brand">
            <span class="brand__mark" aria-hidden="true">T</span>
            <div class="brand__text">
                <h1 class="brand__name">{{ config('app.name') }}</h1>
                <span class="brand__sub">Sala de espera</span>
            </div>
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
const $ = id => document.getElementById(id);
const params = new URLSearchParams(location.search);
const kioskMode = params.get('kiosk') === '1'; // Chrome --kiosk-printing / autoplay ya habilitado

let voiceOn = false;
let audioCtx = null;
let spanishVoice = null;

/* ---------------------------------------------------------------- Audio */

// Un único AudioContext reutilizado (crear uno por sonido agota el límite del navegador).
function getCtx() {
    if (!audioCtx) {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return null;
        audioCtx = new Ctx();
    }
    if (audioCtx.state === 'suspended') audioCtx.resume().catch(() => {});
    return audioCtx;
}

/**
 * Campana de dos tonos descendentes (880 Hz → 660 Hz) sintetizada con Web Audio.
 * Envolvente de ganancia suave para evitar el "clic" al cortar.
 */
function chime() {
    const ctx = getCtx();
    if (!ctx) return;
    const now = ctx.currentTime;
    const duration = 0.35;
    const gap = 0.04;

    [[880, 0], [660, duration + gap]].forEach(([freq, offset]) => {
        const start = now + offset;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, start);

        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(0.28, start + 0.02);        // ataque rápido
        gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);  // caída suave

        osc.connect(gain).connect(ctx.destination);
        osc.start(start);
        osc.stop(start + duration + 0.02);
    });
}

/* ---------------------------------------------------------------- Voz */

// Primero es-MX, luego es-ES, luego cualquier variante de español.
function pickSpanishVoice() {
    if (!('speechSynthesis' in window)) return null;
    const voices = speechSynthesis.getVoices() || [];
    if (!voices.length) return null;

    return voices.find(v => /^es[-_]MX/i.test(v.lang))
        || voices.find(v => /^es[-_]ES/i.test(v.lang))
        || voices.find(v => /^es/i.test(v.lang))
        || null;
}

function loadVoice() {
    spanishVoice = pickSpanishVoice();
    if (spanishVoice) return;
    // Chrome carga las voces de forma asíncrona: se espera el evento una sola vez.
    if ('speechSynthesis' in window) {
        speechSynthesis.addEventListener('voiceschanged', () => { spanishVoice = pickSpanishVoice(); }, { once: true });
    }
}

// "TD003P" → "T D, cero cero tres P"   ·   "PG001" → "P G, cero cero uno"
const DIGITS = ['cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
function speakCode(code) {
    // Prefijo de letras al inicio, dígitos, y un posible sufijo de letra (turno preferencial).
    const [, prefix = '', digits = '', suffix = ''] = code.match(/^([A-Za-z]*)(\d+)([A-Za-z]*)$/) || [];
    const parts = [];
    if (prefix) parts.push(prefix.split('').join(' '));
    if (digits) parts.push(digits.split('').map(d => DIGITS[+d]).join(' '));
    if (suffix) parts.push(suffix.split('').join(' '));
    return parts.join(', ');
}

function speak(code, counter) {
    if (!voiceOn || !('speechSynthesis' in window)) return;

    const text = `Turno ${speakCode(code)}, ${counter}`;
    speechSynthesis.cancel(); // cancela cualquier locución anterior

    [0, 1].forEach((i) => {
        const u = new SpeechSynthesisUtterance(text);
        u.lang = spanishVoice ? spanishVoice.lang : 'es-MX';
        if (spanishVoice) u.voice = spanishVoice;
        u.rate = 0.9;
        u.pitch = 1;
        if (i === 1) u.volume = 1;
        speechSynthesis.speak(u);
    });
}

/* ---------------------------------------------------------------- Activación */

function activate(showStatus) {
    voiceOn = true;
    getCtx();          // desbloquea el AudioContext dentro del gesto del usuario
    loadVoice();
    chime();           // sonido de prueba
    try { sessionStorage.setItem('turnos.sound', 'on'); } catch (e) { /* modo privado */ }
    if (showStatus) {
        const btn = $('voice');
        if (btn) {
            btn.textContent = 'Sonido activado';
            btn.disabled = true;
            btn.classList.add('is-on');
        }
    }
}

/* ---------------------------------------------------------------- Pantalla */

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
    const box = $('now');
    box.classList.remove('flash-call'); void box.offsetWidth; box.classList.add('flash-call');

    chime();
    setTimeout(() => speak(code, counter), 900); // deja respirar al timbre
}

/* ---------------------------------------------------------------- Arranque */

render(@json($snapshot));

const voiceBtn = $('voice');

if (kioskMode) {
    // Chrome con autoplay habilitado: no hace falta el gesto, se intenta directo.
    if (voiceBtn) voiceBtn.hidden = true;
    activate(false);
} else {
    let saved = null;
    try { saved = sessionStorage.getItem('turnos.sound'); } catch (e) { /* modo privado */ }
    if (saved === 'on') {
        // Sesión ya activada: suena solo si el navegador lo permite.
        activate(true);
    } else if (voiceBtn) {
        voiceBtn.addEventListener('click', () => activate(true));
    }
}

loadVoice();

document.addEventListener('DOMContentLoaded', () => {
    window.onQueueUpdate(({ snapshot, announce: code, counter }) => {
        render(snapshot);
        if (code) announce(code, counter);
    });
    setInterval(() => fetch(@json(route('display.data'))).then(r => r.json()).then(render), 30000);
});
</script>
@endpush
