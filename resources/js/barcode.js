const TARGET_SIZE = 900;
const MAX_UPSCALE = 3;
const LOCAL_CONTRAST = 0.15;
const ALWAYS_DARK_LUMINANCE = 60;
const TRIM_MARGIN = 6;
const MIN_SELECTION_PX = 10;
const DETECTED_PADDING = 0.12;
const MIN_SHORT_SIDE_RATIO = 0.3;

let zxingPromise = null;

/**
 * Lazy-load the ZXing WebAssembly reader, serving the .wasm file from our own Vite build (no CDN).
 */
function loadZXing() {
    zxingPromise ??= Promise.all([
        import('zxing-wasm/reader'),
        import('zxing-wasm/reader/zxing_reader.wasm?url'),
    ]).then(([zxing, { default: wasmUrl }]) => {
        zxing.prepareZXingModule({
            overrides: {
                locateFile: (path, prefix) => (path.endsWith('.wasm') ? wasmUrl : prefix + path),
            },
        });

        return zxing;
    }).catch((error) => {
        zxingPromise = null;
        throw error;
    });

    return zxingPromise;
}

/**
 * Decode the first barcode in the image data. Returns the ZXing result ({ text, format, position, rotation }) or null.
 */
async function decodeBarcode(imageData) {
    const { readBarcodes } = await loadZXing();
    const results = await readBarcodes(imageData, { tryHarder: true, maxNumberOfSymbols: 1 });

    return results.find((result) => result.text) ?? null;
}

function imageDataOf(source) {
    const width = source.naturalWidth ?? source.width;
    const height = source.naturalHeight ?? source.height;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;

    const context = canvas.getContext('2d', { willReadFrequently: true });
    context.drawImage(source, 0, 0, width, height);

    return context.getImageData(0, 0, width, height);
}

/**
 * Bounding box of the decoded barcode in image pixels, padded so the quiet zone and the printed digits are kept.
 */
function rectFromPosition(position, image) {
    const points = [position.topLeft, position.topRight, position.bottomLeft, position.bottomRight];
    const xs = points.map((point) => point.x);
    const ys = points.map((point) => point.y);

    let x = Math.min(...xs);
    let y = Math.min(...ys);
    let width = Math.max(...xs) - x;
    let height = Math.max(...ys) - y;

    // Linear barcodes can be reported as a thin scan line; grow the short side so the full bars are cut out.
    const longSide = Math.max(width, height);
    if (width < longSide * MIN_SHORT_SIDE_RATIO) {
        x -= (longSide * MIN_SHORT_SIDE_RATIO - width) / 2;
        width = longSide * MIN_SHORT_SIDE_RATIO;
    }
    if (height < longSide * MIN_SHORT_SIDE_RATIO) {
        y -= (longSide * MIN_SHORT_SIDE_RATIO - height) / 2;
        height = longSide * MIN_SHORT_SIDE_RATIO;
    }

    const padding = longSide * DETECTED_PADDING;
    const left = Math.max(0, x - padding);
    const top = Math.max(0, y - padding);

    return {
        x: left,
        y: top,
        width: Math.min(image.naturalWidth, x + width + padding) - left,
        height: Math.min(image.naturalHeight, y + height + padding) - top,
    };
}

/**
 * Rotation (0/90/180/270) that turns the detected barcode upright.
 */
function uprightRotation(detectedRotation) {
    const quarterTurns = Math.round((detectedRotation ?? 0) / 90);

    return (((4 - quarterTurns) % 4) + 4) % 4 * 90;
}

/**
 * Cut the selected area out of the photo (rotated and upscaled for print), keeping all original pixels.
 */
function cropRegion(image, rect, rotation) {
    const scale = Math.min(MAX_UPSCALE, Math.max(1, TARGET_SIZE / Math.max(rect.width, rect.height)));
    const drawWidth = Math.round(rect.width * scale);
    const drawHeight = Math.round(rect.height * scale);
    const isSideways = rotation % 180 !== 0;

    const canvas = document.createElement('canvas');
    canvas.width = isSideways ? drawHeight : drawWidth;
    canvas.height = isSideways ? drawWidth : drawHeight;

    const context = canvas.getContext('2d', { willReadFrequently: true });
    context.imageSmoothingQuality = 'high';
    context.translate(canvas.width / 2, canvas.height / 2);
    context.rotate((rotation * Math.PI) / 180);
    context.drawImage(image, rect.x, rect.y, rect.width, rect.height, -drawWidth / 2, -drawHeight / 2, drawWidth, drawHeight);

    return canvas;
}

/**
 * Keep only the dark barcode strokes of the crop.
 *
 * Uses Bradley adaptive thresholding (local mean via an integral image), so shadows and
 * uneven lighting on the paper are removed as well as the background. Everything that is
 * not ink becomes transparent. Returns a trimmed canvas, or null when nothing dark is found.
 */
