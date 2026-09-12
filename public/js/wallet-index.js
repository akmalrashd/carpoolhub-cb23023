(() => {
    const modal = document.getElementById('walletWithdrawModal');
    const openBtn = document.getElementById('walletWithdrawBtn');
    const closeBtn = document.getElementById('walletWithdrawClose');
    if (!modal || !openBtn || !closeBtn) return;

    const open = () => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    window.CarpoolBottomSheet?.enable({
        modal: modal,
        card: modal.querySelector('.wallet-modal-card'),
        head: modal.querySelector('.wallet-modal-head'),
        closeFn: close,
    });

    openBtn.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });

    // Re-open on a failed submit (validation error redirected back here) so
    // the passenger — well, driver — isn't left staring at a closed modal
    // with no visible clue their request was rejected.
    const form = document.getElementById('walletWithdrawForm');
    if (form && form.querySelector('.has-error, .wallet-modal-error')) {
        open();
    }
})();
