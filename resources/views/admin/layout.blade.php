<!DOCTYPE html>
<html lang="en" class="h-full bg-[#0B0F19]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .glass-card { background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(245, 158, 11, 0.15); }
        .field { width: 100%; padding: .6rem .8rem; border-radius: .75rem; background: #09090b; border: 1px solid #27272a; font-size: .8rem; color: #f4f4f5; }
        .field:focus { outline: none; border-color: #f59e0b; }
        .label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #a1a1aa; margin-bottom: .25rem; }
    </style>
</head>
<body class="min-h-full text-zinc-100 bg-[#0B0F19] antialiased pb-12">
    @include('admin.partials.nav')
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 space-y-6">
        @include('admin.partials.flash')
        @yield('content')
    </main>
</body>
</html>
