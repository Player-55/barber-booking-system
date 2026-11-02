<!-- Top Navigation Bar (shared by all admin pages) -->
<nav class="border-b border-zinc-800/80 bg-zinc-950/60 backdrop-blur-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <img src="{{ asset(config('app.shop_logo')) }}" alt="Logo" class="w-10 h-10">
            <div>
                <h1 class="text-sm font-bold text-amber-400 uppercase tracking-wider">{{ config('app.name') }}</h1>
                <p class="text-[10px] text-zinc-400">Admin Control Center</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <a href="{{ route('home') }}" class="text-xs text-zinc-400 hover:text-amber-400 transition-colors">&larr; Public View</a>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs bg-red-500/10 text-red-400 border border-red-500/20 px-3 py-1.5 rounded-xl hover:bg-red-500/20 transition-all font-semibold">Logout</button>
            </form>
        </div>

        @php
            $links = [
                'admin.dashboard'      => 'Bookings',
                'admin.services.index' => 'Services',
                'admin.barbers.index'  => 'Barbers',
                'admin.schedule.index' => 'Hours & Days Off',
                'admin.commission.index' => 'Commission',
            ];
        @endphp
        <div class="w-full flex gap-2 overflow-x-auto text-xs">
            @foreach($links as $name => $label)
                <a href="{{ route($name) }}"
                   class="px-3 py-1.5 rounded-xl border whitespace-nowrap {{ request()->routeIs(str_replace('.index', '.*', $name)) ? 'bg-amber-500 text-zinc-950 font-bold border-amber-500' : 'bg-zinc-900/80 text-zinc-400 border-zinc-800 hover:text-amber-400' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
</nav>
