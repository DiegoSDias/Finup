<x-guest-layout>
    <h1 class="sr-only">Entrar no FINUP</h1>
    @if (session('status'))<p class="finup-auth-status" role="status">{{ session('status') }}</p>@endif
    <form method="POST" action="{{ route('login') }}" class="finup-auth-form">
        @csrf
        <div>
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Escreva o seu e-mail" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="finup-auth-error" />
        </div>
        <div>
            <label for="password">Senha</label>
            <input id="password" type="password" name="password" placeholder="Escreva a sua senha" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="finup-auth-error" />
        </div>
        <div class="finup-auth-options">
            <label for="remember_me"><input id="remember_me" type="checkbox" name="remember">Lembrar de mim</label>
            @if (Route::has('password.request'))<a href="{{ route('password.request') }}">Esqueci minha senha</a>@endif
        </div>
        <button type="submit" class="finup-auth-submit">Entrar</button>
        <p class="finup-auth-switch">Não tem uma conta? <a href="{{ route('register') }}">Cadastre-se</a></p>
    </form>
</x-guest-layout>
