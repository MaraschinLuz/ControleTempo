@props(['active' => false, 'icon'])

<a
    {{ $attributes->class([
        'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold transition duration-200',
        'bg-brand-500 text-white shadow-lg shadow-brand-950/20' => $active,
        'text-slate-300 hover:bg-white/[0.08] hover:text-white' => ! $active,
    ]) }}
    @if($active) aria-current="page" @endif
>
    <span @class([
        'grid h-8 w-8 shrink-0 place-items-center rounded-lg transition',
        'bg-white/10' => $active,
        'bg-white/[0.06] text-slate-400 group-hover:bg-white/10 group-hover:text-brand-300' => ! $active,
    ])>
        <x-icon :name="$icon" class="h-[18px] w-[18px]" />
    </span>
    <span class="min-w-0 flex-1 truncate">{{ $slot }}</span>
    @if($active)
        <span class="h-1.5 w-1.5 rounded-full bg-white/80" aria-hidden="true"></span>
    @endif
</a>
