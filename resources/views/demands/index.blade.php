@php
    $demandData = $demands->map(fn ($demand) => [
        'id' => $demand->id,
        'title' => $demand->title,
        'description' => $demand->description,
        'project_id' => $demand->project_id,
        'project_name' => $demand->project->name,
        'client_name' => $demand->project->client->name,
        'status' => $demand->status->value,
        'priority' => $demand->priority->value,
        'priority_label' => $demand->priority->label(),
        'due_date' => $demand->due_date?->format('Y-m-d'),
    ])->values();

    $oldForm = old('_form_mode') === 'demand' ? [
        'id' => old('_demand_id') ? (int) old('_demand_id') : null,
        'title' => old('title', ''),
        'description' => old('description', ''),
        'project_id' => (string) old('project_id', ''),
        'status' => old('status', 'pending'),
        'priority' => old('priority', 'medium'),
        'due_date' => old('due_date', ''),
    ] : null;

    $columnMeta = [
        'pending' => ['Pendente', 'Aguardando início', 'bg-amber-400', 'text-amber-700', 'border-amber-200'],
        'in_progress' => ['Em andamento', 'Trabalho ativo', 'bg-cyan-400', 'text-cyan-700', 'border-cyan-200'],
        'completed' => ['Concluída', 'Trabalho finalizado', 'bg-emerald-400', 'text-emerald-700', 'border-emerald-200'],
    ];

    $shareQuery = array_filter([
        'user_id' => $selectedUser->id,
        'project_id' => request('project_id'),
        'priority' => request('priority'),
        'due_date' => request('due_date'),
        'search' => request('search'),
    ], fn ($value) => filled($value));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-cyan-700">Controle de demandas</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-950">Quadro de {{ $selectedUser->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Organize as entregas por etapa, projeto e prioridade.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('demands.share', $shareQuery) }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:border-cyan-400 hover:text-cyan-700">
                    Compartilhar quadro
                </a>
                <button type="button" @click="$dispatch('open-demand-modal')" class="rounded-xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-cyan-300">
                    + Nova demanda
                </button>
            </div>
        </div>
    </x-slot>

    <div
        x-data="demandBoard({
            demands: @js($demandData),
            storeUrl: @js(route('demands.store')),
            updateUrl: @js(route('demands.update', ['demand' => '__ID__'])),
            statusUrl: @js(route('demands.status', ['demand' => '__ID__'])),
            deleteUrl: @js(route('demands.destroy', ['demand' => '__ID__'])),
            csrf: @js(csrf_token()),
            openOnError: @js($errors->any() && old('_form_mode') === 'demand'),
            oldForm: @js($oldForm),
        })"
        @keydown.escape.window="closeModal()"
        @open-demand-modal.window="openCreate()"
    >
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('demands.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1fr_1fr_1fr_auto] xl:items-end">
                @if(auth()->user()->isManagerOrAdmin())
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                        Página do usuário
                        <select name="user_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected($selectedUser->id === $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @else
                    <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                @endif
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Projeto
                    <select name="project_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                        <option value="">Todos os projetos</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Prioridade
                    <select name="priority" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                        <option value="">Todas</option>
                        <option value="urgent" @selected(request('priority') === 'urgent')>Urgente</option>
                        <option value="high" @selected(request('priority') === 'high')>Alta</option>
                        <option value="medium" @selected(request('priority') === 'medium')>Média</option>
                        <option value="low" @selected(request('priority') === 'low')>Baixa</option>
                    </select>
                </label>
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Data prevista de término
                    <input name="due_date" type="date" value="{{ request('due_date') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                </label>
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Buscar
                    <input name="search" value="{{ request('search') }}" placeholder="Título ou descrição" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                </label>
                <div class="flex gap-2">
                    <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">Filtrar</button>
                    @if(request()->hasAny(['project_id', 'priority', 'due_date', 'search']))
                        <a href="{{ route('demands.index', ['user_id' => $selectedUser->id]) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600">Limpar</a>
                    @endif
                </div>
            </form>
        </section>

        <div class="mb-4 flex items-center justify-between gap-3">
            <p class="text-sm text-slate-500"><strong class="text-slate-900" x-text="demands.length"></strong> demandas exibidas</p>
            <p x-show="statusMessage" x-transition x-text="statusMessage" aria-live="polite" class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white"></p>
        </div>

        <div class="grid items-start gap-5 xl:grid-cols-3">
            @foreach($columnMeta as $status => [$label, $description, $dotColor, $textColor, $borderColor])
                <section
                    class="rounded-2xl border {{ $borderColor }} bg-slate-100/80 p-3"
                    @dragover.prevent
                    @drop.prevent="dropIn('{{ $status }}')"
                >
                    <div class="mb-3 flex items-center justify-between px-1 py-1">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full {{ $dotColor }}"></span>
                            <div>
                                <h2 class="font-bold text-slate-900">{{ $label }}</h2>
                                <p class="text-xs text-slate-500">{{ $description }}</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black {{ $textColor }}" x-text="column('{{ $status }}').length"></span>
                    </div>

                    <div class="min-h-32 space-y-3">
                        <template x-for="demand in column('{{ $status }}')" :key="demand.id">
                            <article
                                draggable="true"
                                @dragstart="startDragging(demand)"
                                @dragend="draggingId = null"
                                @dblclick="openEdit(demand)"
                                class="group cursor-grab rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-cyan-300 hover:shadow-md active:cursor-grabbing"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-black uppercase tracking-wide"
                                        :class="{
                                            'bg-slate-100 text-slate-600': demand.priority === 'low',
                                            'bg-blue-50 text-blue-700': demand.priority === 'medium',
                                            'bg-orange-50 text-orange-700': demand.priority === 'high',
                                            'bg-rose-50 text-rose-700': demand.priority === 'urgent',
                                        }"
                                        x-text="demand.priority_label"
                                    ></span>
                                    <button type="button" @click="openEdit(demand)" class="rounded-lg px-2 py-1 text-xs font-bold text-slate-400 transition hover:bg-slate-100 hover:text-cyan-700" aria-label="Editar demanda">Editar</button>
                                </div>
                                <h3 class="mt-3 font-bold leading-snug text-slate-950" x-text="demand.title"></h3>
                                <p x-show="demand.description" class="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-500" x-text="demand.description"></p>
                                <div class="mt-4 border-t border-slate-100 pt-3">
                                    <div class="flex items-center gap-2 text-xs text-slate-600">
                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-slate-100 font-black text-slate-500">P</span>
                                        <span class="min-w-0"><strong class="block truncate" x-text="demand.project_name"></strong><span class="block truncate text-slate-400" x-text="demand.client_name"></span></span>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <span
                                            x-show="demand.due_date"
                                            class="text-xs font-semibold"
                                            :class="isOverdue(demand) ? 'text-rose-700' : 'text-slate-500'"
                                        ><span x-text="isOverdue(demand) ? 'Atrasada · ' : 'Prazo · '"></span><span x-text="formatDate(demand.due_date)"></span></span>
                                        <select
                                            :value="demand.status"
                                            @change="moveDemand(demand.id, $event.target.value)"
                                            @click.stop
                                            class="ml-auto rounded-lg border-slate-200 py-1 pl-2 pr-7 text-xs font-semibold text-slate-600"
                                            aria-label="Alterar status"
                                        >
                                            <option value="pending">Pendente</option>
                                            <option value="in_progress">Em andamento</option>
                                            <option value="completed">Concluída</option>
                                        </select>
                                    </div>
                                </div>
                            </article>
                        </template>

                        <button
                            x-show="column('{{ $status }}').length === 0"
                            type="button"
                            @click="openCreate('{{ $status }}')"
                            class="grid min-h-32 w-full place-items-center rounded-2xl border-2 border-dashed border-slate-300 px-5 text-center text-sm font-semibold text-slate-400 transition hover:border-cyan-400 hover:bg-white hover:text-cyan-700"
                        >Nenhuma demanda nesta etapa<br>+ Adicionar demanda</button>
                    </div>
                </section>
            @endforeach
        </div>

        <div x-cloak x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="demand-modal-title">
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="modalOpen" x-transition.opacity @click="closeModal()" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
                <div x-show="modalOpen" x-transition class="relative w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-cyan-700" x-text="form.id ? 'Editar demanda' : 'Nova demanda'"></p>
                            <h2 id="demand-modal-title" class="mt-1 text-xl font-bold text-slate-950" x-text="form.id ? form.title : 'Adicionar ao quadro'"></h2>
                            <p class="mt-1 text-sm text-slate-500">Responsável: {{ $selectedUser->name }}</p>
                        </div>
                        <button type="button" @click="closeModal()" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Fechar">✕</button>
                    </div>

                    <form id="demand-form" method="POST" :action="formAction()" class="grid gap-5 p-6 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" :disabled="!form.id">
                        <input type="hidden" name="_form_mode" value="demand">
                        <input type="hidden" name="_demand_id" :value="form.id || ''">
                        <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">

                        <label class="sm:col-span-2 text-sm font-bold text-slate-700">
                            Título
                            <input x-ref="demandTitle" x-model="form.title" name="title" required maxlength="255" placeholder="Ex.: Revisar fluxo de aprovação" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                        </label>
                        <label class="sm:col-span-2 text-sm font-bold text-slate-700">
                            Descrição <span class="font-normal text-slate-400">(opcional)</span>
                            <textarea x-model="form.description" name="description" rows="4" maxlength="5000" placeholder="Contexto, critérios de aceite e observações..." class="mt-1 block w-full rounded-xl border-slate-300 text-sm"></textarea>
                        </label>
                        <label class="sm:col-span-2 text-sm font-bold text-slate-700">
                            Projeto
                            <select x-model="form.project_id" name="project_id" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                                <option value="">Selecione o projeto</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }} · {{ $project->client->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm font-bold text-slate-700">
                            Status
                            <select x-model="form.status" name="status" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                                <option value="pending">Pendente</option>
                                <option value="in_progress">Em andamento</option>
                                <option value="completed">Concluída</option>
                            </select>
                        </label>
                        <label class="text-sm font-bold text-slate-700">
                            Prioridade
                            <select x-model="form.priority" name="priority" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                                <option value="low">Baixa</option>
                                <option value="medium">Média</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </label>
                        <label class="sm:col-span-2 text-sm font-bold text-slate-700">
                            Prazo <span class="font-normal text-slate-400">(opcional)</span>
                            <input x-model="form.due_date" name="due_date" type="date" class="mt-1 block w-full rounded-xl border-slate-300 text-sm sm:w-1/2">
                        </label>
                    </form>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <form x-show="form.id" method="POST" :action="deleteAction()" data-confirm="A demanda será removida permanentemente do quadro." data-confirm-title="Excluir demanda?" data-confirm-button="Excluir" data-confirm-variant="danger">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-xl px-3 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50">Excluir demanda</button>
                        </form>
                        <div class="ml-auto flex gap-2">
                            <button type="button" @click="closeModal()" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-600">Cancelar</button>
                            <button type="submit" form="demand-form" class="rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-black text-slate-950 hover:bg-cyan-300" x-text="form.id ? 'Salvar alterações' : 'Adicionar demanda'"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
