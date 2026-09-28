import { initBarcodeCroppers } from './barcode';
import { initCameraButtons } from './camera';
import { initLightbox } from './lightbox';

const MAX_PHOTO_DIMENSION = 1600;
const PHOTO_QUALITY = 0.8;

/**
 * Shrink a camera photo client-side so uploads stay small (phone photos are often 5-10 MB).
 */
async function compressImage(file) {
    if (!file.type.startsWith('image/')) {
        return file;
    }

    const bitmap = await createImageBitmap(file);
    const scale = Math.min(1, MAX_PHOTO_DIMENSION / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', PHOTO_QUALITY));

    if (!blob || blob.size >= file.size) {
        return file;
    }

    return new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
}

function initPhotoInputs() {
    document.querySelectorAll('[data-photo-input]').forEach((input) => {
        const key = input.dataset.photoInput;
        const preview = document.querySelector(`[data-photo-preview="${key}"]`);
        const placeholder = document.querySelector(`[data-photo-placeholder="${key}"]`);

        input.addEventListener('change', async () => {
            const [file] = input.files;

            if (!file) {
                return;
            }

            try {
                const compressed = await compressImage(file);
                const transfer = new DataTransfer();
                transfer.items.add(compressed);
                input.files = transfer.files;
            } catch {
                // Keep the original file if the browser cannot compress it.
            }

            const url = URL.createObjectURL(input.files[0]);
            preview.src = url;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');

            input.dispatchEvent(new CustomEvent('photo-ready', { detail: { url } }));
        });
    });
}

function initVesselSelect() {
    const select = document.querySelector('[data-vessel-select]');

    if (!select) {
        return;
    }

    const info = document.querySelector('[data-vessel-info]');
    const fields = document.querySelectorAll('[data-vessel-field]');
    let vessels = [];

    const fillFields = () => {
        const vessel = vessels.find((item) => item.voyage_no === select.value);

        fields.forEach((field) => {
            field.value = vessel ? (vessel[field.dataset.vesselField] ?? '') : '';
        });

        if (vessel) {
            info.textContent = `${vessel.operator_name ?? ''} · Dermaga ${vessel.berth_name ?? '-'} · ETD ${vessel.etd ?? '-'} · ${vessel.status ?? ''}`;
            info.classList.remove('hidden');
        } else {
            info.classList.add('hidden');
        }
    };

    const setSingleOption = (label) => {
        select.innerHTML = '';
        select.add(new Option(label, ''));
    };

    const load = async (refresh = false) => {
        const selected = select.value || select.dataset.old;
        setSingleOption('⏳ Memuat data kapal...');

        try {
            const url = new URL(select.dataset.url, window.location.origin);

            if (refresh) {
                url.searchParams.set('refresh', '1');
            }

            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message ?? `HTTP ${response.status}`);
            }

            vessels = payload.data ?? [];

            if (vessels.length === 0) {
                setSingleOption('Tidak ada kapal beroperasi saat ini');
                return;
            }

            setSingleOption('-- Pilih Kapal --');
            vessels.forEach((vessel) => {
                select.add(new Option(`${vessel.vessel_name} → ${vessel.destination_port_name} (ETD ${vessel.etd ?? '-'})`, vessel.voyage_no));
            });
            select.value = vessels.some((vessel) => vessel.voyage_no === selected) ? selected : '';
        } catch (error) {
            vessels = [];
            setSingleOption(`❌ ${error.message}`);
        } finally {
            fillFields();
        }
    };

    select.addEventListener('change', fillFields);
    document.querySelector('[data-vessel-refresh]')?.addEventListener('click', () => load(true));
    load();
}

function initWeightMode() {
    const weightInput = document.querySelector('[data-weight-input]');

    if (!weightInput) {
        return;
    }

    const vehicleClass = document.querySelector('[data-vehicle-class]');
    const hint = document.querySelector('[data-weight-hint]');
    const modes = document.querySelectorAll('[data-weight-mode]');

    const sync = () => {
        const isAutomatic = document.querySelector('[data-weight-mode]:checked')?.value === 'otomatis';

        weightInput.readOnly = isAutomatic;
        weightInput.required = !isAutomatic;
        weightInput.classList.toggle('bg-slate-100', isAutomatic);
        hint.classList.toggle('hidden', !isAutomatic);

        if (isAutomatic) {
            weightInput.value = vehicleClass.selectedOptions[0]?.dataset.defaultWeight ?? '';
        }
    };

    modes.forEach((mode) => mode.addEventListener('change', sync));
    vehicleClass.addEventListener('change', sync);
    sync();
}

function initSubmitGuard() {
    document.querySelectorAll('[data-ticket-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
                button.textContent = '⏳ Menyimpan...';
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initPhotoInputs();
    initCameraButtons();
    initLightbox();
    initBarcodeCroppers();
    initVesselSelect();
    initWeightMode();
    initSubmitGuard();
});
