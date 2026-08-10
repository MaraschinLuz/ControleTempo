@php
    $columns = [
        'pending' => [
            'label' => 'Pendentes',
            'subtitle' => 'Aguardando início',
            'dot' => 'bg-amber-400',
            'border' => 'border-amber-200',
            'items' => $demands->where('status', \App\Enums\DemandStatus::Pending)->values(),
        ],
        'in_progress' => [
            'label' => 'Em andamento',
            'subtitle' => 'Trabalho ativo',
            'dot' => 'bg-brand-400',
            'border' => 'border-brand-200',
            'items' => $demands->where('status', \App\Enums\DemandStatus::InProgress)->values(),
        ],
        'completed' => [
            'label' => 'Concluídas',
            'subtitle' => 'Trabalho finalizado',
            'dot' => 'bg-emerald-400',
            'border' => 'border-emerald-200',
            'items' => $demands->where('status', \App\Enums\DemandStatus::Completed)->values(),
        ],
    ];

    $priorityClasses = [
        'low' => 'bg-canvas text-slate-600',
        'medium' => 'bg-blue-50 text-blue-700',
        'high' => 'bg-orange-50 text-orange-700',
        'urgent' => 'bg-rose-50 text-rose-700',
    ];

    $density = $demands->count() > 36 ? 'density-tight' : ($demands->count() > 20 ? 'density-compact' : '');
    $priorityLabel = match (request('priority')) {
        'low' => 'Baixa',
        'medium' => 'Média',
        'high' => 'Alta',
        'urgent' => 'Urgente',
        default => null,
    };
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quadro de demandas · {{ $selectedUser->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4 landscape; margin: 7mm; }

        @media print {
            body { background: white !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .share-sheet { max-width: none !important; padding: 0 !important; }
            .share-board { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; gap: 6px !important; }
            .share-column { break-inside: avoid; padding: 6px !important; }
            .share-card { break-inside: avoid; box-shadow: none !important; }
        }

        .density-compact .share-card { padding: .55rem; }
        .density-compact .share-card-description { display: none; }
        .density-compact .share-card-list { gap: .4rem; }
        .density-tight .share-card { padding: .4rem; }
        .density-tight .share-card-description, .density-tight .share-client, .density-tight .share-deadline-label { display: none; }
        .density-tight .share-card-list { gap: .3rem; }
        .density-tight .share-card-title { font-size: .72rem; line-height: 1rem; }
        .density-tight .share-project { margin-top: .25rem; padding-top: .25rem; font-size: .62rem; }
    </style>
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
    <div class="no-print fixed bottom-5 right-5 z-20 flex flex-wrap justify-end gap-2 rounded-2xl border border-canvas bg-white/95 p-2 shadow-xl backdrop-blur">
        <a href="{{ route('demands.index', request()->query()) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-600">Voltar ao quadro</a>
        <button type="button" onclick="window.print()" class="rounded-xl bg-brand-400 px-5 py-2.5 text-sm font-black text-ink">Imprimir ou salvar em PDF</button>
    </div>

    <main class="share-sheet {{ $density }} mx-auto max-w-[1600px] p-5 lg:p-8">
        <header class="mb-5 flex items-start justify-between gap-6 border-b-2 border-slate-900 pb-4">
            <div>
                <div class="mb-2 flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-400 text-lg font-black text-ink">T</span>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.2em] text-brand-700">Resumo para compartilhar</p>
                        <h1 class="text-2xl font-black tracking-tight text-ink">Quadro de demandas</h1>
                    </div>
                </div>
                <p class="text-sm text-slate-500">Responsável: <strong class="text-ink">{{ $selectedUser->name }}</strong></p>
            </div>
            <div class="text-right text-xs text-slate-500">
                <p class="font-bold text-ink">Atualizado em {{ now()->format('d/m/Y \à\s H:i') }}</p>
                <p class="mt-1">{{ $demands->count() }} {{ $demands->count() === 1 ? 'demanda exibida' : 'demandas exibidas' }}</p>
                @if($selectedProject)<p class="mt-1">Projeto: <strong>{{ $selectedProject->name }}</strong></p>@endif
                @if($priorityLabel)<p class="mt-1">Prioridade: <strong>{{ $priorityLabel }}</strong></p>@endif
                @if(request('search'))<p class="mt-1">Busca: <strong>“{{ request('search') }}”</strong></p>@endif
            </div>
        </header>

        <div class="share-board grid items-start gap-4 md:grid-cols-3">
            @foreach($columns as $status => $column)
                <section class="share-column rounded-2xl border {{ $column['border'] }} bg-slate-50 p-3">
                    <div class="mb-3 flex items-center justify-between gap-3 px-1">
                        <div class="flex items-center gap-2.5">
                            <span class="h-3 w-3 rounded-full {{ $column['dot'] }}"></span>
                            <div>
                                <h2 class="text-sm font-black text-ink">{{ $column['label'] }}</h2>
                                <p class="text-[10px] text-slate-500">{{ $column['subtitle'] }}</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-700 shadow-sm">{{ $column['items']->count() }}</span>
                    </div>

                    <div class="share-card-list grid gap-2">
                        @forelse($column['items'] as $demand)
                            <article class="share-card rounded-xl border border-canvas bg-white p-3 shadow-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="share-card-title text-sm font-bold leading-snug text-ink">{{ $demand->title }}</h3>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wide {{ $priorityClasses[$demand->priority->value] }}">{{ $demand->priority->label() }}</span>
                                </div>
                                @if($demand->description)
                                    <p class="share-card-description mt-1.5 line-clamp-2 text-[11px] leading-relaxed text-slate-500">{{ $demand->description }}</p>
                                @endif
                                <div class="share-project mt-2 flex items-end justify-between gap-2 border-t border-slate-100 pt-2 text-[10px]">
                                    <div class="min-w-0">
                                        <strong class="block truncate text-slate-700">{{ $demand->project->name }}</strong>
                                        <span class="share-client block truncate text-slate-400">{{ $demand->project->client->name }}</span>
                                    </div>
                                    @if($demand->due_date)
                                        <span class="shrink-0 font-semibold {{ $demand->due_date->isBefore(today()) && $demand->status !== \App\Enums\DemandStatus::Completed ? 'text-rose-700' : 'text-slate-500' }}">
                                            <span class="share-deadline-label">Prazo </span>{{ $demand->due_date->format('d/m') }}
                                        </span>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-xl border-2 border-dashed border-canvas py-8 text-center text-xs font-semibold text-slate-400">Nenhuma demanda</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <footer class="mt-4 flex items-center justify-between border-t border-canvas pt-3 text-[10px] text-slate-400">
            <span>Tempo Interno · Controle de demandas</span>
            <span>Pendente → Em andamento → Concluída</span>
        </footer>
    </main>
</body>
</html>
