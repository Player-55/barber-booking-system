@extends('admin.layout')
@section('title', 'Barbers')

@section('content')

    <!-- Add new barber -->
    <div class="glass-card rounded-3xl p-6">
        <h2 class="text-base font-bold uppercase tracking-wide mb-4">Add New Barber</h2>
        <form action="{{ route('admin.barbers.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
            @csrf
            <div class="sm:col-span-2">
                <label class="label">Name</label>
                <input name="name" value="{{ old('name') }}" required maxlength="100" placeholder="e.g. Hafiz" class="field">
            </div>
            <div>
                <label class="label">Title (optional)</label>
                <input name="specialty" value="{{ old('specialty') }}" maxlength="50" placeholder="e.g. Fade Specialist" class="field">
            </div>
            <div>
                <label class="label">Phone (optional)</label>
                <input name="phone" value="{{ old('phone') }}" maxlength="20" placeholder="012-3456789" class="field">
            </div>
            <input type="hidden" name="is_active" value="1">
            <button class="py-2.5 bg-amber-500 text-zinc-950 font-bold uppercase rounded-xl text-xs hover:bg-amber-400">+ Add</button>
        </form>
    </div>

    <!-- Existing barbers -->
    <div class="glass-card rounded-3xl p-6">
        <h2 class="text-base font-bold uppercase tracking-wide mb-4">Barbers</h2>

        <div class="space-y-3">
            @forelse($barbers as $barber)
                <div class="rounded-2xl border border-zinc-800 bg-zinc-950/50 p-4 {{ $barber->is_active ? '' : 'opacity-60' }}">
                    <form action="{{ route('admin.barbers.update', $barber) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-6 gap-3 items-end">
                        @csrf @method('PUT')
                        <div class="sm:col-span-2">
                            <label class="label">Name</label>
                            <input name="name" value="{{ $barber->name }}" required maxlength="100" class="field">
                        </div>
                        <div>
                            <label class="label">Title</label>
                            <input name="specialty" value="{{ $barber->specialty }}" maxlength="50" class="field">
                        </div>
                        <div>
                            <label class="label">Phone</label>
                            <input name="phone" value="{{ $barber->phone }}" maxlength="20" class="field">
                        </div>
                        <label class="flex items-center gap-2 text-xs text-zinc-300 pb-2.5">
                            <input type="checkbox" name="is_active" value="1" {{ $barber->is_active ? 'checked' : '' }} class="accent-amber-500 w-4 h-4">
                            Taking bookings
                        </label>
                        <button class="py-2.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold rounded-xl text-xs hover:bg-amber-500/30">Save</button>
                    </form>
                    <form action="{{ route('admin.barbers.destroy', $barber) }}" method="POST" class="mt-2 text-right"
                          onsubmit="return confirm('Delete {{ addslashes($barber->name) }}? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button class="text-[11px] text-red-400 hover:underline">Delete</button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-zinc-500 text-center py-6">No barbers yet. Add one above.</p>
            @endforelse
        </div>
    </div>

@endsection
