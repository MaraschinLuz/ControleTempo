@php
    $initialRows = old('rows', $scheduleRows->all());
    $registeredUserNames = $users->pluck('name');
    $unregisteredResponsibleNames = collect($initialRows)
        ->pluck('responsible')
        ->filter()
        ->diff($registeredUserNames)
        ->unique()
        ->values();
    $columnLetter = function (int $number): string {
        $letter = '';

        while ($number > 0) {
            $number--;
            $letter = chr(65 + ($number % 26)).$letter;
            $number = intdiv($number, 26);
        }

        return $letter;
    };
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
        <section class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 shadow-sm">
            <form
                method="POST"
                action="{{ route('project-schedules.import', $selectedProject) }}"
                enctype="multipart/form-data"
                class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
                x-data="{ fileName: '' }"
                data-confirm="A importação substituirá todas as linhas atuais deste cronograma."
                data-confirm-title="Importar planilha?"
                data-confirm-button="Importar e atualizar"
                data-confirm-variant="warning"
            >
                @csrf
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-600 font-bold text-white">X</span>
                    <div>
                        <h2 class="font-bold text-emerald-950">Atualizar usando uma planilha Excel</h2>
                        <p class="mt-1 text-sm text-emerald-800">Use o mesmo padrão do arquivo anexado, com as colunas A–N. A aba válida da planilha substituirá as linhas atuais.</p>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label class="cursor-pointer rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-800 shadow-sm transition hover:border-emerald-500">
                        <input
                            type="file"
                            name="spreadsheet"
                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            required
                            class="sr-only"
                            @change="fileName = $event.target.files[0]?.name ?? ''"
                        >
                        <span x-text="fileName || 'Selecionar arquivo .xlsx'"></span>
                    </label>
                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                        Importar e atualizar
                    </button>
                </div>
            </form>
        </section>

        <form id="add-schedule-column" method="POST" action="{{ route('project-schedules.columns.store', $selectedProject) }}" class="hidden">
            @csrf
            <input type="hidden" name="type" value="text">
        </form>

        @foreach($columns as $column)
            <form
                id="move-column-left-{{ $column->id }}"
                method="POST"
                action="{{ route('project-schedules.columns.move', [$selectedProject, $column]) }}"
                class="hidden"
                data-confirm="A página será recarregada. Salve as alterações das linhas antes de mover a coluna."
                data-confirm-title="Mover coluna para a esquerda?"
                data-confirm-button="Mover coluna"
                data-confirm-variant="warning"
            >
                @csrf
                @method('PATCH')
                <input type="hidden" name="direction" value="left">
            </form>
            <form
                id="move-column-right-{{ $column->id }}"
                method="POST"
                action="{{ route('project-schedules.columns.move', [$selectedProject, $column]) }}"
                class="hidden"
                data-confirm="A página será recarregada. Salve as alterações das linhas antes de mover a coluna."
                data-confirm-title="Mover coluna para a direita?"
                data-confirm-button="Mover coluna"
                data-confirm-variant="warning"
            >
                @csrf
                @method('PATCH')
                <input type="hidden" name="direction" value="right">
            </form>
            @if($column->is_custom)
                <form
                    id="delete-column-{{ $column->id }}"
                    method="POST"
                    action="{{ route('project-schedules.columns.destroy', [$selectedProject, $column]) }}"
                    class="hidden"
                    data-confirm="Os dados desta coluna deixarão de aparecer no cronograma."
                    data-confirm-title="Remover coluna?"
                    data-confirm-button="Remover"
                    data-confirm-variant="danger"
                >
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        @endforeach

        <form
            method="POST"
            action="{{ route('project-schedules.update', $selectedProject) }}"
            x-data="{
                rows: @js($initialRows),
                customColumnKeys: @js($columns->where('is_custom', true)->pluck('column_key')->values()),
                addingColumn: @js($errors->has('label')),
                nextKey: 1,
                blankRow() {
                    return {
                        column_1: '', column_2: '', demand: '', ai_suggestion: '',
                        completion_status: '', execution_date: '', responsible: '',
                        client_responsible: '', client_contact: '', scope: '',
                        completed_demands: '', remaining_work: '', completion_date: '', hours: '',
                        custom_data: {}
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
                startAddingColumn() {
                    this.addingColumn = true
                    this.$nextTick(() => this.$refs.columnName?.focus())
                },
                cancelAddingColumn() {
                    this.addingColumn = false
                    if (this.$refs.columnName) this.$refs.columnName.value = ''
                },
                totalHours() {
                    return this.rows.reduce((total, row) => total + (Number.parseFloat(row.hours) || 0), 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                }
            }"
            x-init="
                rows = rows.map((row, index) => {
                    const normalized = { ...blankRow(), ...row, custom_data: { ...(row.custom_data ?? {}) }, _key: row._key ?? 'saved-' + index }
                    customColumnKeys.forEach(key => normalized.custom_data[key] ??= '')
                    return normalized
                })
                if (rows.length === 0) addRow()
                if (addingColumn) this.$nextTick(() => this.$refs.columnName?.focus())
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
                                    <th style="min-width: {{ $column->width }}px; width: {{ $column->width }}px" class="border-b border-r border-slate-300 bg-slate-100 px-2 py-1">
                                        {{ $columnLetter($loop->iteration) }}
                                    </th>
                                @endforeach
                                <th
                                    :style="addingColumn ? 'min-width:220px;width:220px' : 'min-width:48px;width:48px'"
                                    class="sticky right-0 z-30 border-b border-slate-300 bg-slate-200 text-center transition-[width,min-width]"
                                >
                                    <span x-show="addingColumn" x-cloak>{{ $columnLetter($columns->count() + 1) }}</span>
                                </th>
                            </tr>
                            <tr class="bg-[#d9ead3] text-left font-bold text-slate-800">
                                <th class="sticky left-0 z-30 border-b border-r border-slate-300 bg-slate-200 px-2 py-3 text-center text-slate-500">#</th>
                                @foreach($columns as $column)
                                    <th style="min-width: {{ $column->width }}px; width: {{ $column->width }}px" class="border-b border-r border-slate-300 bg-[#d9ead3] px-2 py-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="min-w-0 truncate px-1" title="{{ $column->label }}">{{ $column->label }}</span>
                                            <span class="flex shrink-0 items-center gap-0.5">
                                                <button
                                                    type="submit"
                                                    form="move-column-left-{{ $column->id }}"
                                                    @disabled($loop->first)
                                                    class="grid h-7 w-7 place-items-center rounded text-sm text-slate-600 hover:bg-white/70 disabled:cursor-not-allowed disabled:opacity-25"
                                                    title="Mover {{ $column->label }} para a esquerda"
                                                    aria-label="Mover {{ $column->label }} para a esquerda"
                                                >←</button>
                                                <button
                                                    type="submit"
                                                    form="move-column-right-{{ $column->id }}"
                                                    @disabled($loop->last)
                                                    class="grid h-7 w-7 place-items-center rounded text-sm text-slate-600 hover:bg-white/70 disabled:cursor-not-allowed disabled:opacity-25"
                                                    title="Mover {{ $column->label }} para a direita"
                                                    aria-label="Mover {{ $column->label }} para a direita"
                                                >→</button>
                                                @if($column->is_custom)
                                                    <button
                                                        type="submit"
                                                        form="delete-column-{{ $column->id }}"
                                                        class="grid h-7 w-7 place-items-center rounded text-base text-red-600 hover:bg-red-50"
                                                        title="Remover {{ $column->label }}"
                                                        aria-label="Remover {{ $column->label }}"
                                                    >×</button>
                                                @endif
                                            </span>
                                        </div>
                                    </th>
                                @endforeach
                                <th
                                    :style="addingColumn ? 'min-width:220px;width:220px' : 'min-width:48px;width:48px'"
                                    :class="addingColumn ? 'bg-indigo-50' : 'bg-slate-200'"
                                    class="sticky right-0 z-30 border-b border-slate-300 p-1.5 text-center text-slate-500 transition-[width,min-width]"
                                >
                                    <button
                                        x-show="!addingColumn"
                                        type="button"
                                        @click="startAddingColumn()"
                                        class="mx-auto grid h-8 w-8 place-items-center rounded-lg border border-dashed border-slate-400 bg-white text-lg font-medium text-slate-500 transition hover:border-indigo-500 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        title="Adicionar coluna"
                                        aria-label="Adicionar coluna"
                                    >＋</button>

                                    <div x-show="addingColumn" x-cloak class="flex items-center gap-1">
                                        <input
                                            x-ref="columnName"
                                            form="add-schedule-column"
                                            name="label"
                                            value="{{ old('label') }}"
                                            maxlength="80"
                                            required
                                            placeholder="Nome da coluna"
                                            @keydown.escape.prevent="cancelAddingColumn()"
                                            class="min-w-0 flex-1 rounded-lg border-indigo-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                        <button
                                            type="submit"
                                            form="add-schedule-column"
                                            class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            title="Confirmar nova coluna"
                                            aria-label="Confirmar nova coluna"
                                        >✓</button>
                                        <button
                                            type="button"
                                            @click="cancelAddingColumn()"
                                            class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-base text-slate-500 hover:bg-slate-200 hover:text-slate-800"
                                            title="Cancelar"
                                            aria-label="Cancelar"
                                        >×</button>
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <template x-for="(row, index) in rows" :key="row._key">
                                <tr class="group align-top hover:bg-cyan-50/30">
                                    <th class="sticky left-0 z-10 border-b border-r border-slate-300 bg-slate-100 px-2 py-5 text-center font-semibold text-slate-500 group-hover:bg-cyan-50" x-text="index + 1"></th>

                                    @foreach($columns as $column)
                                        @php
                                            $columnKey = $column->column_key;
                                            $inputName = $column->is_custom ? "custom_data][{$columnKey}" : $columnKey;
                                            $model = $column->is_custom ? "row.custom_data['{$columnKey}']" : "row.{$columnKey}";
                                        @endphp
                                        <td style="min-width: {{ $column->width }}px; width: {{ $column->width }}px" class="border-b border-r border-slate-300 bg-white p-0 group-hover:bg-cyan-50/20">
                                            @if($column->type === 'textarea')
                                                <textarea
                                                    :name="'rows[' + index + '][{{ $inputName }}]'"
                                                    x-model="{{ $model }}"
                                                    rows="2"
                                                    class="block min-h-16 w-full resize-y border-0 bg-transparent px-3 py-2 text-xs leading-5 text-slate-800 placeholder:text-slate-300 focus:bg-cyan-50 focus:ring-2 focus:ring-inset focus:ring-cyan-500"
                                                    placeholder="Digite aqui"
                                                ></textarea>
                                            @elseif($column->type === 'status')
                                                <select
                                                    :name="'rows[' + index + '][{{ $inputName }}]'"
                                                    x-model="{{ $model }}"
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
                                            @elseif($column->type === 'user')
                                                <select
                                                    :name="'rows[' + index + '][{{ $inputName }}]'"
                                                    x-model="{{ $model }}"
                                                    class="block min-h-16 w-full border-0 bg-transparent px-3 py-2 text-xs font-semibold text-slate-700 focus:bg-cyan-50 focus:ring-2 focus:ring-inset focus:ring-cyan-500"
                                                >
                                                    <option value="">Selecione o responsável</option>
                                                    @foreach($users as $user)
                                                        <option value="{{ $user->name }}">
                                                            {{ $user->name }}@if(! $user->active) · Inativo @endif
                                                        </option>
                                                    @endforeach
                                                    @foreach($unregisteredResponsibleNames as $responsibleName)
                                                        <option value="{{ $responsibleName }}">{{ $responsibleName }} · Não cadastrado</option>
                                                    @endforeach
                                                </select>
                                            @elseif($column->type === 'date')
                                                <input
                                                    type="date"
                                                    :name="'rows[' + index + '][{{ $inputName }}]'"
                                                    x-model="{{ $model }}"
                                                    class="block min-h-16 w-full border-0 bg-transparent px-3 py-2 text-xs focus:bg-cyan-50 focus:ring-2 focus:ring-inset focus:ring-cyan-500"
                                                >
                                            @elseif($column->type === 'number')
                                                <input
                                                    type="number"
                                                    @if($columnKey === 'hours') min="0" max="999999.99" step="0.25" @else step="any" @endif
                                                    :name="'rows[' + index + '][{{ $inputName }}]'"
                                                    x-model="{{ $model }}"
                                                    class="block min-h-16 w-full border-0 bg-transparent px-3 py-2 text-right text-xs tabular-nums focus:bg-cyan-50 focus:ring-2 focus:ring-inset focus:ring-cyan-500"
                                                    placeholder="0,00"
                                                >
                                            @else
                                                <input
                                                    type="text"
                                                    :name="'rows[' + index + '][{{ $inputName }}]'"
                                                    x-model="{{ $model }}"
                                                    @if($loop->first) data-first-cell @endif
                                                    class="block min-h-16 w-full border-0 bg-transparent px-3 py-2 text-xs focus:bg-cyan-50 focus:ring-2 focus:ring-inset focus:ring-cyan-500"
                                                    placeholder="Digite aqui"
                                                >
                                            @endif
                                        </td>
                                    @endforeach

                                    <td
                                        :style="addingColumn ? 'min-width:220px;width:220px' : 'min-width:48px;width:48px'"
                                        :class="addingColumn ? 'bg-indigo-50/50' : 'bg-slate-100 group-hover:bg-cyan-50'"
                                        class="sticky right-0 z-10 border-b border-slate-300 p-2 text-center transition-[width,min-width]"
                                    >
                                        <button
                                            x-show="!addingColumn"
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
                                <td
                                    :style="addingColumn ? 'min-width:220px;width:220px' : 'min-width:48px;width:48px'"
                                    :class="addingColumn ? 'bg-indigo-50/50' : 'bg-slate-100'"
                                    class="sticky right-0 z-10 border-b border-slate-300 transition-[width,min-width]"
                                ></td>
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
