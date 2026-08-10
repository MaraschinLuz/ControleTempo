<x-app-layout>
    <x-slot name="header"><div class="flex justify-between gap-4"><h1 class="text-2xl font-bold">Atividades</h1><a href="{{ route('activities.create') }}" class="rounded-xl bg-brand-500 px-4 py-2 font-bold text-white">Nova atividade</a></div></x-slot>
    <div class="overflow-hidden rounded-2xl border bg-white">
        <table class="min-w-full text-sm"><thead class="bg-slate-50 text-left"><tr><th class="p-4">Nome</th><th class="p-4">Status</th><th class="p-4">Ações</th></tr></thead><tbody>
            @foreach($items as $item)<tr class="border-t"><td class="p-4"><strong>{{ $item->name }}</strong><span class="block text-xs text-slate-500">{{ $item->description }}</span></td><td class="p-4">{{ $item->active ? 'Ativa' : 'Inativa' }}</td><td class="p-4"><div class="flex items-center gap-3"><a class="font-semibold text-brand-700" href="{{ route('activities.edit', $item) }}">Editar</a><form method="POST" action="{{ route('activities.destroy', $item) }}" data-confirm="A atividade será excluída. Os registros de horas serão preservados." data-confirm-title="Excluir atividade?" data-confirm-button="Excluir" data-confirm-variant="danger">@csrf @method('DELETE')<button class="font-semibold text-red-700">Excluir</button></form></div></td></tr>@endforeach
        </tbody></table><div class="p-4">{{ $items->links() }}</div>
    </div>
</x-app-layout>
