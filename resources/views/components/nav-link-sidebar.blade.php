@props(['active' => false])
<a {{ $attributes->class(['flex items-center rounded-xl px-3 py-2.5 font-medium transition', 'bg-cyan-400 text-slate-950' => $active, 'text-slate-300 hover:bg-white/10 hover:text-white' => ! $active]) }}>{{ $slot }}</a>
