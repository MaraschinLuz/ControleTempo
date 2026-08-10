@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : $status;
    $label = $status instanceof \App\Enums\EntryStatus ? $status->label() : ucfirst($value);
    $class = match($value) { 'running' => 'bg-brand-100 text-brand-800', 'approved', 'completed' => 'bg-emerald-100 text-emerald-800', 'pending' => 'bg-amber-100 text-amber-800', 'rejected', 'cancelled' => 'bg-red-100 text-red-800', default => 'bg-canvas text-slate-700' };
@endphp
<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $class }}">{{ $label }}</span>
