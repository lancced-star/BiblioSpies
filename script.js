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
            
            closeBtn.addEventListener('keydown', (ev) => { if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); fermerModal(); } });
            
            closeBtn.focus();
        }

        const escHandler = (ev) => { if (ev.key === 'Escape') { fermerModal(); } };
        document.addEventListener('keydown', escHandler, { once: false });

      
        const originalFermer = fermerModal;
        function fermerModalWrapper() {
            document.removeEventListener('keydown', escHandler);
            originalFermer();
        }
        
        if (closeBtn) closeBtn._fermerWrapper = fermerModalWrapper;

       
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

  
    window.addEventListener('click', (event) => {
        if (event.target === bookModal) { fermerModal(); }
    });

 

});

if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
        navLinks.classList.toggle('show');
    });
}

// Theme toggle functionality
const themeToggleBtn = document.getElementById('theme-toggle');
const themeIcon = document.getElementById('theme-icon');

if (themeToggleBtn) {
    // Check for saved theme preference or default to light mode
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
        document.body.classList.add('dark');
        updateThemeIcon(true);
    }

    themeToggleBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeIcon(isDark);
    });
}

function updateThemeIcon(isDark) {
    if (isDark) {
        themeIcon.innerHTML = `
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        `;
    } else {
        themeIcon.innerHTML = `
            <circle cx="12" cy="12" r="5"></circle>
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
        `;
    }
}

 // Profile menu toggle — position fixed calculée dynamiquement
  function positionProfileMenu() {
      const btn  = document.getElementById('profileBtn');
      const menu = document.getElementById('profileMenu');
      if (!btn || !menu) return;
      const rect = btn.getBoundingClientRect();
      menu.style.top   = (rect.bottom + 8) + 'px';
      menu.style.right = (window.innerWidth - rect.right) + 'px';
      menu.style.left  = 'auto';
  }
  document.addEventListener('click', (e) => {
      const btn  = document.getElementById('profileBtn');
      const menu = document.getElementById('profileMenu');
      if (!btn || !menu) return;
      if (btn.contains(e.target)) {
          const willShow = !menu.classList.contains('show');
          if (willShow) positionProfileMenu();
          menu.classList.toggle('show', willShow);
          btn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
          menu.setAttribute('aria-hidden', willShow ? 'false' : 'true');
          return;
      }
      if (!menu.contains(e.target)) {
          menu.classList.remove('show');
          btn.setAttribute('aria-expanded', 'false');
          menu.setAttribute('aria-hidden', 'true');
      }
  });
  window.addEventListener('resize', () => {
      const menu = document.getElementById('profileMenu');
      if (menu && menu.classList.contains('show')) positionProfileMenu();
  });

// close on Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const menu = document.getElementById('profileMenu');
        const btn = document.getElementById('profileBtn');
        if (menu && menu.classList.contains('show')) {
            menu.classList.remove('show');
            if (btn) btn.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-hidden', 'true');
        }
    }
});