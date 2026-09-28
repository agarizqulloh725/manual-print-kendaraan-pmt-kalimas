/**
 * Full-screen photo viewer for links marked with data-lightbox (href = image URL, data-lightbox = caption).
 * Closes with the × button in the top-right corner, Esc, or a click outside the photo.
 * Without JavaScript the link still opens the image normally.
 */

let dialog = null;
let image = null;
let caption = null;

function buildDialog() {
    dialog = document.createElement('dialog');
    dialog.className = 'm-0 h-dvh max-h-none w-screen max-w-none bg-slate-950/95 p-0 backdrop:bg-slate-950/80';
    dialog.innerHTML = `
        <div class="relative flex size-full flex-col items-center justify-center gap-3 p-4 pt-16" data-lightbox-backdrop>
            <button type="button" class="absolute top-3 right-3 flex size-11 items-center justify-center rounded-full bg-white/15 text-3xl leading-none text-white hover:bg-white/30 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none" data-lightbox-close aria-label="Tutup foto">×</button>
            <img alt="" class="max-h-[calc(100dvh-7rem)] max-w-full rounded-lg bg-white object-contain shadow-2xl" data-lightbox-image>
            <p class="text-center text-sm font-semibold text-white" data-lightbox-caption></p>
        </div>
    `;
    document.body.append(dialog);

    image = dialog.querySelector('[data-lightbox-image]');
    caption = dialog.querySelector('[data-lightbox-caption]');

    dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
    // A click on the dark area (not the photo itself) closes the viewer.
    dialog.querySelector('[data-lightbox-backdrop]').addEventListener('click', (event) => {
        if (event.target === event.currentTarget) {
            dialog.close();
        }
    });
    dialog.addEventListener('close', () => {
        image.removeAttribute('src');
    });
}

export function initLightbox() {
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-lightbox]');

        if (!link) {
            return;
        }

        event.preventDefault();

        if (!dialog) {
            buildDialog();
        }

        image.src = link.href;
        image.alt = link.dataset.lightbox;
        caption.textContent = link.dataset.lightbox;
        dialog.showModal();
    });
}
