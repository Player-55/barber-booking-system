<!DOCTYPE html>
<html lang="en" class="h-full bg-[#0B0F19]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Slot - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        /* Golden Dusk Ambient Background Animation */
        .golden-dusk-bg {
            background: radial-gradient(circle at 50% -20%, #2b1f0d 0%, #0d121d 45%, #070a10 100%);
            position: relative;
            overflow-x: hidden;
        }

        .golden-dusk-bg::before {
            content: '';
            position: absolute;
            top: -20%;
            left: 20%;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.15) 0%, rgba(217, 119, 6, 0.05) 50%, rgba(0, 0, 0, 0) 70%);
            filter: blur(80px);
            animation: pulseDusk 8s ease-in-out infinite alternate;
            pointer-events: none;
        }

        @keyframes pulseDusk {
            0% { transform: scale(1) translateY(0); opacity: 0.7; }
            100% { transform: scale(1.2) translateY(30px); opacity: 1; }
        }

        /* Glassmorphism Styling */
        .glass-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(245, 158, 11, 0.15);
        }

        .glass-input {
            background: rgba(7, 10, 16, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s ease;
        }

        .glass-input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.2);
        }
    </style>
</head>
<body class="min-h-full text-zinc-100 golden-dusk-bg antialiased selection:bg-amber-500 selection:text-zinc-950 pb-16">

    <!-- Header Section with Logo -->
    <header class="pt-10 pb-6 text-center relative z-10 max-w-4xl mx-auto px-4">
        <div class="inline-block relative group mb-4">
            <div class="absolute -inset-2 rounded-full bg-gradient-to-r from-amber-500 to-amber-700 opacity-25 blur-lg group-hover:opacity-50 transition duration-500"></div>
            <img src="{{ asset(config('app.shop_logo')) }}" alt="{{ config('app.name') }} logo" class="relative w-28 h-28 sm:w-32 sm:h-32 mx-auto rounded-full object-cover drop-shadow-2xl">
        </div>

        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-wider text-amber-400 uppercase">
            {{ config('app.name') }}
        </h1>
        <p class="text-xs sm:text-sm tracking-widest text-zinc-400 font-medium uppercase mt-1">
            {{ config('app.shop_tagline') }}
        </p>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-10 mt-2">

        <!-- Left Column: Operating Hours, Barbers & Location (5 Cols) -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Opening Hours -->
            <div class="glass-card rounded-3xl p-6 shadow-2xl relative overflow-hidden">
                <div class="flex items-center gap-3 border-b border-zinc-800/80 pb-4 mb-4">
                    <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-zinc-100 tracking-wide uppercase">Opening Hours</h2>
                        <p class="text-xs text-zinc-400">{{ config('app.shop_branch') ?: 'When we are open' }}</p>
                    </div>
                </div>

                @php
                    // If every open day has the same break, show it once as "Daily Break Time"
                    $openDays = $hours->where('is_closed', false);
                    $breaks   = $openDays->map(fn ($h) => $h->break_start ? $h->break_start . '-' . $h->break_end : '')->unique();
                    $sameBreak = $breaks->count() === 1 && $breaks->first() !== '';
                @endphp
                <div class="space-y-2.5 text-xs">
                    @foreach($hours as $h)
                        <div class="flex justify-between items-center py-1 border-b border-zinc-800/40 {{ $h->is_closed ? 'text-red-400 font-semibold' : 'text-zinc-300' }}">
                            <span>{{ $h->dayName() }}</span>
                            @if($h->is_closed)
                                <span class="px-2 py-0.5 rounded bg-red-500/10 text-[10px]">CLOSED</span>
                            @else
                                <span class="text-right">
                                    <span class="font-mono text-amber-400">{{ \App\Models\OpeningHour::niceTime($h->open_time) }} – {{ \App\Models\OpeningHour::niceTime($h->close_time) }}</span>
                                    @if(! $sameBreak && $h->break_start)
                                        <span class="block text-[10px] text-zinc-500">Break {{ \App\Models\OpeningHour::niceTime($h->break_start) }} – {{ \App\Models\OpeningHour::niceTime($h->break_end) }}</span>
                                    @endif
                                </span>
                            @endif
                        </div>
                    @endforeach
                    @if($sameBreak)
                        @php $first = $openDays->first(); @endphp
                        <div class="flex justify-between items-center py-1 text-zinc-400 text-[11px] pt-2">
                            <span>Daily Break Time</span>
                            <span class="font-mono text-zinc-400">{{ \App\Models\OpeningHour::niceTime($first->break_start) }} – {{ \App\Models\OpeningHour::niceTime($first->break_end) }}</span>
                        </div>
                    @endif
                </div>

                <!-- Contact & Social Details -->
                <div class="mt-6 pt-4 border-t border-zinc-800/80 flex items-center justify-between text-xs">
                    <div class="text-zinc-400">
                        @if(config('app.shop_phone'))
                            Contact: <span class="font-mono text-amber-400 font-medium">{{ config('app.shop_phone') }}</span>
                        @else
                            Book online 24/7
                        @endif
                    </div>
                    <div class="flex items-center gap-2 text-zinc-400">
                        {{ config('app.shop_social') }}
                    </div>
                </div>
            </div>

            <!-- Master Barbers Info -->
            <div class="glass-card rounded-3xl p-6 shadow-2xl">
                <div class="flex items-center gap-3 border-b border-zinc-800/80 pb-4 mb-4">
                    <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 0L4 4m5.121 5.121L4 19"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-zinc-100 tracking-wide uppercase">Our Master Barbers</h2>
                        <p class="text-xs text-zinc-400">Experienced in custom fades & styling</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    @foreach($barbers as $barber)
                        <div class="p-3 rounded-2xl bg-zinc-950/60 border border-zinc-800/60 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-amber-500/20 border border-amber-500/30 flex items-center justify-center font-bold text-amber-400 text-sm">{{ strtoupper(mb_substr($barber->name, 0, 1)) }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-zinc-200">{{ $barber->name }}</p>
                                <p class="text-[10px] text-zinc-500">{{ $barber->specialty }}</p>
                            </div>
                            @if($barber->whatsappNumber())
                                <a href="https://wa.me/{{ $barber->whatsappNumber() }}?text={{ urlencode('Hi ' . $barber->name . ', I have a question about booking at ' . config('app.name') . '.') }}"
                                   target="_blank" rel="noopener" title="WhatsApp {{ $barber->name }}"
                                   class="shrink-0 w-8 h-8 rounded-full bg-emerald-600 hover:bg-emerald-500 flex items-center justify-center shadow-md">
                                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3a.5.5 0 0 0 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.3c0-.1-.2-.2-.5-.3Z"/></svg>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @if(config('app.shop_address'))
                <!-- Shop Address -->
                <div class="px-6 py-4 rounded-2xl bg-zinc-950/50 border border-zinc-800/50 text-xs text-zinc-400 flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <div>
                        <span class="font-semibold text-zinc-200 block mb-0.5">Location:</span>
                        {{ config('app.shop_address') }}
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(config('app.shop_address')) }}" target="_blank" rel="noopener"
                           class="block mt-1.5 text-amber-400 hover:underline font-semibold">Open in Google Maps &rarr;</a>
                    </div>
                </div>
            @endif

        </div>

        <!-- Right Column: Booking Form (7 Cols) -->
        <div class="lg:col-span-7">
            <div class="glass-card rounded-3xl p-6 sm:p-8 shadow-2xl relative">
                
                @if(session('booking'))
                    @php $b = session('booking'); @endphp
                    <div class="mb-6 p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30">
                        <p class="text-sm font-extrabold text-emerald-400 uppercase tracking-wide">✔ Booking received!</p>
                        <p class="text-[11px] text-zinc-400 mb-3">Please screenshot this. The shop will confirm your booking.</p>
                        <dl class="grid grid-cols-2 gap-y-1.5 text-xs">
                            <dt class="text-zinc-400">Booking code</dt><dd class="font-mono font-bold text-amber-400">{{ $b['code'] }}</dd>
                            <dt class="text-zinc-400">Name</dt><dd class="text-zinc-100">{{ $b['name'] }}</dd>
                            <dt class="text-zinc-400">Service</dt><dd class="text-zinc-100">{{ $b['service'] }}</dd>
                            <dt class="text-zinc-400">Barber</dt><dd class="text-zinc-100">{{ $b['barber'] }}</dd>
                            <dt class="text-zinc-400">Date</dt><dd class="text-zinc-100">{{ $b['date'] }}</dd>
                            <dt class="text-zinc-400">Time</dt><dd class="text-zinc-100">{{ $b['time'] }}</dd>
                        </dl>
                        @if(! empty($b['barber_whatsapp']))
                            @php
                                $waText = "Hi {$b['barber']}, I just booked at " . config('app.name') . ".\n"
                                    . "Booking code: {$b['code']}\nName: {$b['name']}\nService: {$b['service']}\nDate: {$b['date']}\nTime: {$b['time']}";
                            @endphp
                            <a href="https://wa.me/{{ $b['barber_whatsapp'] }}?text={{ urlencode($waText) }}" target="_blank" rel="noopener"
                               class="mt-4 flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold uppercase tracking-wide shadow-lg">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm4.5 12.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3a.5.5 0 0 0 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.3c0-.1-.2-.2-.5-.3Z"/></svg>
                                WhatsApp {{ $b['barber'] }}
                            </a>
                        @endif
                    </div>
                @endif

                <form id="booking-form" action="{{ route('booking.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <!-- Customer Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                                Full Name <span class="text-amber-400">*</span>
                            </label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}" required maxlength="255" placeholder="e.g. Ahmad Faiz" 
                                class="w-full px-4 py-3 rounded-xl glass-input text-xs text-zinc-100 placeholder-zinc-600 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                                Phone Number <span class="text-amber-400">*</span>
                            </label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required inputmode="numeric" pattern="[0-9]{9,11}" placeholder="0123456789" 
                                class="w-full px-4 py-3 rounded-xl glass-input text-xs text-zinc-100 placeholder-zinc-600 focus:outline-none">
                        </div>
                    </div>

                    <!-- 1. Select Service -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                            1. Select Service <span class="text-amber-400">*</span>
                        </label>
                        <select name="service_id" required class="w-full px-4 py-3 rounded-xl glass-input text-xs text-zinc-100 focus:outline-none cursor-pointer">
                            <option value="" class="bg-zinc-950 text-zinc-400">-- Choose Service --</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }} class="bg-zinc-950 text-zinc-100">
                                    {{ $service->name }} (RM {{ number_format($service->price, 2) }} · {{ $service->duration_minutes }} min)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Pick a date (next 14 days) -->
                    @php
                        $closedDays = $hours->where('is_closed', true)->pluck('day_of_week')->all();
                    @endphp
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                            2. Pick a Date <span class="text-amber-400">*</span>
                        </label>
                        <div class="slot-scroll flex gap-2 overflow-x-auto pb-2 snap-x">
                            @for($i = 0; $i < 14; $i++)
                                @php
                                    $d      = today()->addDays($i);
                                    $closed = in_array($d->dayOfWeek, $closedDays);
                                @endphp
                                <button type="button" data-date="{{ $d->toDateString() }}" aria-pressed="false" {{ $closed ? 'disabled' : '' }}
                                        class="date-chip snap-start shrink-0 w-16 py-2 rounded-xl border border-zinc-800 bg-zinc-950/60 text-center text-zinc-300 transition-colors">
                                    <span class="block text-[10px] uppercase font-semibold">{{ $i === 0 ? 'Today' : $d->format('D') }}</span>
                                    <span class="block text-lg font-extrabold leading-tight">{{ $d->day }}</span>
                                    <span class="block text-[10px]">{{ $closed ? 'Closed' : $d->format('M') }}</span>
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" name="booking_date" id="booking-date" value="{{ old('booking_date') }}">
                    </div>

                    <!-- 3. Pick a time slot -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">
                                3. Pick a Time <span class="text-amber-400">*</span>
                            </label>
                            <div class="flex items-center gap-3 text-[10px] text-zinc-400">
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm border border-amber-500/60 bg-amber-500/10"></span>Available</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-zinc-800"></span>Full</span>
                            </div>
                        </div>

                        <p id="slot-message" class="text-xs text-zinc-400 py-4 text-center rounded-xl border border-dashed border-zinc-800">
                            Choose a service and a date to see available times.
                        </p>
                        <div id="slot-grid" class="slot-scroll grid grid-cols-3 sm:grid-cols-4 gap-2 max-h-60 overflow-y-auto pr-1"></div>
                        <p id="scroll-hint" class="hidden text-[10px] text-zinc-500 text-center mt-1">Scroll for more times ↓</p>
                        <input type="hidden" name="booking_time" id="booking-time" value="{{ old('booking_time') }}">

                        <p class="text-[11px] text-zinc-500 mt-3">
                            💈 A barber will be assigned to you automatically. You'll see who in your booking receipt, and can WhatsApp them directly.
                        </p>
                    </div>

                    <!-- Selected summary -->
                    <div id="slot-summary" class="hidden p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-300 text-center font-semibold"></div>

                    @if(config('app.booking_deposit') > 0)
                    <!-- Deposit Notice -->
                    <div class="p-4 rounded-2xl bg-zinc-950/80 border border-amber-500/20 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-amber-400 tracking-wide uppercase">Booking Deposit Notice</p>
                            <p class="text-[11px] text-zinc-400 mt-0.5">A fixed deposit is required to reserve your slot.</p>
                        </div>
                        <span class="text-sm font-bold font-mono text-amber-400 bg-amber-500/10 px-3 py-1.5 rounded-xl border border-amber-500/20">
                            RM {{ number_format(config('app.booking_deposit'), 2) }}
                        </span>
                    </div>
                    @endif

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-zinc-950 font-extrabold uppercase tracking-wider rounded-2xl text-xs sm:text-sm transition-all shadow-xl shadow-amber-500/10 active:scale-[0.99] mt-2">
                        Confirm & Reserve Appointment
                    </button>
                </form>

                <!-- Form Error Handling -->
                @if ($errors->any())
                    <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-medium space-y-1">
                        <p class="font-bold uppercase">Please fix the following errors:</p>
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Admin Link -->
                <div class="mt-6 text-center pt-4 border-t border-zinc-800/60">
                    <a href="{{ url('/admin/login') }}" class="text-[11px] text-zinc-500 hover:text-amber-400 transition-colors">
                        Admin Staff Access &rarr;
                    </a>
                </div>

            </div>
        </div>

    </main>

    <!-- Slot picker: date chips + time slot grid -->
    <style>
        .date-chip:not(:disabled):hover { border-color: rgba(245, 158, 11, .5); }
        .date-chip:disabled { opacity: .35; cursor: not-allowed; }
        .date-chip[aria-pressed="true"] { background: #f59e0b; border-color: #f59e0b; color: #09090b; }
        .slot-btn { padding: .6rem 0; border-radius: .75rem; font-size: .75rem; font-weight: 700; font-family: 'JetBrains Mono', monospace;
                    border: 1px solid rgba(245, 158, 11, .35); background: rgba(245, 158, 11, .06); color: #fcd34d; transition: all .15s; }
        .slot-btn:not(:disabled):hover { background: rgba(245, 158, 11, .18); }
        .slot-btn:disabled { border-color: transparent; background: #27272a; color: #52525b; text-decoration: line-through; cursor: not-allowed; }
        .slot-btn[aria-pressed="true"] { background: #f59e0b; border-color: #f59e0b; color: #09090b; }
        /* Dark, thin scrollbars for the date chips and time grid */
        .slot-scroll { scrollbar-width: thin; scrollbar-color: rgba(245, 158, 11, .5) transparent; }
        .slot-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .slot-scroll::-webkit-scrollbar-track { background: transparent; }
        .slot-scroll::-webkit-scrollbar-thumb { background: rgba(245, 158, 11, .45); border-radius: 999px; }
    </style>
    <script>
        (function () {
            const form      = document.getElementById('booking-form');
            const service   = form.elements['service_id'];
            const dateInput = document.getElementById('booking-date');
            const timeInput = document.getElementById('booking-time');
            const grid      = document.getElementById('slot-grid');
            const message   = document.getElementById('slot-message');
            const summary   = document.getElementById('slot-summary');
            const chips     = document.querySelectorAll('.date-chip');

            function showMessage(text) {
                message.textContent = text;
                message.classList.remove('hidden');
            }

            function updateSummary() {
                if (!dateInput.value || !timeInput.value || !service.value) {
                    summary.classList.add('hidden');
                    return;
                }
                const d = new Date(dateInput.value + 'T00:00:00');
                const dateText = d.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });
                const chosen = grid.querySelector('[aria-pressed="true"]');
                summary.textContent = '✔ ' + service.options[service.selectedIndex].text.split(' (')[0] + ' · ' + dateText + ' · ' + (chosen ? chosen.textContent : timeInput.value);
                summary.classList.remove('hidden');
            }

            function selectDate(value) {
                chips.forEach(c => c.setAttribute('aria-pressed', c.dataset.date === value ? 'true' : 'false'));
                dateInput.value = value;
            }

            let requestNo = 0; // only the latest request may draw the slots

            async function loadSlots() {
                const myRequest = ++requestNo;
                grid.innerHTML = '';
                document.getElementById('scroll-hint').classList.add('hidden');
                updateSummary();

                if (!service.value)   return showMessage('Choose a service first to see available times.');
                if (!dateInput.value) return showMessage('Pick a date above to see available times.');

                showMessage('Loading available times...');
                try {
                    const params = new URLSearchParams({ service_id: service.value, date: dateInput.value });
                    const res  = await fetch('{{ route('booking.slots') }}?' + params, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (myRequest !== requestNo) return; // a newer request is running - ignore this old one
                    grid.innerHTML = '';

                    if (!res.ok)                          return showMessage('Please choose a valid date.');
                    if (!data.slots || !data.slots.length) return showMessage(data.message || 'No times available on this day.');

                    if (data.message) { showMessage(data.message); } else { message.classList.add('hidden'); }

                    let keepSelected = false;
                    data.slots.forEach(function (slot) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'slot-btn';
                        btn.textContent = slot.label;
                        btn.disabled = !slot.available;
                        btn.title = slot.available ? 'Available' : 'Fully booked';
                        const isChosen = slot.available && slot.value === timeInput.value;
                        btn.setAttribute('aria-pressed', isChosen ? 'true' : 'false');
                        if (isChosen) keepSelected = true;

                        btn.addEventListener('click', function () {
                            grid.querySelectorAll('.slot-btn').forEach(b => b.setAttribute('aria-pressed', 'false'));
                            btn.setAttribute('aria-pressed', 'true');
                            timeInput.value = slot.value;
                            updateSummary();
                        });
                        grid.appendChild(btn);
                    });
                    if (!keepSelected) timeInput.value = '';
                    updateSummary();
                    document.getElementById('scroll-hint').classList.toggle('hidden', grid.scrollHeight <= grid.clientHeight);
                    const chosenBtn = grid.querySelector('[aria-pressed="true"]');
                    if (chosenBtn) chosenBtn.scrollIntoView({ block: 'nearest' });
                } catch (e) {
                    showMessage('Could not load times. Please check your connection and try again.');
                }
            }

            chips.forEach(chip => chip.addEventListener('click', function () {
                if (chip.disabled) return;
                selectDate(chip.dataset.date);
                timeInput.value = '';
                loadSlots();
            }));

            service.addEventListener('change', function () {
                timeInput.value = '';
                loadSlots();
            });

            // Don't submit without a date and time
            form.addEventListener('submit', function (e) {
                if (!dateInput.value || !timeInput.value) {
                    e.preventDefault();
                    showMessage(!dateInput.value ? 'Please pick a date.' : 'Please pick a time slot.');
                    message.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            // After a validation error, restore the previous choice
            if (dateInput.value) selectDate(dateInput.value);
            loadSlots();
        })();
    </script>

</body>
</html>