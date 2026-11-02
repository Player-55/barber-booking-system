@extends('admin.layout')
@section('title', 'Admin Dashboard')

@php
    $isToday   = $selected->isToday();
    $dateParam = $selected->toDateString();

    // Calendar grid: Monday-first weeks covering the whole month
    $gridStart = $month->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $gridEnd   = $month->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);

    $filters = [null => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
@endphp

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

    {{-- ================= LEFT PANEL (2/5) ================= --}}
    <div class="lg:col-span-2 space-y-6 lg:sticky lg:top-32">

        <!-- Live clock & date -->
        <div class="glass-card rounded-3xl p-6 text-center">
            <p id="live-clock" class="text-4xl sm:text-5xl font-extrabold font-mono text-amber-400 tracking-wider">--:--:--</p>
            <p id="live-date" class="text-sm text-zinc-300 mt-2 font-semibold">&nbsp;</p>
            <p class="text-[10px] text-zinc-500 mt-1 uppercase tracking-widest">Shah Alam, Malaysia</p>
        </div>

        <!-- Totals -->
        <div class="grid grid-cols-3 lg:grid-cols-1 xl:grid-cols-3 gap-3">
            <div class="glass-card rounded-2xl p-4">
                <p class="text-[10px] text-zinc-400 uppercase font-semibold">Total Bookings</p>
                <p class="text-xl font-extrabold font-mono text-amber-400 mt-1">{{ $totalBookings }}</p>
            </div>
            <div class="glass-card rounded-2xl p-4">
                <p class="text-[10px] text-zinc-400 uppercase font-semibold">Pending Requests</p>
                <p class="text-xl font-extrabold font-mono text-amber-400 mt-1">{{ $pendingBookings }}</p>
            </div>
            <div class="glass-card rounded-2xl p-4">
                <p class="text-[10px] text-zinc-400 uppercase font-semibold">Completed Revenue</p>
                <p class="text-lg font-extrabold font-mono text-emerald-400 mt-1 break-words">RM {{ number_format($totalRevenue, 2) }}</p>
            </div>
        </div>

        <!-- Booking calendar -->
        <div class="glass-card rounded-3xl p-5">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('admin.dashboard', ['date' => $dateParam, 'month' => $month->copy()->subMonth()->format('Y-m'), 'status' => $status]) }}"
                   class="w-8 h-8 flex items-center justify-center rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300 hover:text-amber-400" title="Previous month">&lsaquo;</a>
                <p class="text-sm font-bold uppercase tracking-wide">{{ $month->format('F Y') }}</p>
                <a href="{{ route('admin.dashboard', ['date' => $dateParam, 'month' => $month->copy()->addMonth()->format('Y-m'), 'status' => $status]) }}"
                   class="w-8 h-8 flex items-center justify-center rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300 hover:text-amber-400" title="Next month">&rsaquo;</a>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-[10px] text-zinc-500 font-bold uppercase mb-1">
                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
            </div>

            <div class="grid grid-cols-7 gap-1">
                @for($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay())
                    @php
                        $key        = $day->toDateString();
                        $inMonth    = $day->month === $month->month;
                        $isSelected = $key === $dateParam;
                        $count      = $counts[$key] ?? 0;
                    @endphp
                    <a href="{{ route('admin.dashboard', ['date' => $key, 'status' => $status]) }}"
                       class="relative aspect-square flex flex-col items-center justify-center rounded-xl text-xs transition-colors
                              {{ $isSelected ? 'bg-amber-500 text-zinc-950 font-extrabold' : ($inMonth ? 'bg-zinc-950/60 text-zinc-200 hover:bg-zinc-800' : 'text-zinc-700 hover:bg-zinc-900') }}
                              {{ $day->isToday() && ! $isSelected ? 'ring-1 ring-amber-500' : '' }}">
                        <span>{{ $day->day }}</span>
                        @if($count > 0 && $inMonth)
                            <span class="mt-0.5 text-[9px] leading-none font-bold {{ $isSelected ? 'text-zinc-900' : 'text-amber-400' }}">{{ $count }}</span>
                        @endif
                    </a>
                @endfor
            </div>

            <div class="flex items-center justify-between mt-4 text-[10px] text-zinc-500">
                <span><span class="text-amber-400 font-bold">2</span> = bookings that day</span>
                <a href="{{ route('admin.dashboard') }}" class="px-3 py-1 rounded-lg border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 font-semibold">Go to Today</a>
            </div>
        </div>
    </div>

    {{-- ================= RIGHT PANEL (3/5) - Appointment records ================= --}}
    <div class="lg:col-span-3 glass-card rounded-3xl p-6 shadow-2xl">

        <!-- Header: title | TODAY (middle) | count -->
        <div class="grid grid-cols-3 items-center gap-2 pb-4 border-b border-zinc-800">
            <div>
                <h2 class="text-sm sm:text-base font-bold text-zinc-100 uppercase tracking-wide">Appointment Records</h2>
                <p class="text-[11px] text-zinc-400 mt-0.5">{{ $selected->format('l, d M Y') }}</p>
            </div>
            <div class="text-center">
                @if($isToday)
                    <span class="inline-block px-4 py-1.5 rounded-full bg-amber-500 text-zinc-950 text-xs font-extrabold tracking-[0.2em] shadow-lg shadow-amber-500/20">TODAY</span>
                @endif
            </div>
            <div class="text-right">
                <p class="text-2xl font-extrabold font-mono text-amber-400 leading-none">{{ $dayBookings->where('status', '!=', 'cancelled')->count() }}</p>
                <p class="text-[10px] text-zinc-500 uppercase">active bookings</p>
            </div>
        </div>

        <!-- Status filter (keeps the chosen date) + walk-in button -->
        <div class="flex flex-wrap items-center gap-2 text-xs py-4">
            @foreach($filters as $value => $label)
                @php $active = ($status ?? null) === ($value ?: null); @endphp
                <a href="{{ route('admin.dashboard', ['date' => $dateParam, 'status' => $value ?: null]) }}"
                   class="px-3 py-1.5 rounded-xl border {{ $active ? 'bg-amber-500 text-zinc-950 font-bold border-amber-500' : 'bg-zinc-900/80 text-zinc-400 border-zinc-800 hover:text-amber-400' }}">
                    {{ $label }}
                    <span class="opacity-70">({{ $value ? $dayBookings->where('status', $value)->count() : $dayBookings->count() }})</span>
                </a>
            @endforeach
            @unless($selected->lt(today()))
                <a href="{{ route('admin.walkin.create', ['date' => $dateParam]) }}"
                   class="ml-auto px-3 py-1.5 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-500 shadow-md">+ Walk-in</a>
            @endunless
        </div>

        <!-- Bookings of the chosen day, earliest first -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-300">
                <thead class="text-[11px] uppercase bg-zinc-950/80 text-zinc-400 border-b border-zinc-800">
                    <tr>
                        <th class="px-3 py-3">No.</th>
                        <th class="px-3 py-3">Time</th>
                        <th class="px-3 py-3">Customer</th>
                        <th class="px-3 py-3">Service & Barber</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60">
                    @forelse($bookings as $booking)
                        <tr class="hover:bg-zinc-900/40 transition-colors {{ $booking->status === 'cancelled' ? 'opacity-50' : '' }}">
                            <td class="px-3 py-3 font-mono font-extrabold text-amber-400 text-sm" title="Booking code: {{ $booking->booking_code }}">
                                #{{ $booking->queue_no }}
                            </td>
                            <td class="px-3 py-3 font-mono whitespace-nowrap">
                                <p class="font-bold text-zinc-100">{{ \Carbon\Carbon::parse($booking->booking_time)->format('h:i A') }}</p>
                                @if($booking->end_time)
                                    <p class="text-[10px] text-zinc-500">to {{ \Carbon\Carbon::parse($booking->end_time)->format('h:i A') }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <p class="font-bold text-zinc-100">{{ $booking->customer_name }}</p>
                                @if($booking->customer_phone)
                                    <a href="https://wa.me/6{{ ltrim($booking->customer_phone, '6') }}" target="_blank" rel="noopener"
                                       class="text-[10px] text-zinc-500 font-mono hover:text-emerald-400">{{ $booking->customer_phone }}</a>
                                @endif
                                @if(str_starts_with($booking->booking_code, 'WI-'))
                                    <span class="inline-block text-[9px] font-bold uppercase px-1.5 rounded bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">Walk-in</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <p class="text-zinc-200">{{ $booking->service->name ?? 'N/A' }}</p>
                                <p class="text-[10px] text-amber-400/80">Barber: {{ $booking->barber->name ?? 'N/A' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                @if($booking->status == 'pending')
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 uppercase text-[10px] font-bold">Pending</span>
                                @elseif($booking->status == 'confirmed')
                                    <span class="px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 uppercase text-[10px] font-bold">Confirmed</span>
                                @elseif($booking->status == 'completed')
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase text-[10px] font-bold">Completed</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 uppercase text-[10px] font-bold">Cancelled</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right">
                                <form action="{{ route('admin.bookings.updateStatus', $booking->id) }}" method="POST" class="inline-flex gap-1.5 justify-end">
                                    @csrf
                                    @method('PATCH')
                                    @if($booking->status == 'pending')
                                        <button type="submit" name="status" value="confirmed" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[11px] font-bold cursor-pointer transition-all active:scale-95 shadow-md bg-blue-600 text-white hover:bg-blue-500 hover:shadow-blue-500/30">Confirm</button>
                                    @endif
                                    @if($booking->status == 'confirmed')
                                        <button type="submit" name="status" value="completed" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[11px] font-bold cursor-pointer transition-all active:scale-95 shadow-md bg-emerald-600 text-white hover:bg-emerald-500 hover:shadow-emerald-500/30">Complete</button>
                                    @endif
                                    @if($booking->status != 'cancelled')
                                        <button type="submit" name="status" value="cancelled" onclick="return confirm('Cancel booking #{{ $booking->queue_no }} ({{ addslashes($booking->customer_name) }})?')"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[11px] font-bold cursor-pointer transition-all active:scale-95 shadow-md bg-transparent text-red-400 border border-red-500/60 hover:bg-red-600 hover:text-white hover:border-red-600">Cancel</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-zinc-500 text-xs">
                                No bookings on {{ $selected->format('d M Y') }}{{ $status ? ' with status "' . $status . '"' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Live clock (Malaysia time) -->
<script>
    (function () {
        const tz = 'Asia/Kuala_Lumpur';
        const clock = document.getElementById('live-clock');
        const date  = document.getElementById('live-date');
        function tick() {
            const now = new Date();
            clock.textContent = now.toLocaleTimeString('en-GB', { timeZone: tz, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            date.textContent  = now.toLocaleDateString('en-GB', { timeZone: tz, weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
@endsection
