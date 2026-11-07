/**
 * assets/js/sistema.js
 * Interacciones compartidas: menú móvil y cierre de menús.
 */
(() => {
    'use strict';

    const toggle = document.getElementById('spMenuToggle');
    const mobileMenu = document.getElementById('spMobileMenu');

    const cerrarMenuMovil = () => {
        if (!toggle || !mobileMenu) {
            return;
        }

        mobileMenu.classList.remove('is-open');
        mobileMenu.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Abrir menú');
        toggle.innerHTML = '<i class="bi bi-list" aria-hidden="true"></i>';
        document.body.classList.remove('sp-menu-open');
    };

    const cerrarDesplegables = () => {
        document.querySelectorAll('details.sp-menu[open]').forEach((menu) => {
            menu.removeAttribute('open');
        });
    };

    if (toggle && mobileMenu) {
        toggle.addEventListener('click', (evento) => {
            evento.stopPropagation();

            const abierto = mobileMenu.classList.toggle('is-open');

            mobileMenu.setAttribute('aria-hidden', abierto ? 'false' : 'true');
            toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
            toggle.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
            toggle.innerHTML = abierto
                ? '<i class="bi bi-x-lg" aria-hidden="true"></i>'
                : '<i class="bi bi-list" aria-hidden="true"></i>';

            document.body.classList.toggle('sp-menu-open', abierto);
        });

        document.addEventListener('click', (evento) => {
            if (!mobileMenu.classList.contains('is-open')) {
                return;
            }

            if (!mobileMenu.contains(evento.target) && !toggle.contains(evento.target)) {
                cerrarMenuMovil();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) {
                cerrarMenuMovil();
            }
        });
    }

    document.addEventListener('click', (evento) => {
        document.querySelectorAll('details.sp-menu[open]').forEach((menu) => {
            if (!menu.contains(evento.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key !== 'Escape') {
            return;
        }

        cerrarDesplegables();
        cerrarMenuMovil();
    });
})();
