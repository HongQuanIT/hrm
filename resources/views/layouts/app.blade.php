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

    <script>
        (function () {
            function formatMoney(raw) {
                raw = String(raw ?? '');
                var neg = raw.trim().charAt(0) === '-';
                var digits = raw.replace(/[^\d]/g, '');
                digits = digits.replace(/^0+(?=\d)/, '');
                if (digits === '') return neg ? '-' : '';
                var formatted = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return (neg ? '-' : '') + formatted;
            }
            function unformatMoney(val) {
                val = String(val ?? '');
                var neg = val.trim().charAt(0) === '-';
                var digits = val.replace(/[^\d]/g, '');
                if (digits === '') return '';
                return (neg ? '-' : '') + digits;
            }
            function countDigits(str, pos) {
                var c = 0;
                for (var i = 0; i < pos && i < str.length; i++) {
                    if (str[i] >= '0' && str[i] <= '9') c++;
                }
                return c;
            }
            function caretFromDigits(str, digitCount) {
                if (digitCount <= 0) return str.charAt(0) === '-' ? 1 : 0;
                var c = 0;
                for (var i = 0; i < str.length; i++) {
                    if (str[i] >= '0' && str[i] <= '9') {
                        c++;
                        if (c === digitCount) return i + 1;
                    }
                }
                return str.length;
            }
            function handleInput(e) {
                var el = e.target;
                var oldVal = el.value;
                var caret = el.selectionStart == null ? oldVal.length : el.selectionStart;
                var digitsBefore = countDigits(oldVal, caret);
                var newVal = formatMoney(oldVal);
                el.value = newVal;
                var newCaret = caretFromDigits(newVal, digitsBefore);
                try { el.setSelectionRange(newCaret, newCaret); } catch (_) {}
            }
            function initAll(root) {
                (root || document).querySelectorAll('input.money-input').forEach(function (el) {
                    if (el.dataset.moneyBound) return;
                    el.dataset.moneyBound = '1';
                    el.setAttribute('inputmode', 'numeric');
                    el.addEventListener('input', handleInput);
                    el.value = formatMoney(el.value);
                });
            }
            document.addEventListener('DOMContentLoaded', function () { initAll(document); });
            document.addEventListener('submit', function (e) {
                if (!e.target || !e.target.querySelectorAll) return;
                e.target.querySelectorAll('input.money-input').forEach(function (el) {
                    el.value = unformatMoney(el.value);
                });
            }, true);
            window.initMoneyInputs = initAll;
        })();
    </script>

    @stack('scripts')
</body>
</html>
