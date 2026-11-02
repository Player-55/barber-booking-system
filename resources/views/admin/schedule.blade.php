@extends('admin.layout')
@section('title', 'Hours & Days Off')

@section('content')

    <!-- Weekly opening hours -->
    <div class="glass-card rounded-3xl p-6">
        <h2 class="text-base font-bold uppercase tracking-wide">Opening Hours</h2>
        <p class="text-xs text-zinc-400 mb-4">Customers can only book inside these hours. Leave the break empty if there is none.</p>

        <form action="{{ route('admin.schedule.hours') }}" method="POST" class="space-y-3">
            @csrf @method('PUT')

            @foreach($hours as $h)
                @php $d = $h->day_of_week; @endphp
                <div class="rounded-2xl border border-zinc-800 bg-zinc-950/50 p-4 grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
                    <div class="col-span-2 sm:col-span-1">
                        <p class="text-sm font-bold text-zinc-100">{{ $h->dayName() }}</p>
                        <label class="flex items-center gap-2 text-xs text-red-300 mt-1">
                            <input type="checkbox" name="hours[{{ $d }}][is_closed]" value="1" {{ $h->is_closed ? 'checked' : '' }} class="accent-red-500 w-4 h-4">
                            Closed
                        </label>
                    </div>
                    <div>
                        <label class="label">Open</label>
                        <input type="time" step="1800" name="hours[{{ $d }}][open_time]" value="{{ $h->open_time }}" class="field">
                    </div>
                    <div>
                        <label class="label">Close</label>
                        <input type="time" step="1800" name="hours[{{ $d }}][close_time]" value="{{ $h->close_time }}" class="field">
                    </div>
                    <div>
                        <label class="label">Break start</label>
                        <input type="time" step="1800" name="hours[{{ $d }}][break_start]" value="{{ $h->break_start }}" class="field">
                    </div>
                    <div>
                        <label class="label">Break end</label>
                        <input type="time" step="1800" name="hours[{{ $d }}][break_end]" value="{{ $h->break_end }}" class="field">
                    </div>
                </div>
            @endforeach

            <button class="w-full sm:w-auto px-6 py-3 bg-amber-500 text-zinc-950 font-bold uppercase rounded-xl text-xs hover:bg-amber-400">Save Opening Hours</button>
        </form>
    </div>

    <!-- Barber days off -->
    <div class="glass-card rounded-3xl p-6">
        <h2 class="text-base font-bold uppercase tracking-wide">Barber Days Off</h2>
        <p class="text-xs text-zinc-400 mb-4">MC, cuti or holiday. Customers can't book that barber on these dates.</p>

        <form action="{{ route('admin.schedule.daysoff.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end mb-6">
            @csrf
            <div>
                <label class="label">Barber</label>
                <select name="barber_id" required class="field">
                    @foreach($barbers as $barber)
                        <option value="{{ $barber->id }}">{{ $barber->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Date</label>
                <input type="date" name="date" min="{{ today()->toDateString() }}" required class="field">
            </div>
            <div>
                <label class="label">Reason (optional)</label>
                <input name="reason" maxlength="100" placeholder="e.g. MC, Cuti" class="field">
            </div>
            <button class="py-2.5 bg-amber-500 text-zinc-950 font-bold uppercase rounded-xl text-xs hover:bg-amber-400">+ Add Day Off</button>
        </form>

        <div class="space-y-2">
            @forelse($daysOff as $off)
                <div class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-950/50 px-4 py-3 text-xs">
                    <div>
                        <span class="font-bold text-zinc-100">{{ $off->barber->name }}</span>
                        <span class="text-zinc-400">— {{ $off->date->format('D, d M Y') }}</span>
                        @if($off->reason)<span class="text-zinc-500"> ({{ $off->reason }})</span>@endif
                    </div>
                    <form action="{{ route('admin.schedule.daysoff.destroy', $off) }}" method="POST">
                        @csrf @method('DELETE')
                        <button class="text-red-400 hover:underline">Remove</button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-zinc-500 text-center py-4">No upcoming days off.</p>
            @endforelse
        </div>
    </div>

@endsection
