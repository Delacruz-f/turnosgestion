<x-guest-layout>
    <h1>Acceso del personal</h1>
    <p class="lead">Ingresa con tu cuenta para atender la ventanilla asignada.</p>

    <!-- Session Status -->
    <x-auth-session-status class="auth-status" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="auth-field">
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="auth-errors" />
        </div>

        <!-- Password -->
        <div class="auth-field">
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="auth-errors" />
        </div>

        <!-- Remember Me -->
        <div class="auth-remember">
            <input id="remember_me" type="checkbox" name="remember">
            <label for="remember_me">{{ __('Mantener la sesión abierta') }}</label>
        </div>

        <div class="auth-actions">
            <x-primary-button>
                {{ __('Ingresar') }}
            </x-primary-button>
        </div>

        @if (Route::has('password.request'))
            <p class="auth-links">
                <a href="{{ route('password.request') }}">{{ __('¿Olvidaste tu contraseña?') }}</a>
            </p>
        @endif
    </form>
</x-guest-layout>
