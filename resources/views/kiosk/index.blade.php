@extends('layouts.turnos')
@section('title', 'Solicitar turno')

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
            <div class="clock">
                <span class="clock__time" id="clock-time">--:--</span>
                <span class="clock__date" id="clock-date"></span>
            </div>
            <a class="bar__link" href="{{ route('agent.panel') }}">Acceso del personal</a>
        </div>
    </div>
</header>

<main class="wrap" id="contenido">
    <div class="page-head">
        <span class="eyebrow">Mesa de partes virtual</span>
        <h1>Solicita tu turno</h1>
        <p class="lead">Elige el trámite que necesitas. Recibirás un código para seguir tu posición desde tu celular.</p>
    </div>

    <form method="POST" action="{{ route('tickets.store') }}" id="f" class="kiosk-form">
        @csrf

        <label class="field" for="document">
            <span class="field__label">DNI o documento <span class="field__hint">Opcional</span></span>
            <input type="text" id="document" name="document" value="{{ old('document') }}" inputmode="text" maxlength="20" autocomplete="off" aria-describedby="document-help">
            <span class="field__help" id="document-help">Solo letras, números y guiones. Sirve para identificar tu turno.</span>
        </label>
        @error('document')<p class="err" role="alert">{{ $message }}</p>@enderror

        <label class="choice" for="priority">
            <input type="checkbox" id="priority" name="priority" value="1" @checked(old('priority'))>
            <span class="choice__text">
                <strong>Atención preferencial</strong>
                <span>Para adulto mayor, gestante o persona con discapacidad.</span>
            </span>
        </label>

        @error('service_id')<p class="err" role="alert">{{ $message }}</p>@enderror

        @if ($services->isEmpty())
            <div class="empty">
                <h2>No hay trámites disponibles</h2>
                <p>En este momento no hay servicios habilitados. Acércate a la ventanilla de orientación para que te ayuden.</p>
            </div>
        @else
            <h2 class="sr-only">Trámites disponibles</h2>
            <div class="services">
                @foreach ($services as $s)
                    <article class="service">
                        <div class="service__head">
                            <span class="service__prefix" aria-hidden="true">{{ $s->prefix }}</span>
                            <h3 class="service__name">{{ $s->name }}</h3>
                        </div>
                        <p class="service__desc">{{ $s->description }}</p>
                        <p class="service__wait">
                            <span data-wait="{{ $s->id }}">{{ $s->waiting }} {{ $s->waiting === 1 ? 'persona espera' : 'personas esperan' }}</span>
                            <span class="service__eta" data-eta="{{ $s->id }}">≈ {{ $s->waiting * 5 }} min</span>
                        </p>
                        <button class="btn btn--signal btn--block" name="service_id" value="{{ $s->id }}">Obtener turno</button>
                    </article>
                @endforeach
            </div>
        @endif
    </form>
</main>

<footer class="site-foot">
    <p>Si necesitas ayuda, acércate a la ventanilla de orientación.</p>
</footer>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Reloj y fecha en vivo
    const timeEl = document.getElementById('clock-time');
    const dateEl = document.getElementById('clock-date');
    const fmtTime = new Intl.DateTimeFormat('es-PE', { hour: '2-digit', minute: '2-digit' });
    const fmtDate = new Intl.DateTimeFormat('es-PE', { weekday: 'long', day: 'numeric', month: 'long' });
    const tick = () => {
        const now = new Date();
        timeEl.textContent = fmtTime.format(now);
        dateEl.textContent = fmtDate.format(now);
    };
    tick();
    setInterval(tick, 15000);

    // Espera por servicio en tiempo real
    window.onQueueUpdate(({ snapshot }) => {
        snapshot.waiting.forEach(w => {
            const el = document.querySelector(`[data-wait="${w.id}"]`);
            if (el) el.textContent = `${w.waiting} ${w.waiting === 1 ? 'persona espera' : 'personas esperan'}`;
            const eta = document.querySelector(`[data-eta="${w.id}"]`);
            if (eta) eta.textContent = `≈ ${w.waiting * 5} min`;
        });
    });

    // Evita doble envío
    document.getElementById('f').addEventListener('submit', e => {
        e.target.querySelectorAll('button').forEach(b => setTimeout(() => b.disabled = true));
    });
});
</script>
@endpush
