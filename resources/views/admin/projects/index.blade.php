<x-app-layout>
    <x-slot name="header"><div class="flex justify-between gap-4"><h1 class="text-2xl font-bold">Projetos</h1><a href="{{ route('projects.create') }}" class="rounded-xl bg-cyan-500 px-4 py-2 font-bold">Novo projeto</a></div></x-slot>
    <div class="overflow-x-auto rounded-2xl border bg-white">
        <table class="min-w-full text-sm"><thead class="bg-slate-50 text-left"><tr><th class="p-4">Projeto</th><th class="p-4">Cliente</th><th class="p-4">Status</th><th class="p-4">Estimativa</th><th class="p-4">Ações</th></tr></thead><tbody>
            @foreach($items as $item)<tr class="border-t"><td class="p-4 font-semibold">{{ $item->name }}</td><td class="p-4">{{ $item->client->name }}</td><td class="p-4">{{ $item->status->label() }}</td><td class="p-4">{{ $item->estimated_hours ? $item->estimated_hours.'h' : '—' }}</td><td class="p-4"><div class="flex items-center gap-3"><a class="font-semibold text-cyan-700" href="{{ route('projects.edit', $item) }}">Editar</a><form method="POST" action="{{ route('projects.destroy', $item) }}" data-confirm="O projeto será excluído. Os registros de horas serão preservados." data-confirm-title="Excluir projeto?" data-confirm-button="Excluir" data-confirm-variant="danger">@csrf @method('DELETE')<button class="font-semibold text-red-700">Excluir</button></form></div></td></tr>@endforeach
        </tbody></table><div class="p-4">{{ $items->links() }}</div>
    </div>
</x-app-layout>
