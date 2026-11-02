@extends('admin.layout')
@section('title', 'Services')

@section('content')

    <!-- Add new service -->
    <div class="glass-card rounded-3xl p-6">
        <h2 class="text-base font-bold uppercase tracking-wide mb-4">Add New Service</h2>
        <form action="{{ route('admin.services.store') }}" method="POST" class="grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
            @csrf
            <div class="col-span-2">
                <label class="label">Service name</label>
                <input name="name" value="{{ old('name') }}" required maxlength="100" placeholder="e.g. Skin Fade" class="field">
            </div>
            <div>
                <label class="label">Price (RM)</label>
                <input name="price" type="number" step="0.01" min="0" value="{{ old('price') }}" required class="field">
            </div>
            <div>
                <label class="label">Minutes</label>
                <input name="duration_minutes" type="number" min="5" max="240" value="{{ old('duration_minutes', 30) }}" required class="field">
            </div>
            <input type="hidden" name="is_active" value="1">
            <button class="col-span-2 sm:col-span-1 py-2.5 bg-amber-500 text-zinc-950 font-bold uppercase rounded-xl text-xs hover:bg-amber-400">+ Add</button>
        </form>
    </div>

    <!-- Existing services -->
    <div class="glass-card rounded-3xl p-6">
        <h2 class="text-base font-bold uppercase tracking-wide mb-4">Services & Prices</h2>

        <div class="space-y-3">
            @forelse($services as $service)
                <div class="rounded-2xl border border-zinc-800 bg-zinc-950/50 p-4 {{ $service->is_active ? '' : 'opacity-60' }}">
                    <form action="{{ route('admin.services.update', $service) }}" method="POST" class="grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
                        @csrf @method('PUT')
                        <div class="col-span-2">
                            <label class="label">Name</label>
                            <input name="name" value="{{ $service->name }}" required maxlength="100" class="field">
                        </div>
                        <div>
                            <label class="label">Price (RM)</label>
                            <input name="price" type="number" step="0.01" min="0" value="{{ $service->price }}" required class="field">
                        </div>
                        <div>
                            <label class="label">Minutes</label>
                            <input name="duration_minutes" type="number" min="5" max="240" value="{{ $service->duration_minutes }}" required class="field">
                        </div>
                        <label class="flex items-center gap-2 text-xs text-zinc-300 pb-2.5">
                            <input type="checkbox" name="is_active" value="1" {{ $service->is_active ? 'checked' : '' }} class="accent-amber-500 w-4 h-4">
                            Show to customers
                        </label>
                        <button class="py-2.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold rounded-xl text-xs hover:bg-amber-500/30">Save</button>
                    </form>
                    <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="mt-2 text-right"
                          onsubmit="return confirm('Delete {{ addslashes($service->name) }}? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button class="text-[11px] text-red-400 hover:underline">Delete</button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-zinc-500 text-center py-6">No services yet. Add one above.</p>
            @endforelse
        </div>
    </div>

@endsection
