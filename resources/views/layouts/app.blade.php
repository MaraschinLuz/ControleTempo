<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Controle de Horas') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased" x-data="appShell">
<div class="min-h-screen lg:flex">
    <div x-show="menu" x-transition.opacity class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden" @click="menu=false"></div>
    <aside :class="menu ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-slate-950 text-white transition-transform lg:sticky lg:translate-x-0">
        <a href="{{ route('dashboard') }}" class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-400 font-black text-slate-950">T</span>
            <span><strong class="block text-lg">Tempo Interno</strong><small class="text-slate-400">Controle de horas</small></span>
        </a>
        <nav class="flex-1 space-y-1 overflow-y-auto p-4 text-sm">
            <x-nav-link-sidebar :href="route('dashboard')" :active="request()->routeIs('dashboard')">Visão geral</x-nav-link-sidebar>
            <x-nav-link-sidebar :href="route('time-entries.index')" :active="request()->routeIs('time-entries.*')">Registros</x-nav-link-sidebar>
            <x-nav-link-sidebar :href="route('time-entries.create')" :active="request()->routeIs('time-entries.create')">Adicionar horas</x-nav-link-sidebar>
            <x-nav-link-sidebar :href="route('reports.index')" :active="request()->routeIs('reports.*')">Relatórios</x-nav-link-sidebar>
            <x-nav-link-sidebar :href="route('project-schedules.index')" :active="request()->routeIs('project-schedules.*')">Cronograma</x-nav-link-sidebar>
            @if(auth()->user()->isAdmin())
                <p class="px-3 pb-1 pt-6 text-xs font-bold uppercase tracking-widest text-slate-500">Administração</p>
                <x-nav-link-sidebar :href="route('users.index')" :active="request()->routeIs('users.*')">Usuários</x-nav-link-sidebar>
                <x-nav-link-sidebar :href="route('clients.index')" :active="request()->routeIs('clients.*')">Clientes</x-nav-link-sidebar>
                <x-nav-link-sidebar :href="route('projects.index')" :active="request()->routeIs('projects.*')">Projetos</x-nav-link-sidebar>
                <x-nav-link-sidebar :href="route('activities.index')" :active="request()->routeIs('activities.*')">Atividades</x-nav-link-sidebar>
                <x-nav-link-sidebar :href="route('settings.edit')" :active="request()->routeIs('settings.*')">Configurações</x-nav-link-sidebar>
            @endif
        </nav>
        <div class="border-t border-white/10 p-4">
            <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 hover:bg-white/10"><strong class="block text-sm">{{ auth()->user()->name }}</strong><span class="text-xs text-slate-400">{{ auth()->user()->role->label() }}</span></a>
            <form action="{{ route('logout') }}" method="POST">@csrf<button class="mt-2 w-full rounded-lg px-3 py-2 text-left text-sm text-slate-300 hover:bg-white/10">Sair</button></form>
        </div>
    </aside>
    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 flex h-16 items-center border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:hidden">
            <button @click="menu=true" class="rounded-lg border border-slate-200 p-2" aria-label="Abrir menu">☰</button>
            <strong class="ml-3">Tempo Interno</strong>
        </header>
        <main class="p-4 sm:p-6 lg:p-8">
            @if(isset($header))<div class="mb-6">{{ $header }}</div>@endif
            <x-flash-messages />
            {{ $slot }}
        </main>
    </div>
</div>
<x-confirmation-modal />
</body>
</html>
