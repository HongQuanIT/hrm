@props(['id', 'title' => ''])
<div id="modal-{{ $id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center p-md bg-black/40" onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-surface rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-lg py-md border-b border-outline-variant">
            <h3 class="font-headline-md text-headline-md text-on-surface">{{ $title }}</h3>
            <button type="button" onclick="document.getElementById('modal-{{ $id }}').classList.add('hidden')" class="w-9 h-9 rounded-lg hover:bg-surface-container flex items-center justify-center text-on-surface-variant">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-lg">
            {{ $slot }}
        </div>
    </div>
</div>
