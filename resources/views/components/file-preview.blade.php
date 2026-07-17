{{-- Modal xem trước tài liệu dùng chung cho mọi loại tệp.
     Kích hoạt bằng cách thêm thuộc tính data-preview + data-url/data-name/data-mime/data-ext vào phần tử bất kỳ. --}}
<div id="file-preview-modal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/60" onclick="closeFilePreview()"></div>
    <div class="absolute inset-0 sm:inset-6 lg:inset-16 bg-surface-container-lowest sm:rounded-xl shadow-2xl flex flex-col overflow-hidden">
        <div class="flex items-center justify-between gap-md px-lg py-md border-b border-outline-variant bg-surface-container-low">
            <div class="flex items-center gap-sm min-w-0">
                <span id="fp-icon" class="material-symbols-outlined text-primary shrink-0">description</span>
                <p id="fp-name" class="font-body-md text-body-md font-semibold truncate"></p>
            </div>
            <div class="flex items-center gap-xs shrink-0">
                <a id="fp-open" href="#" target="_blank" rel="noopener"
                   class="hidden sm:inline-flex items-center gap-1 px-md py-1.5 rounded-lg border border-outline-variant text-label-md hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-sm">open_in_new</span> Mở tab mới
                </a>
                <a id="fp-download" href="#" download
                   class="inline-flex items-center gap-1 px-md py-1.5 rounded-lg bg-primary text-on-primary text-label-md hover:opacity-90 transition-opacity">
                    <span class="material-symbols-outlined text-sm">download</span> Tải xuống
                </a>
                <button type="button" onclick="closeFilePreview()" class="text-on-surface-variant hover:text-on-surface p-1" title="Đóng">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <div id="fp-body" class="flex-1 overflow-auto bg-surface-container-high flex items-center justify-center"></div>
    </div>
</div>

@once
@push('scripts')
<script>
    function fpType(mime, ext) {
        mime = (mime || '').toLowerCase();
        ext = (ext || '').toLowerCase();
        if (mime.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)) return 'image';
        if (mime === 'application/pdf' || ext === 'pdf') return 'pdf';
        if (mime.startsWith('text/') || ['txt', 'csv', 'md', 'log', 'json', 'xml'].includes(ext)) return 'text';
        if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(ext)) return 'office';
        return 'other';
    }

    function fpAbsolute(url) {
        try { return new URL(url, window.location.origin).href; } catch (e) { return url; }
    }

    function fpLoading() {
        return '<div class="flex flex-col items-center gap-sm text-on-surface-variant p-lg"><span class="material-symbols-outlined animate-spin">progress_activity</span><span class="text-body-md">Đang tải…</span></div>';
    }

    function fpFallback(message) {
        return '<div class="flex flex-col items-center gap-sm text-on-surface-variant p-xl text-center">'
            + '<span class="material-symbols-outlined text-6xl">visibility_off</span>'
            + '<p class="text-body-md max-w-md">' + message + '</p>'
            + '<p class="text-xs">Dùng nút “Tải xuống” hoặc “Mở tab mới” phía trên.</p></div>';
    }

    function closeFilePreview() {
        const m = document.getElementById('file-preview-modal');
        if (!m) return;
        m.classList.add('hidden');
        document.getElementById('fp-body').innerHTML = '';
        document.body.style.overflow = '';
    }

    function openFilePreview(url, name, mime, ext) {
        const m = document.getElementById('file-preview-modal');
        if (!m) return;
        const abs = fpAbsolute(url);
        document.getElementById('fp-name').textContent = name || 'Tài liệu';
        document.getElementById('fp-open').href = abs;
        document.getElementById('fp-download').href = abs;
        const body = document.getElementById('fp-body');
        const type = fpType(mime, ext);
        body.innerHTML = fpLoading();

        if (type === 'image') {
            body.innerHTML = '<img src="' + abs + '" alt="" class="max-w-full max-h-full object-contain">';
        } else if (type === 'pdf') {
            body.innerHTML = '<iframe src="' + abs + '" class="w-full h-full" frameborder="0"></iframe>';
        } else if (type === 'text') {
            fetch(abs).then(r => r.text()).then(t => {
                const pre = document.createElement('pre');
                pre.className = 'w-full h-full overflow-auto p-lg text-body-md whitespace-pre-wrap break-words self-start';
                pre.textContent = t;
                body.innerHTML = '';
                body.appendChild(pre);
            }).catch(() => { body.innerHTML = fpFallback('Không tải được nội dung tệp.'); });
        } else if (type === 'office') {
            const isLocal = /^(localhost|127\.|0\.0\.0\.0|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/.test(window.location.hostname)
                || window.location.hostname.endsWith('.local');
            if (isLocal) {
                body.innerHTML = fpFallback('Xem trước tài liệu Office (Word/Excel/PowerPoint) cần máy chủ truy cập được từ Internet nên không hoạt động ở môi trường nội bộ.');
            } else {
                const src = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(abs);
                body.innerHTML = '<iframe src="' + src + '" class="w-full h-full" frameborder="0"></iframe>';
            }
        } else {
            body.innerHTML = fpFallback('Định dạng này không hỗ trợ xem trước trực tiếp.');
        }

        m.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    document.addEventListener('click', function (e) {
        const el = e.target.closest('[data-preview]');
        if (!el) return;
        e.preventDefault();
        openFilePreview(el.dataset.url, el.dataset.name, el.dataset.mime, el.dataset.ext);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeFilePreview();
    });
</script>
@endpush
@endonce
