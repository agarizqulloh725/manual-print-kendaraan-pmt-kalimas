@props(['name', 'label', 'id' => null, 'currentUrl' => null])

@php($inputId = $id ?? $name)

<div>
    <label for="{{ $inputId }}" class="form-label">{{ $label }}</label>
    <label for="{{ $inputId }}"
           class="flex aspect-video cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 text-center text-xs text-slate-500 hover:border-sky-500">
        <img src="{{ $currentUrl }}" alt="{{ $label }}" loading="lazy" @class(['size-full object-cover', 'hidden' => ! $currentUrl]) data-photo-preview="{{ $inputId }}">
        <span @class(['p-3', 'hidden' => $currentUrl]) data-photo-placeholder="{{ $inputId }}">Ketuk untuk ambil / pilih foto</span>
    </label>
    <input id="{{ $inputId }}" name="{{ $name }}" type="file" accept="image/*" class="sr-only" data-photo-input="{{ $inputId }}">
</div>
