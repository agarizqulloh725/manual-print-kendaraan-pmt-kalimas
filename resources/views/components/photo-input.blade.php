@props(['name', 'label', 'id' => null, 'currentUrl' => null, 'optional' => false])

@php($inputId = $id ?? $name)

<div>
    <label for="{{ $inputId }}" class="form-label flex items-center gap-2">
        <span class="truncate">{{ $label }}</span>
        @if ($optional)
            <span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold tracking-wide text-slate-500">OPSIONAL</span>
        @endif
    </label>
    <label for="{{ $inputId }}"
           class="flex aspect-video cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 text-center text-xs text-slate-500 hover:border-sky-500">
        <img src="{{ $currentUrl }}" alt="{{ $label }}" loading="lazy" @class(['size-full object-cover', 'hidden' => ! $currentUrl]) data-photo-preview="{{ $inputId }}">
        <span @class(['p-3', 'hidden' => $currentUrl]) data-photo-placeholder="{{ $inputId }}">
            Ketuk untuk ambil / pilih foto
            @if ($optional)
                <span class="block text-slate-400">Boleh dikosongkan</span>
            @endif
        </span>
    </label>
    <input id="{{ $inputId }}" name="{{ $name }}" type="file" accept="image/*" class="sr-only" data-photo-input="{{ $inputId }}">
</div>
