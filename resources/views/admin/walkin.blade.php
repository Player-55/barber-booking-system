@extends('admin.layout')
@section('title', 'Add Walk-in')

@section('content')
<div class="max-w-3xl mx-auto glass-card rounded-3xl p-6 sm:p-8">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-lg font-bold uppercase tracking-wide">Add Walk-in / Phone Booking</h2>
            <p class="text-xs text-zinc-400 mt-1">For customers who come to the shop or call. Uses the same slots as online booking, so nothing can double-book.</p>
        </div>
        <a href="{{ route('admin.dashboard', ['date' => $date]) }}" class="text-xs text-zinc-400 hover:text-amber-400 whitespace-nowrap">&larr; Back</a>
    </div>

    <form id="walkin-form" action="{{ route('admin.walkin.store') }}" method="POST" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Customer name (optional)</label>
                <input name="customer_name" value="{{ old('customer_name') }}" maxlength="255" placeholder="Walk-in Customer" class="field">
            </div>
            <div>
                <label class="label">Phone (optional)</label>
                <input name="customer_phone" type="tel" inputmode="numeric" value="{{ old('customer_phone') }}" placeholder="0123456789" class="field">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="label">Service</label>
                <select name="service_id" required class="field">
                    <option value="">-- Choose service --</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                            {{ $service->name }} (RM {{ number_format($service->price, 2) }} · {{ $service->duration_minutes }} min)
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Date</label>
                <input type="date" name="booking_date" value="{{ old('booking_date', $date) }}" min="{{ today()->toDateString() }}" required class="field">
            </div>
        </div>

        <div>
            <label class="label">Barber</label>
            <select name="barber_id" class="field">
                <option value="">Any free barber (assigned automatically)</option>
                @foreach($barbers as $barber)
                    <option value="{{ $barber->id }}" {{ old('barber_id') == $barber->id ? 'selected' : '' }}>{{ $barber->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="label !mb-0">Time</label>
                <button type="button" id="start-now" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-[11px] font-bold hover:bg-emerald-500">Start Now</button>
            </div>
            <p id="slot-message" class="text-xs text-zinc-400 py-4 text-center rounded-xl border border-dashed border-zinc-800">Choose a service to see free times.</p>
            <div id="slot-grid" class="slot-scroll grid grid-cols-3 sm:grid-cols-5 gap-2 max-h-60 overflow-y-auto pr-1"></div>
            <input type="hidden" name="booking_time" id="booking-time" value="{{ old('booking_time') }}">
            <p id="chosen" class="hidden mt-3 text-xs text-amber-300 font-semibold"></p>
        </div>

        <div>
            <label class="label">Status</label>
            <div class="flex flex-wrap gap-3 text-xs">
                <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-zinc-800 bg-zinc-950/60 cursor-pointer">
                    <input type="radio" name="status" value="confirmed" {{ old('status', 'confirmed') === 'confirmed' ? 'checked' : '' }} class="accent-amber-500"> Confirmed (waiting / upcoming)
                </label>
                <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-zinc-800 bg-zinc-950/60 cursor-pointer">
                    <input type="radio" name="status" value="completed" {{ old('status') === 'completed' ? 'checked' : '' }} class="accent-emerald-500"> Completed (already done &amp; paid)
                </label>
            </div>
        </div>

        <button class="w-full py-3.5 bg-amber-500 text-zinc-950 font-extrabold uppercase rounded-2xl text-xs tracking-wider hover:bg-amber-400">Add Booking</button>
    </form>
</div>

<style>
    .slot-btn { padding:.55rem 0; border-radius:.7rem; font-size:.72rem; font-weight:700; font-family:'JetBrains Mono',monospace;
                border:1px solid rgba(245,158,11,.35); background:rgba(245,158,11,.06); color:#fcd34d; }
    .slot-btn:not(:disabled):hover { background:rgba(245,158,11,.18); }
    .slot-btn:disabled { border-color:transparent; background:#27272a; color:#52525b; text-decoration:line-through; cursor:not-allowed; }
    .slot-btn[aria-pressed="true"] { background:#f59e0b; border-color:#f59e0b; color:#09090b; }
    .slot-scroll { scrollbar-width:thin; scrollbar-color:rgba(245,158,11,.5) transparent; }
    .slot-scroll::-webkit-scrollbar { width:6px; }
    .slot-scroll::-webkit-scrollbar-thumb { background:rgba(245,158,11,.45); border-radius:999px; }
</style>
<script>
(function () {
    const form    = document.getElementById('walkin-form');
    const service = form.elements['service_id'];
    const barber  = form.elements['barber_id'];
    const date    = form.elements['booking_date'];
    const time    = document.getElementById('booking-time');
    const grid    = document.getElementById('slot-grid');
    const msg     = document.getElementById('slot-message');
    const chosen  = document.getElementById('chosen');

    const nice = v => { const [h, m] = v.split(':').map(Number); return ((h + 11) % 12 + 1) + ':' + String(m).padStart(2, '0') + (h < 12 ? ' AM' : ' PM'); };
    function showChosen() {
        if (time.value) { chosen.textContent = 'Selected time: ' + nice(time.value); chosen.classList.remove('hidden'); }
        else chosen.classList.add('hidden');
    }
    function say(t) { msg.textContent = t; msg.classList.remove('hidden'); }

    let requestNo = 0;
    async function load() {
        const myRequest = ++requestNo;
        grid.innerHTML = '';
        if (!service.value) return say('Choose a service to see free times.');
        if (!date.value)    return say('Choose a date.');
        say('Loading...');
        const params = new URLSearchParams({ service_id: service.value, date: date.value });
        if (barber.value) params.set('barber_id', barber.value);
        try {
            const res  = await fetch('{{ route('admin.walkin.slots') }}?' + params, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (myRequest !== requestNo) return;
            grid.innerHTML = '';
            if (!res.ok || !data.slots || !data.slots.length) return say(data.message || 'No times available.');
            if (data.message) say(data.message); else msg.classList.add('hidden');
            data.slots.forEach(s => {
                const b = document.createElement('button');
                b.type = 'button'; b.className = 'slot-btn'; b.textContent = s.label; b.disabled = !s.available;
                b.setAttribute('aria-pressed', s.value === time.value ? 'true' : 'false');
                b.onclick = () => { grid.querySelectorAll('.slot-btn').forEach(x => x.setAttribute('aria-pressed', 'false')); b.setAttribute('aria-pressed', 'true'); time.value = s.value; showChosen(); };
                grid.appendChild(b);
            });
        } catch (e) { say('Could not load times.'); }
    }

    // "Start Now": today, current Malaysia time rounded down to 5 minutes
    document.getElementById('start-now').onclick = () => {
        const now = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Kuala_Lumpur' }));
        const m = Math.floor(now.getMinutes() / 5) * 5;
        date.value = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
        time.value = String(now.getHours()).padStart(2, '0') + ':' + String(m).padStart(2, '0');
        grid.querySelectorAll('.slot-btn').forEach(x => x.setAttribute('aria-pressed', 'false'));
        showChosen();
        chosen.textContent += ' (now)';
    };

    [service, barber, date].forEach(el => el.addEventListener('change', () => { time.value = ''; showChosen(); load(); }));
    form.addEventListener('submit', e => { if (!time.value) { e.preventDefault(); say('Please choose a time, or press Start Now.'); } });
    showChosen(); load();
})();
</script>
@endsection
