@props(['name', 'label', 'id' => null, 'currentUrl' => null, 'optional' => false])

@php($inputId = $id ?? $name)

{{-- The box opens the in-page camera (webcam on PC, rear camera on phones); picking a file stays available below. --}}
<div>
    <div class="form-label flex items-center gap-2">
        <span class="truncate">{{ $label }}</span>
        @if ($optional)
            <span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold tracking-wide text-slate-500">OPSIONAL</span>
        @endif
    </div>

    <button type="button" data-camera-open="{{ $inputId }}" data-camera-title="{{ $label }}" aria-label="Buka kamera untuk {{ $label }}"
            class="flex aspect-video w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 text-center text-xs text-slate-500 hover:border-sky-500 focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:outline-none">
        <img src="{{ $currentUrl }}" alt="{{ $label }}" loading="lazy" @class(['size-full object-cover', 'hidden' => ! $currentUrl]) data-photo-preview="{{ $inputId }}">
        <span @class(['flex flex-col items-center gap-1 p-3', 'hidden' => $currentUrl]) data-photo-placeholder="{{ $inputId }}">
            <span class="text-2xl" aria-hidden="true">📷</span>
            <span class="font-semibold text-slate-600">Ketuk untuk buka kamera</span>
            @if ($optional)
                <span class="text-slate-400">Boleh dikosongkan</span>
            @endif
        </span>
    </button>

    <div class="mt-2 grid grid-cols-2 gap-2 text-xs font-semibold">
        <button type="button" data-camera-open="{{ $inputId }}" data-camera-title="{{ $label }}"
                class="rounded-lg bg-sky-600 px-2 py-2 text-white hover:bg-sky-700">📷 Kamera</button>
        <label for="{{ $inputId }}" class="cursor-pointer rounded-lg border border-slate-300 bg-white px-2 py-2 text-center text-slate-700 hover:bg-slate-50">📁 Pilih File</label>
    </div>

    <input id="{{ $inputId }}" name="{{ $name }}" type="file" accept="image/*" class="sr-only" data-photo-input="{{ $inputId }}">
</div>
