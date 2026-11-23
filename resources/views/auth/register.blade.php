<x-guest-layout>
    <h1 class="sr-only">Criar conta no FINUP</h1>
    <form method="POST" action="{{ route('register') }}" class="finup-auth-form">
        @csrf
        <div>
            <label for="name">Nome completo</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Escreva o seu nome completo" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="finup-auth-error" />
        </div>
        <div>
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Escreva o seu e-mail" required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="finup-auth-error" />
        </div>
        <div>
            <label for="password">Senha</label>
            <input id="password" type="password" name="password" placeholder="Escreva a sua senha" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="finup-auth-error" />
        </div>
        <div>
            <label for="password_confirmation">Confirmar senha</label>
            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirme a sua senha" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="finup-auth-error" />
        </div>
        <button type="submit" class="finup-auth-submit">Criar conta</button>
        <p class="finup-auth-switch">Já tem uma conta? <a href="{{ route('login') }}">Entre</a></p>
    </form>
</x-guest-layout>
