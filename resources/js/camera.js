/**
 * In-page camera: live preview from the webcam (PC) or rear camera (phone), captured straight into a
 * file input. The captured JPEG is put into input.files and a "change" event is fired, so the normal
 * photo pipeline (compression, preview, barcode detection) handles it like a picked file.
 *
 * Browsers only allow camera access on HTTPS or localhost.
 */

const CAPTURE_QUALITY = 0.92;

const ERROR_MESSAGES = {
    NotAllowedError: 'Izin kamera ditolak. Izinkan akses kamera di browser (ikon 🔒 / 📷 di address bar), lalu coba lagi.',
    SecurityError: 'Izin kamera ditolak. Izinkan akses kamera di browser (ikon 🔒 / 📷 di address bar), lalu coba lagi.',
    NotFoundError: 'Kamera tidak ditemukan di perangkat ini.',
    OverconstrainedError: 'Kamera tidak ditemukan di perangkat ini.',
    NotReadableError: 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.',
};

let dialog = null;
let elements = {};
let stream = null;
let targetInput = null;
let videoDevices = [];
let deviceIndex = -1;

function buildDialog() {
    dialog = document.createElement('dialog');
    dialog.className = 'm-auto w-[min(100vw-1rem,48rem)] max-w-none overflow-hidden rounded-2xl bg-slate-900 p-0 text-white shadow-2xl backdrop:bg-slate-950/80';
    dialog.innerHTML = `
        <div class="flex items-center justify-between gap-3 px-4 py-3">
            <h2 class="truncate text-sm font-bold" data-camera-heading>Kamera</h2>
            <button type="button" class="flex size-8 shrink-0 items-center justify-center rounded-full text-xl text-slate-300 hover:bg-slate-800" data-camera-close aria-label="Tutup kamera">×</button>
        </div>
        <div class="relative flex aspect-video items-center justify-center bg-black">
            <video class="size-full object-contain" autoplay playsinline muted data-camera-video></video>
            <p class="absolute inset-x-4 top-1/2 hidden -translate-y-1/2 rounded-lg bg-slate-800/90 p-4 text-center text-sm" data-camera-message></p>
        </div>
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 px-4 py-4">
            <div class="flex gap-2">
                <button type="button" class="hidden rounded-lg bg-slate-700 px-3 py-2 text-xs font-bold hover:bg-slate-600" data-camera-switch>🔄 Ganti Kamera</button>
            </div>
            <button type="button" class="flex size-16 items-center justify-center rounded-full border-4 border-white bg-sky-600 text-xs font-bold hover:bg-sky-500 disabled:cursor-not-allowed disabled:opacity-40" data-camera-shoot aria-label="Ambil foto">Ambil</button>
            <div class="flex justify-end">
                <button type="button" class="rounded-lg bg-slate-700 px-3 py-2 text-xs font-bold hover:bg-slate-600" data-camera-file>📁 Pilih File</button>
            </div>
        </div>
    `;
    document.body.append(dialog);

    elements = {
        heading: dialog.querySelector('[data-camera-heading]'),
        video: dialog.querySelector('[data-camera-video]'),
        message: dialog.querySelector('[data-camera-message]'),
        shoot: dialog.querySelector('[data-camera-shoot]'),
        switchCamera: dialog.querySelector('[data-camera-switch]'),
    };

    dialog.querySelector('[data-camera-close]').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-camera-file]').addEventListener('click', () => {
        const input = targetInput;
        dialog.close();
        input?.click();
    });
    elements.shoot.addEventListener('click', capture);
    elements.switchCamera.addEventListener('click', () => {
        deviceIndex = (deviceIndex + 1) % videoDevices.length;
        startStream(videoDevices[deviceIndex].deviceId);
    });

    // Esc, the × button and "Pilih File" all end up here: always release the camera.
    dialog.addEventListener('close', stopStream);
}

function showMessage(text) {
    elements.message.textContent = text;
    elements.message.classList.toggle('hidden', !text);
    elements.shoot.disabled = Boolean(text);
}

function stopStream() {
    stream?.getTracks().forEach((track) => track.stop());
    stream = null;
    elements.video.srcObject = null;
}

async function startStream(deviceId = null) {
    stopStream();
    showMessage('');
    elements.shoot.disabled = true;

    const video = deviceId
        ? { deviceId: { exact: deviceId } }
        : { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } };

    try {
        stream = await navigator.mediaDevices.getUserMedia({ video, audio: false });
    } catch (error) {
        showMessage(ERROR_MESSAGES[error.name] ?? `Kamera tidak bisa dibuka (${error.name}).`);
        return;
    }

    // The dialog may have been closed while the permission prompt was open.
    if (!dialog.open) {
        stopStream();
        return;
    }

    elements.video.srcObject = stream;
    elements.shoot.disabled = false;

    // Device labels/ids are only fully available after permission is granted.
    videoDevices = (await navigator.mediaDevices.enumerateDevices()).filter((device) => device.kind === 'videoinput');
    const activeId = stream.getVideoTracks()[0]?.getSettings().deviceId;
    deviceIndex = Math.max(0, videoDevices.findIndex((device) => device.deviceId === activeId));
    elements.switchCamera.classList.toggle('hidden', videoDevices.length < 2);
}

function capture() {
    const { video } = elements;

    if (!stream || !video.videoWidth) {
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob((blob) => {
        if (!blob || !targetInput) {
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(new File([blob], `kamera-${Date.now()}.jpg`, { type: 'image/jpeg' }));
        targetInput.files = transfer.files;
        targetInput.dispatchEvent(new Event('change', { bubbles: true }));

        dialog.close();
    }, 'image/jpeg', CAPTURE_QUALITY);
}

/**
 * Without camera access in the page (plain HTTP or an old browser), fall back to the native picker.
 * On phones the capture attribute still opens the camera app directly.
 */
function openNativeCamera(input) {
    if (!window.isSecureContext) {
        window.alert('Kamera langsung hanya bisa dipakai lewat HTTPS. Buka aplikasi dengan alamat https:// atau pilih file foto.');
    }

    input.setAttribute('capture', 'environment');
    input.click();
    setTimeout(() => input.removeAttribute('capture'), 1000);
}

export function openCamera(input, title) {
    if (!navigator.mediaDevices?.getUserMedia || !window.isSecureContext) {
        openNativeCamera(input);
        return;
    }

    if (!dialog) {
        buildDialog();
    }

    targetInput = input;
    elements.heading.textContent = title || 'Kamera';
    dialog.showModal();
    startStream();
}

export function initCameraButtons() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-camera-open]');

        if (!button) {
            return;
        }

        const input = document.getElementById(button.dataset.cameraOpen);

        if (input) {
            openCamera(input, button.dataset.cameraTitle);
        }
    });
}
