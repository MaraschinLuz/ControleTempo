<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-sm font-medium text-cyan-700">{{ now()->translatedFormat('l, d \d\e F') }}</p><h1 class="text-2xl font-bold">Olá, {{ str(auth()->user()->name)->before(' ') }}</h1></div>
        @if(auth()->user()->isManagerOrAdmin())<form method="GET" class="flex flex-wrap items-end gap-2"><label class="text-xs font-semibold">Visualizar usuário<select name="user_id" class="mt-1 block rounded-lg border-slate-300 text-sm"><option value="">Toda a equipe</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('user_id')==$user->id)>{{ $user->name }}</option>@endforeach</select></label><label class="text-xs font-semibold">De<input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block rounded-lg border-slate-300 text-sm"></label><label class="text-xs font-semibold">Até<input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block rounded-lg border-slate-300 text-sm"></label><button class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Aplicar</button></form>@endif</div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([['Hoje',$todaySeconds,'text-cyan-700'],['Nesta semana',$weekSeconds,'text-indigo-700'],['Neste mês',$monthSeconds,'text-emerald-700'],['Pendentes',$pendingSeconds,'text-amber-700']] as [$label,$seconds,$color])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-black {{ $color }}"><x-duration :seconds="$seconds" /></p></div>
        @endforeach
    </div>

    @if(auth()->user()->isManagerOrAdmin())
        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <h2 class="font-bold">Indicadores por usuário</h2>
                <p class="mt-1 text-sm text-slate-500">Horas contabilizadas dos usuários ativos e lançamentos pendentes de aprovação.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="p-4">Usuário</th>
                            <th class="p-4">Hoje</th>
                            <th class="p-4">Nesta semana</th>
                            <th class="p-4">Neste mês</th>
                            <th class="p-4">Pendentes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userIndicators as $indicator)
                            <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                                <td class="p-4">
                                    <a href="{{ route('dashboard', ['user_id' => $indicator->id]) }}" class="font-semibold text-slate-900 hover:text-cyan-700">{{ $indicator->name }}</a>
                                    <span class="block text-xs text-slate-500">{{ $indicator->role->label() }}</span>
                                </td>
                                <td class="p-4 font-bold text-cyan-700"><x-duration :seconds="$indicator->today_seconds ?? 0" /></td>
                                <td class="p-4 font-bold text-indigo-700"><x-duration :seconds="$indicator->week_seconds ?? 0" /></td>
                                <td class="p-4 font-bold text-emerald-700"><x-duration :seconds="$indicator->month_seconds ?? 0" /></td>
                                <td class="p-4 font-bold text-amber-700"><x-duration :seconds="$indicator->pending_seconds ?? 0" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-slate-500">Nenhum usuário ativo encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="mt-6 rounded-2xl bg-slate-950 p-5 text-white shadow-xl sm:p-6">
        @if($runningEntry)
            <div class="flex flex-col justify-between gap-5 md:flex-row md:items-center" x-data="{elapsed: {{ now()->timestamp - $runningEntry->started_at->timestamp }}, timer:null, formatted(){let h=Math.floor(this.elapsed/3600),m=Math.floor((this.elapsed%3600)/60),s=this.elapsed%60;return [h,m,s].map(v=>String(v).padStart(2,'0')).join(':')}}" x-init="timer=setInterval(()=>elapsed++,1000)">
                <div><span class="rounded-full bg-cyan-400 px-3 py-1 text-xs font-bold text-slate-950">EM ANDAMENTO</span><h2 class="mt-3 text-xl font-bold">{{ $runningEntry->project->client->name }} · {{ $runningEntry->project->name }}</h2><p class="mt-1 text-slate-400">{{ $runningEntry->activity->name }} @if($runningEntry->description) — {{ $runningEntry->description }} @endif</p></div>
                <div class="text-left md:text-center"><p class="font-mono text-4xl font-bold tracking-tight text-cyan-300" x-text="formatted()"></p><p class="mt-1 text-xs text-slate-400">Iniciado às {{ $runningEntry->started_at->format('H:i') }}</p></div>
                <div class="flex gap-2"><form method="POST" action="{{ route('timer.stop',$runningEntry) }}">@csrf @method('PATCH')<button class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950">Finalizar trabalho</button></form><form method="POST" action="{{ route('timer.cancel',$runningEntry) }}" onsubmit="return confirm('Cancelar este registro?')">@csrf @method('PATCH')<button class="rounded-xl border border-white/20 px-4 py-3">Cancelar</button></form></div>
            </div>
        @else
            <div x-data="{client:'', projects: @js($projects->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'client_id'=>$p->client_id])->values())}">
                <h2 class="text-lg font-bold">Iniciar trabalho</h2><p class="mt-1 text-sm text-slate-400">Escolha o contexto da atividade. O horário será salvo imediatamente.</p>
                <form action="{{ route('timer.start') }}" method="POST" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">@csrf
                    <select x-model="client" required class="rounded-xl border-slate-700 bg-slate-900 text-sm"><option value="">Cliente</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select>
                    <select name="project_id" required class="rounded-xl border-slate-700 bg-slate-900 text-sm"><option value="">Projeto</option><template x-for="p in projects.filter(p=>String(p.client_id)===String(client))" :key="p.id"><option :value="p.id" x-text="p.name"></option></template></select>
                    <select name="activity_id" required class="rounded-xl border-slate-700 bg-slate-900 text-sm"><option value="">Atividade</option>@foreach($activities as $activity)<option value="{{ $activity->id }}">{{ $activity->name }}</option>@endforeach</select>
                    <input name="description" maxlength="2000" placeholder="Descrição (opcional)" class="rounded-xl border-slate-700 bg-slate-900 text-sm">
                    <button class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950">Iniciar trabalho</button>
                </form>
            </div>
        @endif
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        @foreach([['Horas por projeto',$projectChart],['Horas por cliente',$clientChart],['Por atividade',$activityChart]] as [$title,$data])
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-bold">{{ $title }}</h3><div class="mt-4 h-64"><canvas x-data x-init="$nextTick(()=>new Chart($el,{type:'doughnut',data:{labels:@js($data->keys()),datasets:[{data:@js($data->values()),backgroundColor:['#22d3ee','#818cf8','#34d399','#fbbf24','#fb7185','#a78bfa']}]},options:{maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}}))"></canvas></div></section>
        @endforeach
    </div>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 p-5"><h3 class="font-bold">Registros recentes</h3><a href="{{ route('time-entries.index') }}" class="text-sm font-semibold text-cyan-700">Ver todos</a></div><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="p-4">Data</th><th class="p-4">Projeto</th><th class="p-4">Atividade</th><th class="p-4">Duração</th><th class="p-4">Status</th></tr></thead><tbody>@forelse($recentEntries as $entry)<tr class="border-t border-slate-100"><td class="p-4">{{ $entry->started_at->format('d/m/Y H:i') }}</td><td class="p-4"><strong>{{ $entry->project->name }}</strong><span class="block text-xs text-slate-500">{{ $entry->user->name }}</span></td><td class="p-4">{{ $entry->activity->name }}</td><td class="p-4 font-semibold"><x-duration :seconds="$entry->duration_seconds" /></td><td class="p-4"><x-status-badge :status="$entry->status" /></td></tr>@empty<tr><td colspan="5" class="p-8 text-center text-slate-500">Nenhum registro encontrado.</td></tr>@endforelse</tbody></table></div></section>
</x-app-layout>
