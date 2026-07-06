<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    @include('layouts.partials.head')
    @stack('head')
</head>
<body class="bg-background text-on-background antialiased">
    @include('layouts.partials.sidebar')

    <div class="md:pl-[260px] min-h-screen flex flex-col">
        @include('layouts.partials.navbar')

        <main class="flex-1 pb-24 md:pb-xl">
            @include('layouts.partials.header')
            @yield('content')
        </main>
    </div>

    @include('layouts.partials.bottom-nav')

    @stack('scripts')
</body>
</html>
