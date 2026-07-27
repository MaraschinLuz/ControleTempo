@php
    $columns = [
        ['key' => 'column_1', 'letter' => 'A', 'label' => 'Column 1', 'width' => '80px', 'type' => 'text'],
        ['key' => 'column_2', 'letter' => 'B', 'label' => 'Column 2', 'width' => '70px', 'type' => 'text'],
        ['key' => 'demand', 'letter' => 'C', 'label' => 'Demandas', 'width' => '340px', 'type' => 'textarea'],
        ['key' => 'ai_suggestion', 'letter' => 'D', 'label' => 'Sugestão IA', 'width' => '360px', 'type' => 'textarea'],
        ['key' => 'completion_status', 'letter' => 'E', 'label' => 'Foi feito?', 'width' => '165px', 'type' => 'status'],
        ['key' => 'execution_date', 'letter' => 'F', 'label' => 'Data Execução', 'width' => '160px', 'type' => 'date'],
        ['key' => 'responsible', 'letter' => 'G', 'label' => 'Responsável', 'width' => '180px', 'type' => 'text'],
        ['key' => 'client_responsible', 'letter' => 'H', 'label' => 'Responsável Cliente', 'width' => '220px', 'type' => 'text'],
        ['key' => 'client_contact', 'letter' => 'I', 'label' => 'Contato Cliente', 'width' => '190px', 'type' => 'text'],
        ['key' => 'scope', 'letter' => 'J', 'label' => 'Escopo', 'width' => '220px', 'type' => 'textarea'],
        ['key' => 'completed_demands', 'letter' => 'K', 'label' => 'Demandas realizadas', 'width' => '250px', 'type' => 'textarea'],
        ['key' => 'remaining_work', 'letter' => 'L', 'label' => 'O que falta', 'width' => '250px', 'type' => 'textarea'],
        ['key' => 'completion_date', 'letter' => 'M', 'label' => 'Quando finaliza', 'width' => '180px', 'type' => 'date'],
        ['key' => 'hours', 'letter' => 'N', 'label' => 'Quantidade de horas', 'width' => '190px', 'type' => 'number'],
    ];

    $initialRows = old('rows', $scheduleRows->all());
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
            <div>
                <p class="text-sm font-medium text-cyan-700">Planejamento de projetos</p>
                <h1 class="text-2xl font-bold">Cronograma</h1>
                <p class="mt-1 text-sm text-slate-500">Organize as etapas do projeto em uma planilha compartilhada.</p>
            </div>

            <form method="GET" action="{{ route('project-schedules.index') }}" class="w-full xl:w-96">
                <label for="project_id" class="text-xs font-bold uppercase tracking-wide text-slate-500">Projeto</label>
                <select id="project_id" name="project_id" onchange="this.form.submit()" class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500">
                    <option value="">Selecione um projeto</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected($selectedProject?->is($project))>
                            {{ $project->name }} · {{ $project->client->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </x-slot>

    @if($projects->isEmpty())
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-xl">▦</div>
            <h2 class="mt-4 text-lg font-bold">Nenhum projeto disponível</h2>
            <p class="mt-1 text-sm text-slate-500">Cadastre um projeto antes de criar o cronograma.</p>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('projects.create') }}" class="mt-5 inline-flex rounded-xl bg-cyan-500 px-5 py-3 text-sm font-bold text-slate-950">Cadastrar projeto</a>
            @endif
        </section>
    @elseif(! $selectedProject)
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-cyan-50 text-2xl text-cyan-700">▦</div>
            <h2 class="mt-4 text-xl font-bold">Selecione um projeto</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">O cronograma de cada projeto é independente. Use o seletor acima para abrir ou começar uma nova planilha.</p>
        </section>
    @else
        <form
            method="POST"
            action="{{ route('project-schedules.update', $selectedProject) }}"
            x-data="{
                rows: @js($initialRows),
                nextKey: 1,
                blankRow() {
                    return {
                        column_1: '', column_2: '', demand: '', ai_suggestion: '',
                        completion_status: '', execution_date: '', responsible: '',
                        client_responsible: '', client_contact: '', scope: '',
                        completed_demands: '', remaining_work: '', completion_date: '', hours: ''
                    }
                },
                addRow() {
                    this.rows.push({ ...this.blankRow(), _key: 'new-' + this.nextKey++ })
                    this.$nextTick(() => {
                        const cells = this.$root.querySelectorAll('[data-first-cell]')
                        cells[cells.length - 1]?.focus()
                    })
                },
                removeRow(index) {
                    this.rows.splice(index, 1)
                },
                totalHours() {
                    return this.rows.reduce((total, row) => total + (Number.parseFloat(row.hours) || 0), 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                }
            }"
            x-init="
                rows = rows.map((row, index) => ({ ...blankRow(), ...row, _key: row._key ?? 'saved-' + index }))
                if (rows.length === 0) addRow()
            "
        >
            @csrf
            @method('PUT')

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-200 bg-white px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="truncate font-bold text-slate-900">{{ $selectedProject->name }}</p>
                        <p class="truncate text-sm text-slate-500">{{ $selectedProject->client->name }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600">
                            <span x-text="rows.length"></span> <span x-text="rows.length === 1 ? 'linha' : 'linhas'"></span>
                        </span>
                        <span class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                            Total: <span x-text="totalHours()"></span>h
                        </span>
                        <button type="submit" class="rounded-xl bg-cyan-500 px-5 py-2.5 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2">
                            Salvar cronograma
                        </button>
                    </div>
                </div>

                <div class="border-b border-slate-200 bg-slate-50 px-5 py-2 text-xs text-slate-500">
                    Clique em uma célula para editar. A planilha pode ser rolada horizontalmente.
                </div>

                <div class="max-h-[68vh] overflow-auto">
                    <table class="border-separate border-spacing-0 text-xs">
                        <thead class="sticky top-0 z-20">
                            <tr class="h-7 bg-slate-100 text-center font-semibold text-slate-500">
                                <th class="sticky left-0 z-30 w-12 min-w-12 border-b border-r border-slate-300 bg-slate-200"></th>
                                @foreach($columns as $column)
                                    <th style="min-width: {{ $column['width'] }}; width: {{ $column['width'] }}" class="border-b border-r border-slate-300 bg-slate-100 px-2 py-1">
                                        {{ $column['letter'] }}
                                    </th>
                                @endforeach
                                <th class="sticky right-0 z-30 w-12 min-w-12 border-b border-slate-300 bg-slate-200"></th>
                            </tr>
                            <tr class="bg-[#d9ead3] text-left font-bold text-slate-800">
                                <th class="sticky left-0 z-30 border-b border-r border-slate-300 bg-slate-200 px-2 py-3 text-center text-slate-500">#</th>
                                @foreach($columns as $column)
                                    <th style="min-width: {{ $column['width'] }}; width: {{ $column['width'] }}" class="border-b border-r border-slate-300 bg-[#d9ead3] px-3 py-3">
                                        {{ $column['label'] }}
                                    </th>
                                @endforeach
                                <th class="sticky right-0 z-30 border-b border-slate-300 bg-slate-200 px-2 py-3 text-center text-slate-500">Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template x-for="(row, index) in rows" :key="row._key">
                                <tr class="group align-top hover:bg-cyan-50/30">
                                    <th class="sticky left-0 z-10 border-b border-r border-slate-300 bg-slate-100 px-2 py-5 text-center font-semibold text-slate-500 group-hover:bg-cyan-50" x-text="index + 1"></th>

                                    @foreach($columns as $column)
                                        <td style="min-width: {{ $column['width'] }}; width: {{ $column['width'] }}" class="border-b border-r border-slate-300 bg-white p-0 group-hover:bg-cyan-50/20">
                                            @if($column['type'] === 'textarea')
                                                <textarea
                                                    :name="'rows[' + index + '][{{ $column['key'] }}]'"
                                                    x-model="row.{{ $column['key'] }}"
                                                    rows="2"
                                                    class="block min-h-16 w-full resize-y border-0 bg-transparent px-3 py-2 text-xs leading-5 text-slate-800 placeholder:text-slate-300 focus:bg-cyan-50 focus:ring-2 focus:ring-inset focus:ring-cyan-500"
                                                    placeholder="Digite aqui"
                                                ></textarea>
                                            @elseif($column['type'] === 'status')
                                                <select
                                                    :name="'rows[' + index + '][{{ $column['key'] }}]'"
                                                    x-model="row.{{ $column['key'] }}"
                                                    class="block min-h-16 w-full border-0 bg-transparent px-3 py-2 text-xs font-semibold focus:bg-cyan-50 focus:ring-2 focus:ring-inset focus:ring-cyan-500"
                                                    :class="{
                                                        'text-emerald-700': row.completion_status === 'Sim',
                                                        'text-red-700': row.completion_status === 'Não',
                                                        'text-amber-700': row.completion_status === 'Em andamento',
                                                        'text-indigo-700': row.completion_status === 'Agendado'
                                                    }"
                                                >
                                                    <option value="">Selecione</option>
                                                    <option value="Sim">Sim</option>
                                                    <option value="Não">Não</option>
                                                    <option value="Em andamento">Em andamento</option>
                                                    <option value="Agendado">Agendado</option>
                                                </select>
                                            @elseif($column['type'] === 'date')
                                                <input
                                                    type="date"
                                                    :name="'rows[' + index + '][{{ $column['key'] }}]'"
                                                    x-model="row.{{ $column['key'] }}"
                                                    class="block min-h-16 w-full border-0 bg-transparent px-3 py-2 text-xs focus:bg-cyan-50 focus:ring-2 focus:ring-inset focus:ring-cyan-500"
                                                >
                                            @elseif($column['type'] === 'number')
                                                <input
                                                    type="number"
                                                    min="0"
                                                    max="999999.99"
                                                    step="0.25"
                                                    :name="'rows[' + index + '][{{ $column['key'] }}]'"
                                                    x-model="row.{{ $column['key'] }}"
                                                    class="block min-h-16 w-full border-0 bg-transparent px-3 py-2 text-right text-xs tabular-nums focus:bg-cyan-50 focus:ring-2 focus:ring-inset focus:ring-cyan-500"
                                                    placeholder="0,00"
                                                >
                                            @else
                                                <input
                                                    type="text"
                                                    :name="'rows[' + index + '][{{ $column['key'] }}]'"
                                                    x-model="row.{{ $column['key'] }}"
                                                    @if($column['key'] === 'column_1') data-first-cell @endif
                                                    class="block min-h-16 w-full border-0 bg-transparent px-3 py-2 text-xs focus:bg-cyan-50 focus:ring-2 focus:ring-inset focus:ring-cyan-500"
                                                    placeholder="Digite aqui"
                                                >
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="sticky right-0 z-10 border-b border-slate-300 bg-slate-100 p-2 text-center group-hover:bg-cyan-50">
                                        <button
                                            type="button"
                                            @click="removeRow(index)"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500"
                                            title="Remover linha"
                                            aria-label="Remover linha"
                                        >×</button>
                                    </td>
                                </tr>
                            </template>

                            <tr>
                                <th class="sticky left-0 z-10 border-b border-r border-slate-300 bg-slate-100"></th>
                                <td colspan="{{ count($columns) }}" class="border-b border-r border-slate-300 bg-white p-2">
                                    <button type="button" @click="addRow()" class="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-cyan-300 bg-cyan-50/60 px-4 py-3 text-sm font-bold text-cyan-800 transition hover:border-cyan-500 hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                                        <span class="text-lg leading-none">＋</span>
                                        Adicionar nova linha
                                    </button>
                                </td>
                                <td class="sticky right-0 z-10 border-b border-slate-300 bg-slate-100"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-950 focus:ring-offset-2">
                    Salvar cronograma
                </button>
            </div>
        </form>
    @endif
</x-app-layout>
