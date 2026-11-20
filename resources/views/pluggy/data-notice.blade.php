<x-app-layout>
    <div class="finup-page finup-transparency"><div class="finup-page-inner">
        <header class="finup-page-heading flex flex-wrap items-start justify-between gap-5">
            <div><h1>Termo de <span>Transparência</span></h1><p class="finup-page-intro">Revise as condições de uso do FINUP.</p></div>
            <a href="{{ route('pluggy.connections') }}" class="finup-button finup-button--secondary">Ver conexões</a>
        </header>
        <div class="finup-transparency-layout" x-data="{ tab: 0 }">
            <nav class="finup-panel finup-transparency-tabs" role="tablist" aria-label="Transparência dos dados" @keydown.arrow-right.prevent="tab = (tab + 1) % 3; $nextTick(() => $el.querySelectorAll('button')[tab].focus())" @keydown.arrow-left.prevent="tab = (tab + 2) % 3; $nextTick(() => $el.querySelectorAll('button')[tab].focus())">
                <button id="transparency-tab-0" type="button" role="tab" :aria-selected="tab === 0" :tabindex="tab === 0 ? 0 : -1" aria-controls="transparency-panel-0" @click="tab = 0"><x-finup-icon name="card" />1. Movimentações</button>
                <button id="transparency-tab-1" type="button" role="tab" :aria-selected="tab === 1" :tabindex="tab === 1 ? 0 : -1" aria-controls="transparency-panel-1" @click="tab = 1"><x-finup-icon name="alert" />2. Atenção</button>
                <button id="transparency-tab-2" type="button" role="tab" :aria-selected="tab === 2" :tabindex="tab === 2 ? 0 : -1" aria-controls="transparency-panel-2" @click="tab = 2"><x-finup-icon name="data" />3. Dados</button>
            </nav>
            <div class="finup-panel finup-transparency-content">
                <div id="transparency-panel-0" role="tabpanel" aria-labelledby="transparency-tab-0" tabindex="0" x-show="tab === 0">
<h2>Movimentações Importadas</h2><p class="mt-4">Entenda como funcionam os dados recebidos pela Pluggy e por que a sua conferência faz diferença.</p><section class="finup-notice">
                <div class="max-w-3xl">
                    <p class="text-sm font-medium text-blue-200">Leia antes de usar a importação</p>
                    <h2 class="mt-2 text-2xl font-light sm:text-3xl">Os dados importados facilitam o controle, mas não substituem a conferência do seu extrato.</h2>
                    <p class="mt-4 leading-7 text-slate-300">As informações são fornecidas pela sua instituição financeira por meio da Pluggy. Embora trabalhemos para organizar cada lançamento da melhor forma, descrições, categorias, valores e tipos de movimentação podem chegar incompletos, atrasados ou interpretados de maneira diferente da situação real.</p>
                </div>
            </section><section>
                <div class="flex gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-amber-950">O que você precisa fazer</h2>
                        <p class="mt-2 leading-7 text-amber-900">Revise periodicamente as movimentações importadas e ajuste ou exclua o que não representa uma receita ou despesa real. Em caso de divergência, considere o seu extrato bancário e os comprovantes como referência.</p>
                    </div>
                </div>
            </section>
                </div>
                <div id="transparency-panel-1" role="tabpanel" aria-labelledby="transparency-tab-1" tabindex="0" x-show="tab === 1" x-cloak>
<section class="mt-8">
                <h2 class="text-xl font-semibold text-slate-900">Situações que podem exigir atenção</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <article class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 12-4-4m4 4 4-4m6-4v12m0-12 4 4m-4-4-4 4" /></svg></div>
                        <h3 class="mt-4 font-semibold text-slate-900">Transferências entre suas contas</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Uma transferência pode aparecer como saída em uma conta e entrada em outra. Ela não é, por si só, uma nova despesa nem uma nova receita.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h11a4 4 0 010 8H9m-6-8 4-4m-4 4 4 4" /></svg></div>
                        <h3 class="mt-4 font-semibold text-slate-900">Estornos, devoluções e cancelamentos</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">O reembolso de uma compra pode ser importado como entrada. Quando ele apenas desfaz uma compra, verifique se os dois lançamentos devem ser desconsiderados no seu controle.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M5.1 19h13.8c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.37 16c-.77 1.33.19 3 1.73 3z" /></svg></div>
                        <h3 class="mt-4 font-semibold text-slate-900">Duplicidades e lançamentos pendentes</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Algumas instituições podem enviar lançamentos pendentes e, depois, o lançamento confirmado. Datas ou valores também podem ser atualizados após a compra.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6h.01M12 12h.01M12 18h.01" /></svg></div>
                        <h3 class="mt-4 font-semibold text-slate-900">Descrições e categorias</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">O nome do estabelecimento pode ser abreviado ou diferente do que aparece no comprovante. A categoria automática é uma sugestão e pode precisar de correção.</p>
                    </article>
                </div>
            </section>
                </div>
                <div id="transparency-panel-2" role="tabpanel" aria-labelledby="transparency-tab-2" tabindex="0" x-show="tab === 2" x-cloak>
<section class="mt-8 rounded-3xl border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-xl font-semibold text-slate-900">Como manter seus dados mais fiéis à realidade</h2>
                <ol class="mt-5 space-y-4 text-slate-700">
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">1</span><span class="pt-0.5">Compare os lançamentos importados com o extrato da instituição financeira.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">2</span><span class="pt-0.5">Edite, exclua ou crie regras para movimentações que não devem entrar nos seus totais.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">3</span><span class="pt-0.5">Revise o período antes de tomar decisões financeiras com base nos relatórios.</span></li>
                </ol>
                <div class="mt-6 rounded-2xl bg-blue-50 p-4 text-sm leading-6 text-blue-900">Ao manter uma conexão ativa, você reconhece que os dados importados são informativos e se compromete a conferir movimentações relevantes antes de usá-las como base para decisões financeiras.</div>
            </section>
                </div>
            </div>
        </div>
    </div></div>
</x-app-layout>
