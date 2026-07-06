@if (session('status') || session('error'))
    <div class="px-md md:px-xl pt-md">
        <div class="max-w-container-max mx-auto">
            @if (session('status'))
                <div class="flash-toast flex items-center gap-md bg-secondary-container text-on-secondary-container px-lg py-sm rounded-xl border border-primary/20 mb-sm">
                    <span class="material-symbols-outlined text-primary">check_circle</span>
                    <p class="font-body-md">{{ session('status') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="flex items-center gap-md bg-error-container text-on-error-container px-lg py-sm rounded-xl mb-sm">
                    <span class="material-symbols-outlined">error</span>
                    <p class="font-body-md">{{ session('error') }}</p>
                </div>
            @endif
        </div>
    </div>
    <script>
        setTimeout(function () {
            document.querySelectorAll('.flash-toast').forEach(function (el) {
                el.style.transition = 'opacity .4s'; el.style.opacity = '0';
                setTimeout(function () { el.remove(); }, 400);
            });
        }, 4000);
    </script>
@endif
