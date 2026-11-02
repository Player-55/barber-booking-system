@extends('admin.layout')
@section('title', 'Barber Commission')

@php
    $rateText = rtrim(rtrim(number_format($rate, 2), '0'), '.');
    $ranges = [
        'Today'      => [today(), today()],
        'This Week'  => [today()->startOfWeek(\Carbon\Carbon::MONDAY), today()->endOfWeek(\Carbon\Carbon::SUNDAY)],
        'This Month' => [today()->startOfMonth(), today()->endOfMonth()],
        'Last Month' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
    ];
@endphp

@section('content')

    <!-- Period + rate -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 glass-card rounded-3xl p-6">
            <h2 class="text-base font-bold uppercase tracking-wide mb-4">Period</h2>
            <form method="GET" action="{{ route('admin.commission.index') }}" class="grid grid-cols-2 sm:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="label">From</label>
                    <input type="date" name="from" value="{{ $from->toDateString() }}" class="field">
                </div>
                <div>
                    <label class="label">To</label>
                    <input type="date" name="to" value="{{ $to->toDateString() }}" class="field">
                </div>
                <button class="col-span-2 sm:col-span-1 py-2.5 bg-amber-500 text-zinc-950 font-bold uppercase rounded-xl text-xs hover:bg-amber-400">Show</button>
            </form>
            <div class="flex flex-wrap gap-2 mt-4 text-xs">
                @foreach($ranges as $label => [$f, $t])
                    @php $active = $f->toDateString() === $from->toDateString() && $t->toDateString() === $to->toDateString(); @endphp
                    <a href="{{ route('admin.commission.index', ['from' => $f->toDateString(), 'to' => $t->toDateString()]) }}"
                       class="px-3 py-1.5 rounded-xl border {{ $active ? 'bg-amber-500 text-zinc-950 font-bold border-amber-500' : 'bg-zinc-900/80 text-zinc-400 border-zinc-800 hover:text-amber-400' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="glass-card rounded-3xl p-6">
            <h2 class="text-base font-bold uppercase tracking-wide mb-1">Commission Rate</h2>
            <p class="text-[11px] text-zinc-400 mb-4">Paid on completed bookings only.</p>
            <form method="POST" action="{{ route('admin.commission.rate') }}" class="flex gap-2 items-end">
                @csrf @method('PUT')
                <div class="flex-1">
                    <label class="label">Rate (%)</label>
                    <input type="number" name="rate" step="0.5" min="0" max="100" value="{{ $rateText }}" required class="field">
                </div>
                <button class="px-4 py-2.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold rounded-xl text-xs hover:bg-amber-500/30">Save</button>
            </form>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-5">
            <p class="text-[10px] text-zinc-400 uppercase font-semibold">Completed Bookings</p>
            <p class="text-2xl font-extrabold font-mono text-amber-400 mt-1">{{ $totals->completed }}</p>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <p class="text-[10px] text-zinc-400 uppercase font-semibold">Total Sales</p>
            <p class="text-2xl font-extrabold font-mono text-zinc-100 mt-1">RM {{ number_format($totals->sales, 2) }}</p>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <p class="text-[10px] text-zinc-400 uppercase font-semibold">Commission ({{ $rateText }}%)</p>
            <p class="text-2xl font-extrabold font-mono text-amber-400 mt-1">RM {{ number_format($totals->commission, 2) }}</p>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <p class="text-[10px] text-zinc-400 uppercase font-semibold">Shop Keeps</p>
            <p class="text-2xl font-extrabold font-mono text-emerald-400 mt-1">RM {{ number_format($totals->shop, 2) }}</p>
        </div>
    </div>

    <!-- Per barber -->
    <div class="glass-card rounded-3xl p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
            <h2 class="text-base font-bold uppercase tracking-wide">Commission per Barber</h2>
            <p class="text-xs text-zinc-400">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-300">
                <thead class="text-[11px] uppercase bg-zinc-950/80 text-zinc-400 border-b border-zinc-800">
                    <tr>
                        <th class="px-4 py-3">Barber</th>
                        <th class="px-4 py-3 text-right">Completed</th>
                        <th class="px-4 py-3 text-right">Sales</th>
                        <th class="px-4 py-3 text-right">Commission ({{ $rateText }}%)</th>
                        <th class="px-4 py-3 text-right">Shop Keeps</th>
                        <th class="px-4 py-3 text-right">Upcoming</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60 font-mono">
                    @forelse($rows as $row)
                        <tr class="hover:bg-zinc-900/40">
                            <td class="px-4 py-3 font-sans font-bold text-zinc-100">
                                {{ $row->name }}
                                @unless($row->is_active)<span class="text-[10px] text-zinc-500 font-normal">(hidden)</span>@endunless
                            </td>
                            <td class="px-4 py-3 text-right">{{ $row->completed }}</td>
                            <td class="px-4 py-3 text-right">RM {{ number_format($row->sales, 2) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-amber-400">RM {{ number_format($row->commission, 2) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-400">RM {{ number_format($row->shop, 2) }}</td>
                            <td class="px-4 py-3 text-right text-zinc-400" title="Pending + confirmed bookings (not paid yet)">
                                {{ $row->upcoming }} <span class="text-[10px]">(RM {{ number_format($row->upcoming_value, 2) }})</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500 font-sans">No barbers yet.</td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                    <tfoot class="border-t-2 border-amber-500/30 font-mono font-bold">
                        <tr>
                            <td class="px-4 py-3 font-sans uppercase text-zinc-100">Total</td>
                            <td class="px-4 py-3 text-right">{{ $totals->completed }}</td>
                            <td class="px-4 py-3 text-right">RM {{ number_format($totals->sales, 2) }}</td>
                            <td class="px-4 py-3 text-right text-amber-400">RM {{ number_format($totals->commission, 2) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-400">RM {{ number_format($totals->shop, 2) }}</td>
                            <td class="px-4 py-3 text-right text-zinc-400">{{ $totals->upcoming }} <span class="text-[10px]">(RM {{ number_format($totals->upcoming_value, 2) }})</span></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        <p class="text-[11px] text-zinc-500 mt-4">Commission = completed sales × {{ $rateText }}%. "Upcoming" shows pending + confirmed bookings in this period, which are not counted until they are marked Completed.</p>
    </div>

@endsection
