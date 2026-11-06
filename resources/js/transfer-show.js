const dialog = document.querySelector('[data-share-dialog]');

if (dialog) {
    const shareUrl = dialog.querySelector('[data-share-url]');
    const status = dialog.querySelector('[data-share-status]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const track = async (channel) => {
        try {
            await fetch(dialog.dataset.trackUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ channel }),
            });
        } catch {
            // Sharing should still work if analytics is temporarily unavailable.
        }
    };

    document.querySelector('[data-share-open]')?.addEventListener('click', () => dialog.showModal());
    dialog.querySelector('[data-share-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });

    dialog.querySelector('[data-copy-link]')?.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(shareUrl.value);
        } catch {
            shareUrl.select();
            document.execCommand('copy');
        }
        await track('copy');
        status.textContent = 'Link copied to clipboard.';
    });

    dialog.querySelector('[data-whatsapp-share]')?.addEventListener('click', async () => {
        await track('whatsapp');
        const message = `Files have been shared with you:\n\n${shareUrl.value}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank', 'noopener,noreferrer');
        status.textContent = 'WhatsApp opened. Choose a contact to share.';
    });
}
