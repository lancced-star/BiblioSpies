const navToggle = document.getElementById('navToggle');
const navLinks = document.querySelector('.nav-links');

document.addEventListener('DOMContentLoaded', () => {
    const bookModal = document.getElementById('bookModal');
    const bookModalContent = document.getElementById('bookModalContent');

    function fermerModal() {
        if (bookModal) {
            bookModal.style.display = 'none';
            if (bookModalContent) bookModalContent.innerHTML = '';
        }
    }

    function showModal(data) {
        if (!bookModal || !bookModalContent) return;
        const titre = data.titre ? escapeHtml(data.titre) : 'Titre inconnu';
        const auteur = data.auteur ? escapeHtml(data.auteur) : 'Auteur inconnu';
        const resume = data.resume ? escapeHtml(data.resume) : '';
        const annee = data.annee ? escapeHtml(data.annee) : '';
        const editeur = data.editeur ? escapeHtml(data.editeur) : '';
        const nbpages = data.nbpages ? escapeHtml(data.nbpages) : '';
        const imageHtml = data.image ? `<div class="modal-image"><img src="${escapeHtml(data.image)}" alt="${escapeHtml(data.titre)}"></div>` : '';

        bookModalContent.innerHTML = `
            <button id="modalClose" class="close-btn" aria-label="Fermer la fenêtre" title="Fermer">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12l-4.9 4.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.9a1 1 0 0 0 1.41-1.41L13.41 12l4.9-4.89a1 1 0 0 0 0-1.4z" fill="currentColor"/>
                </svg>
            </button>
            <div class="modal-body-details">
                ${imageHtml}
                <div class="modal-text">
                    <h2 id="modal-title">${titre}</h2>
                    <p class="modal-author">${auteur} ${annee ? '— ' + annee : ''}</p>
                    ${editeur ? '<p class="modal-editeur">Éditeur: ' + editeur + '</p>' : ''}
                    ${nbpages ? '<p class="modal-pages">' + nbpages + ' pages</p>' : ''}
                    <p class="modal-resume">${resume}</p>
                </div>
            </div>
        `;

        const closeBtn = document.getElementById('modalClose');
        if (closeBtn) {
            closeBtn.addEventListener('click', fermerModal);
            // allow keyboard activation
            closeBtn.addEventListener('keydown', (ev) => { if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); fermerModal(); } });
            // focus the button for accessibility
            closeBtn.focus();
        }

        // close on Escape
        const escHandler = (ev) => { if (ev.key === 'Escape') { fermerModal(); } };
        document.addEventListener('keydown', escHandler, { once: false });

        // ensure we remove listener when modal closed
        const originalFermer = fermerModal;
        function fermerModalWrapper() {
            document.removeEventListener('keydown', escHandler);
            originalFermer();
        }
        // replace fermerModal with wrapper in this scope by binding to closeBtn and outside click
        if (closeBtn) closeBtn._fermerWrapper = fermerModalWrapper;

        // override global fermerModal to use wrapper while modal open
        window._modalFermerBackup = window._modalFermerBackup || fermerModal;
        window._modalFermer = fermerModalWrapper;

        bookModal.style.display = 'flex';
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Close when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === bookModal) { fermerModal(); }
    });

    // Book links now navigate to `book-page.php?isbn=...` directly; no AJAX interception.

});

if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
        navLinks.classList.toggle('show');
    });
}
