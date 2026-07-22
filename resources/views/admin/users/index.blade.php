<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between gap-4">
            <h1 class="text-2xl font-bold">Usuários</h1>
            <a href="{{ route('users.create') }}" class="rounded-xl bg-cyan-500 px-4 py-2 font-bold">Novo usuário</a>
        </div>
    </x-slot>

    <div class="overflow-x-auto rounded-2xl border bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left"><tr><th class="p-4">Nome</th><th class="p-4">E-mail</th><th class="p-4">Perfil</th><th class="p-4">Status</th><th class="p-4">Ações</th></tr></thead>
            <tbody>
                @foreach($items as $item)
                    <tr class="border-t">
                        <td class="p-4 font-semibold">{{ $item->name }}</td><td class="p-4">{{ $item->email }}</td><td class="p-4">{{ $item->role->label() }}</td><td class="p-4">{{ $item->active ? 'Ativo' : 'Inativo' }}</td>
                        <td class="p-4"><div class="flex items-center gap-3">
                            <a class="font-semibold text-cyan-700" href="{{ route('users.edit', $item) }}">Editar</a>
                            @if($item->isNot(auth()->user()))
                                <form method="POST" action="{{ route('users.destroy', $item) }}" onsubmit="return confirm('Excluir este usuário? Os registros de horas serão preservados.')">@csrf @method('DELETE')<button class="font-semibold text-red-700">Excluir</button></form>
                            @endif
                        </div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $items->links() }}</div>
    </div>
</x-app-layout>
