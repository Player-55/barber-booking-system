<!DOCTYPE html>
<html lang="en" class="h-full bg-[#0B0F19]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="min-h-full text-zinc-100 bg-[#0B0F19] flex items-center justify-center p-4">

    <div class="w-full max-w-sm bg-zinc-900/80 border border-amber-500/20 p-8 rounded-3xl backdrop-blur-xl shadow-2xl">
        <div class="text-center mb-6">
            <img src="{{ asset(config('app.shop_logo')) }}" alt="Logo" class="w-20 h-20 mx-auto mb-3 rounded-full object-cover">
            <h1 class="text-xl font-extrabold text-amber-400 uppercase tracking-wider">Admin Access</h1>
            <p class="text-xs text-zinc-400 mt-1">Owner login</p>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs text-center font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs text-center font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase text-zinc-400 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="w-full px-4 py-3 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:outline-none focus:border-amber-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-zinc-400 mb-1">Password</label>
                <input type="password" name="password" required autocomplete="current-password"
                    class="w-full px-4 py-3 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:outline-none focus:border-amber-500">
            </div>

            <button type="submit" class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-bold uppercase tracking-wider rounded-xl text-xs hover:from-amber-400 hover:to-amber-500 transition-all">
                Login to Dashboard
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('home') }}" class="text-xs text-zinc-500 hover:text-amber-400 transition-colors">&larr; Return to Main Booking Page</a>
        </div>
    </div>

</body>
</html>