export function removeBackground(crop) {
    const { width, height } = crop;
    const imageData = crop.getContext('2d', { willReadFrequently: true }).getImageData(0, 0, width, height);
    const pixels = imageData.data;
    const luminance = new Float64Array(width * height);

    for (let i = 0; i < luminance.length; i++) {
        luminance[i] = 0.299 * pixels[i * 4] + 0.587 * pixels[i * 4 + 1] + 0.114 * pixels[i * 4 + 2];
    }

    const stride = width + 1;
    const integral = new Float64Array(stride * (height + 1));

    for (let y = 0; y < height; y++) {
        let rowSum = 0;

        for (let x = 0; x < width; x++) {
            rowSum += luminance[y * width + x];
            integral[(y + 1) * stride + x + 1] = integral[y * stride + x + 1] + rowSum;
        }
    }

    const half = Math.max(8, Math.floor(Math.max(width, height) / 8));
    let minX = width;
    let minY = height;
    let maxX = -1;
    let maxY = -1;

    for (let y = 0; y < height; y++) {
        const y1 = Math.max(0, y - half);
        const y2 = Math.min(height - 1, y + half);

        for (let x = 0; x < width; x++) {
            const x1 = Math.max(0, x - half);
            const x2 = Math.min(width - 1, x + half);
            const count = (x2 - x1 + 1) * (y2 - y1 + 1);
            const sum = integral[(y2 + 1) * stride + x2 + 1] - integral[y1 * stride + x2 + 1]
                - integral[(y2 + 1) * stride + x1] + integral[y1 * stride + x1];
            const value = luminance[y * width + x];
            const isInk = value * count <= sum * (1 - LOCAL_CONTRAST) || value < ALWAYS_DARK_LUMINANCE;
            const offset = (y * width + x) * 4;

            pixels[offset] = 0;
            pixels[offset + 1] = 0;
            pixels[offset + 2] = 0;
            pixels[offset + 3] = isInk ? 255 : 0;

            if (isInk) {
                minX = Math.min(minX, x);
                minY = Math.min(minY, y);
                maxX = Math.max(maxX, x);
                maxY = Math.max(maxY, y);
            }
        }
    }

    if (maxX < 0) {
        return null;
    }

    const trimX = Math.max(0, minX - TRIM_MARGIN);
    const trimY = Math.max(0, minY - TRIM_MARGIN);
    const trimmed = document.createElement('canvas');
    trimmed.width = Math.min(width, maxX + TRIM_MARGIN + 1) - trimX;
    trimmed.height = Math.min(height, maxY + TRIM_MARGIN + 1) - trimY;
    trimmed.getContext('2d').putImageData(imageData, -trimX, -trimY);

    return trimmed;
}

