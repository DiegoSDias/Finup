<x-app-layout>
    <div class="finup-page finup-profile"><div class="finup-page-inner">
        <header class="finup-page-heading"><h1>Perfil</h1><p class="finup-page-intro">Gerencie suas informações pessoais.</p></header>
        <div class="finup-profile-sections">
            <section class="finup-profile-card">
                <header><svg aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" /></svg><div><h2>Informações do Perfil</h2><p>Atualize seu nome e endereço de e-mail.</p></div></header>
                <div class="finup-profile-content">@include('profile.partials.update-profile-information-form')</div>
            </section>
            <section class="finup-profile-card">
                <header><svg aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 10V7a6 6 0 0 1 12 0v3M5 10h14v11H5z" /></svg><div><h2>Atualizar Senha</h2><p>Use uma senha longa e segura para proteger sua conta.</p></div></header>
                <div class="finup-profile-content">@include('profile.partials.update-password-form')</div>
            </section>
            <section class="finup-profile-card">
                <header><svg aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V3h8v3M6 6l1 15h10l1-15" /></svg><div><h2>Excluir Conta</h2><p>Exclua permanentemente sua conta e todos os dados.</p></div></header>
                <div class="finup-profile-content">@include('profile.partials.delete-user-form')</div>
            </section>
        </div>
    </div></div>
</x-app-layout>
