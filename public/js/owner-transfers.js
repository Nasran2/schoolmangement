(() => {
    document.querySelectorAll('[data-transfer-dialog]').forEach(button => {
        button.addEventListener('click', () => document.getElementById(button.dataset.transferDialog)?.showModal());
    });
    document.querySelectorAll('.owner-transfer-dialog').forEach(dialog => {
        dialog.querySelectorAll('[data-close-transfer-dialog]').forEach(button => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('click', event => { if (event.target === dialog) {
            const box = dialog.getBoundingClientRect();
            if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close();
        }});
        dialog.querySelector('form')?.addEventListener('submit', () => {
            const button = dialog.querySelector('button[type="submit"]');
            if (button) { button.disabled = true; button.textContent = 'Saving…'; }
        });
        if (dialog.dataset.reopen === 'true') dialog.showModal();
    });
})();