function initBarcodeCropper(root) {
    const photoInput = document.getElementById(root.dataset.barcodeCropper);
    const panel = root.querySelector('[data-barcode-panel]');
    const stage = root.querySelector('[data-barcode-stage]');
    const source = root.querySelector('[data-barcode-source]');
    const selectionBox = root.querySelector('[data-barcode-selection]');
    const status = root.querySelector('[data-barcode-status]');
    const preview = root.querySelector('[data-barcode-preview]');
    const output = root.querySelector('[data-barcode-output]');
    const detectButton = root.querySelector('[data-barcode-detect]');
    const valueInput = root.querySelector('[data-barcode-value]');
    const formatInput = root.querySelector('[data-barcode-format]');
    const formatLabel = root.querySelector('[data-barcode-format-label]');

    let selection = null;
    let rotation = 0;
    let dragStart = null;

    const setStatus = (message) => {
        status.textContent = message;
    };

    const setBarcodeValue = (text, format) => {
        valueInput.value = text;
        formatInput.value = format;
        formatLabel.textContent = format;
    };

    const clearOutput = () => {
        output.files = new DataTransfer().files;
    };

    const renderSelection = () => {
        if (!selection || !source.naturalWidth) {
            selectionBox.classList.add('hidden');
            return;
        }

        selectionBox.style.left = `${(selection.x / source.naturalWidth) * 100}%`;
        selectionBox.style.top = `${(selection.y / source.naturalHeight) * 100}%`;
        selectionBox.style.width = `${(selection.width / source.naturalWidth) * 100}%`;
        selectionBox.style.height = `${(selection.height / source.naturalHeight) * 100}%`;
        selectionBox.classList.remove('hidden');
    };

    /**
     * Cut out the selection, remove its background and attach the PNG to the form. Returns the raw crop.
     */
    const renderBarcodeImage = () => {
        renderSelection();

        if (!selection) {
            return null;
        }

        const crop = cropRegion(source, selection, rotation);
        const barcode = removeBackground(crop);

        if (!barcode) {
            clearOutput();
            preview.classList.add('hidden');
            setStatus('Tidak ada barcode di area ini. Coba pilih ulang.');
            return null;
        }

        barcode.toBlob((blob) => {
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'barcode.png', { type: 'image/png' }));
            output.files = transfer.files;

            preview.src = URL.createObjectURL(blob);
            preview.classList.remove('hidden');
        }, 'image/png');

        return crop;
    };

    const readSelectionValue = async (crop) => {
        setStatus('Membaca nilai barcode...');

        try {
            const result = await decodeBarcode(imageDataOf(crop));

            if (result) {
                setBarcodeValue(result.text, result.format);
                setStatus('Barcode terbaca. Gambar akan dicetak di bagian bawah tiket.');
                return;
            }

            setStatus('Gambar barcode siap, tapi nilainya tidak terbaca. Perbesar area pilihan atau ketik nilainya manual.');
        } catch {
            setStatus('Pembaca barcode gagal dimuat. Ketik nilai barcode manual.');
        }
    };

    const autoDetect = async () => {
        if (!source.naturalWidth) {
            return;
        }

        detectButton.disabled = true;
        setStatus('Mencari barcode di foto...');

        try {
            const result = await decodeBarcode(imageDataOf(source));

            if (!result) {
                setStatus('Barcode tidak terdeteksi otomatis. Tarik kotak di atas barcode.');
                return;
            }

            selection = rectFromPosition(result.position, source);
            rotation = uprightRotation(result.rotation);
            setBarcodeValue(result.text, result.format);

            if (renderBarcodeImage()) {
                setStatus('Barcode terdeteksi dan terbaca. Periksa hasilnya, putar bila perlu.');
            }
        } catch {
            setStatus('Pembaca barcode gagal dimuat. Tarik kotak di atas barcode dan ketik nilainya manual.');
        } finally {
            detectButton.disabled = false;
        }
    };

    const pointFromEvent = (event) => {
        const bounds = stage.getBoundingClientRect();
        const clamp = (value) => Math.min(1, Math.max(0, value));

        return {
            x: clamp((event.clientX - bounds.left) / bounds.width) * source.naturalWidth,
            y: clamp((event.clientY - bounds.top) / bounds.height) * source.naturalHeight,
        };
    };

    stage.addEventListener('pointerdown', (event) => {
        if (!source.naturalWidth) {
            return;
        }

        stage.setPointerCapture(event.pointerId);
        dragStart = pointFromEvent(event);
    });

    stage.addEventListener('pointermove', (event) => {
        if (!dragStart) {
            return;
        }

        const point = pointFromEvent(event);
        selection = {
            x: Math.min(dragStart.x, point.x),
            y: Math.min(dragStart.y, point.y),
            width: Math.abs(point.x - dragStart.x),
            height: Math.abs(point.y - dragStart.y),
        };
        renderSelection();
    });

    stage.addEventListener('pointerup', () => {
        if (!dragStart) {
            return;
        }

        dragStart = null;

        if (!selection || selection.width < MIN_SELECTION_PX || selection.height < MIN_SELECTION_PX) {
            selection = null;
            renderSelection();
            return;
        }

        rotation = 0;
        const crop = renderBarcodeImage();

        if (crop) {
            readSelectionValue(crop);
        }
    });

    root.querySelector('[data-barcode-rotate]').addEventListener('click', () => {
        rotation = (rotation + 90) % 360;
        renderBarcodeImage();
    });

    root.querySelector('[data-barcode-clear]').addEventListener('click', () => {
        selection = null;
        clearOutput();
        renderSelection();
        preview.classList.add('hidden');
        setStatus('Pilihan dihapus. Tarik kotak di atas barcode atau klik Deteksi Otomatis.');
    });

    detectButton.addEventListener('click', autoDetect);

    valueInput.addEventListener('input', () => {
        // A hand-typed value has no scanned format.
        formatInput.value = '';
        formatLabel.textContent = '';
    });

    const loadSource = (url, shouldAutoDetect) => {
        selection = null;
        rotation = 0;
        clearOutput();
        renderSelection();
        panel.classList.remove('hidden');
        source.onload = shouldAutoDetect ? autoDetect : null;
        source.src = url;
    };

    photoInput.addEventListener('photo-ready', (event) => {
        preview.classList.add('hidden');
        setBarcodeValue('', '');
        loadSource(event.detail.url, true);
    });

    if (root.dataset.sourceUrl) {
        // Only download the stored photo once its panel is actually visible (reprint cards start collapsed).
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();

                if (!source.src) {
                    loadSource(root.dataset.sourceUrl, false);
                }
            }
        });
        observer.observe(root);
    }
}

export function initBarcodeCroppers() {
    document.querySelectorAll('[data-barcode-cropper]').forEach(initBarcodeCropper);
}
