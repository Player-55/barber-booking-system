@if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-medium">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-medium">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-medium">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif
