@if(session('success'))
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3.5 text-sm text-emerald-900 shadow-sm" role="status">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700">
            <x-icon name="check-circle" class="h-5 w-5" />
        </span>
        <div class="pt-1.5 font-semibold">{{ session('success') }}</div>
    </div>
@endif

@if($errors->any())
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 px-4 py-3.5 text-sm text-rose-900 shadow-sm" role="alert">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-700">
            <x-icon name="alert-circle" class="h-5 w-5" />
        </span>
        <div>
            <strong class="block pt-1.5">Revise os dados informados:</strong>
            <ul class="mt-1.5 list-disc space-y-0.5 pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
@endif
