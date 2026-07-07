<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    @include('layouts.partials.head')
</head>
<body class="min-h-screen flex items-center justify-center p-md relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-[-10%] right-[-5%] w-[40rem] h-[40rem] bg-primary-container/5 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-5%] w-[35rem] h-[35rem] bg-secondary-container/10 rounded-full blur-[100px]"></div>
    </div>

    <main class="w-full max-w-[480px] z-10">
        <div class="glass-morphism rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-xl flex flex-col gap-lg">
            <div class="text-center space-y-xs">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-primary-container rounded-xl mb-md shadow-lg shadow-primary-container/20">
                    <span class="material-symbols-outlined text-white text-[32px]">hub</span>
                </div>
                <h1 class="font-display-lg text-display-lg text-on-surface tracking-tight">Chào mừng trở lại</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Vui lòng nhập thông tin để truy cập HRM</p>
            </div>

            @if ($errors->any())
                <div class="flex items-center gap-sm bg-error-container text-on-error-container px-md py-sm rounded-lg">
                    <span class="material-symbols-outlined text-[20px]">error</span>
                    <p class="font-body-md text-body-md">{{ $errors->first() }}</p>
                </div>
            @endif

            <form class="flex flex-col gap-md" method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="email">Email công việc</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">mail</span>
                        <input class="w-full pl-[48px] pr-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface placeholder:text-outline-variant transition-all input-focus-ring"
                               id="email" name="email" placeholder="name@company.com" required type="email" value="{{ old('email') }}">
                    </div>
                </div>

                <div class="flex flex-col gap-xs">
                    <div class="flex justify-between items-center px-1">
                        <label class="font-label-md text-label-md text-on-surface-variant" for="password">Mật khẩu</label>
                        <a class="font-label-md text-label-md text-primary hover:underline transition-all" href="#">Quên mật khẩu?</a>
                    </div>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">lock</span>
                        <input class="w-full pl-[48px] pr-[48px] py-sm bg-surface-container-lowest border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface placeholder:text-outline-variant transition-all input-focus-ring"
                               id="password" name="password" placeholder="••••••••" required type="password">
                        <button class="absolute right-md top-1/2 -translate-y-1/2 text-outline hover:text-on-surface transition-colors focus:outline-none" onclick="togglePassword()" type="button">
                            <span class="material-symbols-outlined" id="passwordIcon">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-xs py-base">
                    <input class="w-5 h-5 rounded border-outline-variant text-primary-container focus:ring-primary-container/20 transition-all cursor-pointer" id="remember" name="remember" type="checkbox">
                    <label class="font-body-md text-body-md text-on-surface-variant cursor-pointer select-none" for="remember">Ghi nhớ đăng nhập</label>
                </div>

                <button class="w-full mt-sm py-sm px-lg bg-primary-container text-white font-headline-md text-headline-md rounded-lg shadow-lg shadow-primary-container/25 hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all flex items-center justify-center gap-xs" type="submit">
                    <span>Đăng nhập</span>
                    <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                </button>
            </form>

            <div class="relative my-sm">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t border-outline-variant/50"></span>
                </div>
                <div class="relative flex justify-center text-label-md uppercase">
                    <span class="bg-white/0 px-md text-outline font-medium tracking-wider">Hoặc đăng nhập với</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-md">
                <button type="button" class="flex items-center justify-center gap-xs py-sm border border-outline-variant rounded-lg font-body-md text-on-surface-variant hover:bg-surface-container-low transition-all">
                    <span class="material-symbols-outlined text-[20px]">g_translate</span>
                    <span>Google</span>
                </button>
                <button type="button" class="flex items-center justify-center gap-xs py-sm border border-outline-variant rounded-lg font-body-md text-on-surface-variant hover:bg-surface-container-low transition-all">
                    <span class="material-symbols-outlined text-[20px]">window</span>
                    <span>Office 365</span>
                </button>
            </div>

            <div class="bg-surface-container-low rounded-lg px-md py-sm text-center">
                <p class="font-label-md text-label-md text-on-surface-variant">Tài khoản demo: <span class="font-bold text-primary">admin@HRM.vn</span> / <span class="font-bold text-primary">password</span></p>
            </div>
        </div>

        <footer class="mt-xl text-center space-y-xs">
            <p class="font-body-md text-body-md text-outline">
                Bạn chưa có tài khoản? <a class="text-primary font-semibold hover:underline" href="#">Liên hệ Admin</a>
            </p>
            <p class="font-label-md text-label-md text-outline/60 mt-lg">© {{ date('Y') }} HRM HRM. Bản quyền thuộc về Enterprise Solutions Inc.</p>
        </footer>
    </main>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerText = 'visibility_off';
            } else {
                input.type = 'password';
                icon.innerText = 'visibility';
            }
        }
    </script>
</body>
</html>
