/**
 * assets/js/login.js
 * Lógica del formulario de acceso. Sin frameworks ni archivo app.js.
 * Envía las credenciales a sistema/ajax/login.php y muestra SweetAlert2.
 */
(() => {
    'use strict';

    const ENDPOINT = 'sistema/ajax/login.php';

    const form       = document.getElementById('formLogin');
    const inputId    = document.getElementById('identificador');
    const inputPass  = document.getElementById('password');
    const togglePass = document.getElementById('togglePassword');
    const btnLogin   = document.getElementById('btnLogin');
    const btnLabel   = btnLogin.querySelector('.login-btn__label');
    const btnArrow   = btnLogin.querySelector('.login-btn__arrow');
    const btnSpinner = btnLogin.querySelector('.login-btn__spinner');
    const alertBox   = document.getElementById('loginAlert');
    const csrfInput  = document.getElementById('csrfToken');
    const recordar   = document.getElementById('recordar');

    let enviando = false;

    /* --------------------------------------------------------------
       Utilidades
       -------------------------------------------------------------- */
    const csrf = () =>
        (csrfInput && csrfInput.value) ||
        document.querySelector('meta[name="csrf-token"]')?.content ||
        '';

    const setError = (input, mensaje) => {
        const error = document.getElementById(
            'error' + input.id.charAt(0).toUpperCase() + input.id.slice(1)
        );
        if (error) error.textContent = mensaje || '';
        input.classList.toggle('is-invalid', Boolean(mensaje));
        input.setAttribute('aria-invalid', mensaje ? 'true' : 'false');
    };

    const limpiarErrores = () => {
        setError(inputId, '');
        setError(inputPass, '');
        alertBox.hidden = true;
        alertBox.textContent = '';
    };

    const mostrarAlerta = (mensaje) => {
        alertBox.textContent = mensaje;
        alertBox.hidden = false;
    };

    const setCargando = (estado) => {
        enviando = estado;
        btnLogin.disabled = estado;
        btnLabel.textContent = estado ? 'Verificando…' : 'Iniciar sesión';
        btnSpinner.hidden = !estado;
        btnArrow.style.display = estado ? 'none' : '';
    };

    const modalError = (titulo, texto) => {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: titulo,
                text: texto,
                confirmButtonText: 'Entendido',
                customClass: { popup: 'swal-stockia' }
            });
            return;
        }
        mostrarAlerta(texto);
    };

    const toastExito = (nombre) => {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: nombre ? `Bienvenido, ${nombre}` : 'Bienvenido',
                showConfirmButton: false,
                timer: 1400,
                timerProgressBar: true,
                customClass: { popup: 'swal-stockia-toast' }
            });
            return;
        }
        mostrarAlerta('Acceso concedido. Redirigiendo…');
    };

    /* --------------------------------------------------------------
       Validación local (el servidor vuelve a validar todo)
       -------------------------------------------------------------- */
    const validar = () => {
        limpiarErrores();
        let valido = true;

        const identificador = inputId.value.trim();

        if (identificador.length === 0) {
            setError(inputId, 'Ingresa tu usuario o correo.');
            valido = false;
        } else if (identificador.length < 3) {
            setError(inputId, 'El usuario debe tener al menos 3 caracteres.');
            valido = false;
        } else if (identificador.includes('@') && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identificador)) {
            setError(inputId, 'El correo no tiene un formato válido.');
            valido = false;
        }

        if (inputPass.value.length === 0) {
            setError(inputPass, 'Ingresa tu contraseña.');
            valido = false;
        } else if (inputPass.value.length < 6) {
            setError(inputPass, 'La contraseña debe tener al menos 6 caracteres.');
            valido = false;
        }

        return valido;
    };

    /* --------------------------------------------------------------
       Mostrar / ocultar contraseña
       -------------------------------------------------------------- */
    togglePass.addEventListener('click', () => {
        const mostrar = inputPass.type === 'password';

        inputPass.type = mostrar ? 'text' : 'password';
        togglePass.setAttribute('aria-pressed', String(mostrar));
        togglePass.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
        togglePass.innerHTML = `<i class="bi bi-eye${mostrar ? '-slash' : ''}" aria-hidden="true"></i>`;
        inputPass.focus();
    });

    /* --------------------------------------------------------------
       Envío
       -------------------------------------------------------------- */
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (enviando || !validar()) {
            return;
        }

        setCargando(true);

        const datos = new FormData();
        datos.append('identificador', inputId.value.trim());
        datos.append('password', inputPass.value);
        datos.append('csrf_token', csrf());

        try {
            const respuesta = await fetch(ENDPOINT, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json'
                }
            });

            const resultado = await respuesta.json().catch(() => {
                throw new Error('Respuesta inválida del servidor.');
            });

            if (resultado.ok === true) {
                guardarUsuarioRecordado();
                setCargando(false);
                toastExito(resultado.nombre);

                window.setTimeout(() => {
                    window.location.href = resultado.redirect || 'dashboard.php';
                }, 750);
                return;
            }

            setCargando(false);
            manejarError(resultado);
        } catch (error) {
            setCargando(false);
            modalError('Sin conexión', 'No pudimos contactar al servidor. Revisa tu conexión e inténtalo de nuevo.');
        }
    });

    const manejarError = (resultado) => {
        const mensaje = resultado.mensaje || 'No se pudo iniciar sesión.';

        switch (resultado.codigo) {
            case 'credenciales':
                setError(inputId, '');
                setError(inputPass, '');
                inputPass.value = '';
                inputPass.focus();
                modalError('Credenciales incorrectas', mensaje);
                break;

            case 'bloqueado':
                modalError('Acceso bloqueado', mensaje);
                break;

            case 'suscripcion':
                modalError('Suscripción inactiva', mensaje);
                break;

            case 'inactivo':
                modalError('Cuenta inactiva', mensaje);
                break;

            case 'csrf':
                modalError('Sesión expirada', mensaje);
                window.setTimeout(() => window.location.reload(), 1800);
                break;

            case 'rate':
                modalError('Demasiados intentos', mensaje);
                break;

            case 'validacion':
                mostrarAlerta(mensaje);
                break;

            default:
                modalError('No se pudo iniciar sesión', mensaje);
        }
    };

    /* --------------------------------------------------------------
       Recordar usuario (solo el nombre, nunca la contraseña)
       -------------------------------------------------------------- */
    const guardarUsuarioRecordado = () => {
        try {
            if (recordar.checked) {
                localStorage.setItem('sp_usuario', inputId.value.trim());
            } else {
                localStorage.removeItem('sp_usuario');
            }
        } catch (error) {
            /* localStorage puede estar bloqueado: no es crítico */
        }
    };

    const cargarUsuarioRecordado = () => {
        try {
            const recordado = localStorage.getItem('sp_usuario');
            if (recordado && !inputId.value) {
                inputId.value = recordado;
                recordar.checked = true;
                inputPass.focus();
            }
        } catch (error) {
            /* ignorar */
        }
    };

    /* --------------------------------------------------------------
       Limpieza al escribir y mensaje de sesión expirada
       -------------------------------------------------------------- */
    inputId.addEventListener('input', () => setError(inputId, ''));
    inputPass.addEventListener('input', () => setError(inputPass, ''));

    cargarUsuarioRecordado();

    const flash = document.body.dataset.flash;
    if (flash) {
        window.setTimeout(() => modalError('Sesión finalizada', flash), 250);
    }
})();
