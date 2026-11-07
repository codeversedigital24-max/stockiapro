/**
 * sistema/modulos/perfil_empresa_api.js
 * Pestañas, dropzone del logo y guardado del perfil.
 * Sin logs en consola y sin datos en la URL.
 */
(() => {
    'use strict';

    const form = document.getElementById('formPerfil');

    if (!form) {
        return;
    }

    const endpoint = form.dataset.endpoint || '';
    const baseUrl = form.dataset.base || '';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const dropzone = document.getElementById('logoDropzone');
    const logoInput = document.getElementById('logo');
    const logoPreview = document.getElementById('logoPreview');
    const logoReset = document.getElementById('logoReset');
    const logoFileNameText = document.getElementById('logoFileNameText');
    const maxLogo = 2 * 1024 * 1024;
    let previewUrl = null;

    /* --------------------------------------------------------------
       Avisos
       -------------------------------------------------------------- */
    const aviso = (titulo, texto, tipo = 'info') => {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: tipo,
                title: titulo,
                text: texto,
                timer: 3200,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: { popup: 'swal-sp-toast' }
            });
            return;
        }

        alert(`${titulo}: ${texto}`);
    };

    const avisoError = (titulo, texto) => {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: titulo,
                text: texto,
                confirmButtonText: 'Entendido',
                customClass: { popup: 'swal-sp' }
            });
            return;
        }

        alert(`${titulo}: ${texto}`);
    };

    /* --------------------------------------------------------------
       Pestañas
       -------------------------------------------------------------- */
    document.querySelectorAll('[data-tab]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const destino = boton.dataset.tab;

            document.querySelectorAll('[data-tab]').forEach((b) => {
                b.classList.toggle('is-active', b === boton);
                b.setAttribute('aria-selected', b === boton ? 'true' : 'false');
            });

            document.querySelectorAll('[data-panel]').forEach((panel) => {
                panel.classList.toggle('is-active', panel.dataset.panel === destino);
            });
        });
    });

    /* --------------------------------------------------------------
       Logo: clic, teclado, drag & drop y reset
       -------------------------------------------------------------- */
    const archivoValido = (archivo) => {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(archivo.type)) {
            avisoError('Formato no permitido', 'Selecciona una imagen JPG, PNG o WebP.');
            return false;
        }

        if (archivo.size > maxLogo) {
            avisoError('Archivo demasiado grande', 'El logo no puede superar los 2 MB.');
            return false;
        }

        return true;
    };

    const pintarArchivo = (archivo) => {
        if (!logoPreview || !archivo) {
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(archivo);
        logoPreview.src = previewUrl;

        if (dropzone) {
            dropzone.classList.add('has-file');
        }

        if (logoFileNameText) {
            logoFileNameText.textContent = archivo.name;
        }
    };

    const limpiarArchivo = () => {
        if (logoInput) {
            logoInput.value = '';
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        if (logoPreview) {
            logoPreview.src = logoPreview.dataset.original || logoPreview.src;
        }

        if (dropzone) {
            dropzone.classList.remove('has-file', 'is-drag');
        }

        if (logoFileNameText) {
            logoFileNameText.textContent = '';
        }
    };

    if (dropzone && logoInput && logoPreview) {
        dropzone.addEventListener('click', (evento) => {
            if (evento.target.closest('[data-logo-reset]')) {
                return;
            }

            logoInput.click();
        });

        dropzone.addEventListener('keydown', (evento) => {
            if (evento.key === 'Enter' || evento.key === ' ') {
                evento.preventDefault();
                logoInput.click();
            }
        });

        // Evita que el clic programático del input vuelva a disparar el dropzone.
        logoInput.addEventListener('click', (evento) => evento.stopPropagation());

        logoInput.addEventListener('change', () => {
            const archivo = logoInput.files?.[0];

            if (!archivo) {
                return;
            }

            if (!archivoValido(archivo)) {
                limpiarArchivo();
                return;
            }

            pintarArchivo(archivo);
        });

        ['dragenter', 'dragover'].forEach((evento) => {
            dropzone.addEventListener(evento, (e) => {
                e.preventDefault();
                dropzone.classList.add('is-drag');
            });
        });

        ['dragleave', 'drop'].forEach((evento) => {
            dropzone.addEventListener(evento, (e) => {
                e.preventDefault();
                dropzone.classList.remove('is-drag');
            });
        });

        dropzone.addEventListener('drop', (evento) => {
            const archivo = evento.dataTransfer?.files?.[0];

            if (!archivo) {
                return;
            }

            if (!archivoValido(archivo)) {
                return;
            }

            try {
                const transferencia = new DataTransfer();
                transferencia.items.add(archivo);
                logoInput.files = transferencia.files;
            } catch (error) {
                avisoError('No se pudo adjuntar', 'Selecciona el archivo con el botón del explorador.');
                return;
            }

            pintarArchivo(archivo);
        });

        logoReset?.addEventListener('click', (evento) => {
            evento.preventDefault();
            evento.stopPropagation();
            limpiarArchivo();
        });
    }

    /* --------------------------------------------------------------
       Guardado
       -------------------------------------------------------------- */
    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        const botones = [...document.querySelectorAll('[data-save]')];
        botones.forEach((b) => {
            b.disabled = true;
            b.classList.add('is-loading');
        });

        const control = new AbortController();
        const reloj = setTimeout(() => control.abort(), 25000);

        try {
            const respuesta = await fetch(endpoint, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-Token': csrf,
                    'Accept': 'application/json'
                },
                signal: control.signal
            });

            const resultado = await respuesta.json().catch(() => null);

            if (!resultado) {
                throw new Error('respuesta');
            }

            if (!resultado.success) {
                avisoError('No se pudo guardar', resultado.message || 'Revisa los datos e inténtalo de nuevo.');

                if (respuesta.status === 401) {
                    setTimeout(() => window.location.reload(), 1600);
                }
                return;
            }

            actualizarCabecera(resultado.data);

            if (resultado.data?.logo && logoPreview) {
                const src = `${baseUrl}${String(resultado.data.logo).replace(/^\/+/, '')}`;
                logoPreview.src = src;
                logoPreview.dataset.original = src;
            }

            // Ya se guardó: se limpia la selección para no reenviar el archivo.
            if (logoInput) {
                logoInput.value = '';
            }
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
            if (dropzone) {
                dropzone.classList.remove('has-file', 'is-drag');
            }
            if (logoFileNameText) {
                logoFileNameText.textContent = '';
            }

            aviso('Cambios guardados', resultado.message || 'Los cambios se guardaron.', 'success');
        } catch (error) {
            avisoError(
                'Sin conexión',
                error?.name === 'AbortError'
                    ? 'El servidor tardó demasiado en responder.'
                    : 'No se pudo contactar al servidor. Revisa tu conexión.'
            );
        } finally {
            clearTimeout(reloj);
            botones.forEach((b) => {
                b.disabled = false;
                b.classList.remove('is-loading');
            });
        }
    });

    /* --------------------------------------------------------------
       Refresca nombre y logo de la barra superior
       -------------------------------------------------------------- */
    function actualizarCabecera(datos) {
        if (!datos) {
            return;
        }

        const nombre = String(datos.nombre_comercial || '');

        document.querySelectorAll('[data-empresa-nombre]').forEach((el) => {
            el.textContent = nombre;
            el.title = nombre;
        });

        if (datos.logo) {
            const src = `${baseUrl}${String(datos.logo).replace(/^\/+/, '')}`;

            document.querySelectorAll('[data-empresa-logo]').forEach((img) => {
                img.src = src;
            });
        }
    }
})();
