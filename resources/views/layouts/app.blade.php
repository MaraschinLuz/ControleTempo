<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Controle de Horas') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas font-sans text-ink antialiased" x-data="appShell" @keydown.escape.window="menu = false">
<a href="#conteudo-principal" class="sr-only z-[60] rounded-lg bg-brand-500 px-4 py-2 font-bold text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Ir para o conteúdo</a>
<div class="min-h-screen lg:flex">
    <div x-cloak x-show="menu" x-transition.opacity class="fixed inset-0 z-30 bg-ink/70 backdrop-blur-sm lg:hidden" @click="menu = false"></div>

    <aside
        id="menu-lateral"
        :class="menu ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-40 flex h-screen w-72 flex-col overflow-hidden bg-ink text-white shadow-2xl shadow-ink/30 transition-transform duration-300 lg:sticky lg:top-0 lg:translate-x-0"
        aria-label="Menu principal"
    >
        <div class="pointer-events-none absolute -right-20 -top-24 h-56 w-56 rounded-full bg-brand-400/10 blur-3xl"></div>
        <div class="relative flex h-20 shrink-0 items-center border-b border-white/[0.08] px-5">
            <a href="{{ route('dashboard') }}" class="group flex min-w-0 flex-1 items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-lg shadow-brand-950/40 transition group-hover:scale-105">
                    <x-icon name="clock" class="h-6 w-6" />
                </span>
                <span class="min-w-0">
                    <strong class="block truncate text-base font-black tracking-tight">Tempo Interno</strong>
                    <small class="block truncate text-xs font-medium text-slate-400">Gestão de horas e entregas</small>
                </span>
            </a>
            <button type="button" @click="menu = false" class="ml-2 rounded-lg p-2 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden" aria-label="Fechar menu">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        </div>

        <nav class="scrollbar-subtle relative flex-1 space-y-1 overflow-y-auto px-4 py-5 text-sm">
            <p class="mb-2 px-3 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Navegação</p>
            <x-nav-link-sidebar icon="home" :href="route('dashboard')" :active="request()->routeIs('dashboard')">Visão geral</x-nav-link-sidebar>
            <x-nav-link-sidebar icon="entries" :href="route('time-entries.index')" :active="request()->routeIs('time-entries.index', 'time-entries.show', 'time-entries.edit')">Registros</x-nav-link-sidebar>
            <x-nav-link-sidebar icon="plus-circle" :href="route('time-entries.create')" :active="request()->routeIs('time-entries.create')">Adicionar horas</x-nav-link-sidebar>
            <x-nav-link-sidebar icon="chart" :href="route('reports.index')" :active="request()->routeIs('reports.*')">Relatórios</x-nav-link-sidebar>
            <x-nav-link-sidebar icon="calendar" :href="route('project-schedules.index')" :active="request()->routeIs('project-schedules.*')">Cronograma</x-nav-link-sidebar>
            <x-nav-link-sidebar icon="kanban" :href="route('demands.index')" :active="request()->routeIs('demands.*')">Demandas</x-nav-link-sidebar>

            @if(auth()->user()->isAdmin())
                <p class="mb-2 px-3 pb-1 pt-6 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Administração</p>
                <x-nav-link-sidebar icon="users" :href="route('users.index')" :active="request()->routeIs('users.*')">Usuários</x-nav-link-sidebar>
                <x-nav-link-sidebar icon="building" :href="route('clients.index')" :active="request()->routeIs('clients.*')">Clientes</x-nav-link-sidebar>
                <x-nav-link-sidebar icon="folder" :href="route('projects.index')" :active="request()->routeIs('projects.*')">Projetos</x-nav-link-sidebar>
                <x-nav-link-sidebar icon="activity" :href="route('activities.index')" :active="request()->routeIs('activities.*')">Atividades</x-nav-link-sidebar>
                <x-nav-link-sidebar icon="settings" :href="route('settings.edit')" :active="request()->routeIs('settings.*')">Configurações</x-nav-link-sidebar>
            @endif
        </nav>

        <div class="relative shrink-0 border-t border-white/[0.08] bg-black/10 p-4">
            <div class="flex items-center gap-3 rounded-2xl border border-white/[0.08] bg-white/[0.04] p-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-400/15 text-sm font-black text-brand-200 ring-1 ring-inset ring-brand-300/20">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </span>
                <a href="{{ route('profile.edit') }}" class="min-w-0 flex-1 rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                    <strong class="block truncate text-sm text-white">{{ auth()->user()->name }}</strong>
                    <span class="block truncate text-xs text-slate-400">{{ auth()->user()->role->label() }}</span>
                </a>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <a href="{{ route('profile.edit') }}" @class(['flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-bold transition', 'bg-white/10 text-white' => request()->routeIs('profile.*'), 'text-slate-400 hover:bg-white/[0.06] hover:text-white' => ! request()->routeIs('profile.*')])>
                    <x-icon name="user" class="h-4 w-4" /> Perfil
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="flex w-full items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-400 transition hover:bg-rose-400/10 hover:text-rose-300">
                        <x-icon name="logout" class="h-4 w-4" /> Sair
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="relative min-w-0 flex-1 overflow-hidden">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(circle_at_top_right,_rgba(15,76,92,0.13),_transparent_45%),radial-gradient(circle_at_top_left,_rgba(18,18,18,0.05),_transparent_40%)]"></div>
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-canvas/80 bg-white/85 px-4 shadow-sm shadow-slate-200/30 backdrop-blur-xl lg:hidden">
            <button type="button" @click="menu = true" class="grid h-10 w-10 place-items-center rounded-xl border border-canvas bg-white text-slate-700 shadow-sm transition hover:border-brand-300 hover:text-brand-700" aria-controls="menu-lateral" :aria-expanded="menu" aria-label="Abrir menu">
                <x-icon name="menu" class="h-5 w-5" />
            </button>
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-black tracking-tight text-ink">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-500 text-white"><x-icon name="clock" class="h-[18px] w-[18px]" /></span>
                Tempo Interno
            </a>
            <a href="{{ route('profile.edit') }}" class="grid h-10 w-10 place-items-center rounded-xl bg-ink text-sm font-black text-white" aria-label="Abrir perfil">
                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </a>
        </header>

        <main id="conteudo-principal" class="relative mx-auto min-h-screen w-full max-w-[1600px] p-4 sm:p-6 lg:p-8 xl:p-10">
            @if(isset($header))
                <div class="mb-7 border-b border-canvas/80 pb-6">{{ $header }}</div>
            @endif
            <x-flash-messages />
            {{ $slot }}
        </main>
    </div>
</div>
<x-confirmation-modal />
</body>
</html>
