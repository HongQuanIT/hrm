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
                <div id="fp-zoom-controls" class="hidden items-center gap-1 mr-xs border border-outline-variant rounded-lg px-1 py-0.5">
                    <button type="button" onclick="fpZoomBy(-0.25)" class="p-1 rounded hover:bg-surface-container-high" title="Thu nhỏ (Ctrl -)">
                        <span class="material-symbols-outlined text-lg">zoom_out</span>
                    </button>
                    <button type="button" id="fp-zoom-label" onclick="fpZoomReset()" class="min-w-[3.25rem] text-center text-label-md tabular-nums hover:text-primary" title="Đặt lại 100%">100%</button>
                    <button type="button" onclick="fpZoomBy(0.25)" class="p-1 rounded hover:bg-surface-container-high" title="Phóng to (Ctrl +)">
                        <span class="material-symbols-outlined text-lg">zoom_in</span>
                    </button>
                </div>
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
        <div id="fp-body" class="flex-1 overflow-auto bg-surface-container-high"></div>
    </div>
</div>

@once
@push('head')
<style>
    /* safe center: căn giữa khi nội dung nhỏ hơn khung, nhưng khi phóng to vượt khung thì
       neo về góc trên-trái để có thể cuộn xem toàn bộ (tránh lỗi bị cắt của flex center). */
    #fp-body { display: flex; align-items: safe center; justify-content: safe center; }
    #fp-stage { flex: none; }
    #fp-media { transform-origin: top left; display: block; }
</style>
@endpush
@push('scripts')
<script>
    let fpZoom = 1;
    let fpBase = null; // kích thước nội dung ở mức 100% {w, h}

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

    // ----- Zoom (transform: scale + bù kích thước stage để cuộn được) -----
    function fpApplyZoom() {
        const media = document.getElementById('fp-media');
        const stage = document.getElementById('fp-stage');
        if (media) media.style.transform = 'scale(' + fpZoom + ')';
        if (stage && fpBase) {
            stage.style.width = (fpBase.w * fpZoom) + 'px';
            stage.style.height = (fpBase.h * fpZoom) + 'px';
        }
        const label = document.getElementById('fp-zoom-label');
        if (label) label.textContent = Math.round(fpZoom * 100) + '%';
    }
    function fpSetBase(w, h) { fpBase = { w: w, h: h }; fpApplyZoom(); }
    function fpSetZoom(z) {
        fpZoom = Math.min(5, Math.max(0.25, Math.round(z * 100) / 100));
        fpApplyZoom();
    }
    function fpZoomBy(delta) { fpSetZoom(fpZoom + delta); }
    function fpZoomReset() { fpSetZoom(1); }
    function fpShowZoom(show) {
        const c = document.getElementById('fp-zoom-controls');
        if (!c) return;
        c.classList.toggle('hidden', !show);
        c.classList.toggle('flex', show);
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

        const type = fpType(mime, ext);
        fpZoom = 1;
        fpBase = null;
        fpShowZoom(false);

        // Hiện modal trước để #fp-body có kích thước thật, phục vụ tính toán base.
        m.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        const body = document.getElementById('fp-body');
        body.innerHTML = fpLoading();

        if (type === 'image') {
            body.innerHTML = '<div id="fp-stage"><img id="fp-media" alt=""></div>';
            const img = document.getElementById('fp-media');
            img.onload = function () {
                const bw = body.clientWidth, bh = body.clientHeight;
                const fit = Math.min(bw / img.naturalWidth, bh / img.naturalHeight, 1) || 1;
                const w = img.naturalWidth * fit, h = img.naturalHeight * fit;
                img.style.width = w + 'px';
                img.style.height = h + 'px';
                fpSetBase(w, h);
                fpShowZoom(true);
            };
            img.onerror = function () { body.innerHTML = fpFallback('Không tải được ảnh.'); };
            img.src = abs;
        } else if (type === 'pdf') {
            const bw = body.clientWidth, bh = body.clientHeight;
            body.innerHTML = '<div id="fp-stage"><iframe id="fp-media" src="' + abs + '" frameborder="0" style="border:0;width:' + bw + 'px;height:' + bh + 'px"></iframe></div>';
            fpSetBase(bw, bh);
            fpShowZoom(true);
        } else if (type === 'text') {
            const bw = body.clientWidth;
            fetch(abs).then(r => r.text()).then(t => {
                body.innerHTML = '<div id="fp-stage"></div>';
                const stage = document.getElementById('fp-stage');
                const pre = document.createElement('pre');
                pre.id = 'fp-media';
                pre.className = 'p-lg text-body-md whitespace-pre-wrap break-words';
                pre.style.width = bw + 'px';
                pre.style.margin = '0';
                pre.textContent = t;
                stage.appendChild(pre);
                fpSetBase(bw, pre.offsetHeight);
                fpShowZoom(true);
            }).catch(() => { body.innerHTML = fpFallback('Không tải được nội dung tệp.'); });
        } else if (type === 'office') {
            const isLocal = /^(localhost|127\.|0\.0\.0\.0|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/.test(window.location.hostname)
                || window.location.hostname.endsWith('.local');
            if (isLocal) {
                body.innerHTML = fpFallback('Xem trước tài liệu Office (Word/Excel/PowerPoint) cần máy chủ truy cập được từ Internet nên không hoạt động ở môi trường nội bộ.');
            } else {
                const bw = body.clientWidth, bh = body.clientHeight;
                const src = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(abs);
                body.innerHTML = '<div id="fp-stage"><iframe id="fp-media" src="' + src + '" frameborder="0" style="border:0;width:' + bw + 'px;height:' + bh + 'px"></iframe></div>';
                fpSetBase(bw, bh);
                fpShowZoom(true);
            }
        } else {
            body.innerHTML = fpFallback('Định dạng này không hỗ trợ xem trước trực tiếp.');
        }
    }

    document.addEventListener('click', function (e) {
        const el = e.target.closest('[data-preview]');
        if (!el) return;
        e.preventDefault();
        openFilePreview(el.dataset.url, el.dataset.name, el.dataset.mime, el.dataset.ext);
    });
    document.addEventListener('keydown', function (e) {
        const open = !document.getElementById('file-preview-modal')?.classList.contains('hidden');
        if (!open) return;
        if (e.key === 'Escape') { closeFilePreview(); return; }
        if ((e.ctrlKey || e.metaKey) && (e.key === '+' || e.key === '=')) { e.preventDefault(); fpZoomBy(0.25); }
        else if ((e.ctrlKey || e.metaKey) && e.key === '-') { e.preventDefault(); fpZoomBy(-0.25); }
        else if ((e.ctrlKey || e.metaKey) && e.key === '0') { e.preventDefault(); fpZoomReset(); }
    });
    // Ctrl + cuộn chuột để phóng to/thu nhỏ trong khung xem trước.
    document.getElementById('fp-body')?.addEventListener('wheel', function (e) {
        if (!(e.ctrlKey || e.metaKey)) return;
        if (document.getElementById('fp-zoom-controls')?.classList.contains('hidden')) return;
        e.preventDefault();
        fpZoomBy(e.deltaY < 0 ? 0.1 : -0.1);
    }, { passive: false });
</script>
@endpush
@endonce
