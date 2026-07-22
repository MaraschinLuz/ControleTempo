@props(['seconds' => 0])
@php $h=intdiv((int)$seconds,3600); $m=intdiv(((int)$seconds)%3600,60); @endphp
<span>{{ $h ? $h.'h' : '' }}{{ $m ? $m.'min' : ($h ? '' : '0min') }}</span>
