/**
 * Editor de ambientes por sucursal (mismo diseño que Mesas).
 * Globales: activar/desactivar solo para la sucursal (Switchery).
 * Propios: agregar, activar/desactivar y eliminar.
 */
(function () {
    'use strict';

    var editor = document.getElementById('ambientesEditor');
    if (!editor) {
        return;
    }

    var body = document.getElementById('ambientesBody');
    var template = document.getElementById('ambienteRowTemplate');
    var errores = document.getElementById('ambientesErrores');
    var contadores = document.querySelectorAll('.ambientes-count');
    var btnAgregar = document.getElementById('btnAgregarAmbiente');
    var btnGuardar = document.getElementById('btnGuardarAmbientes');
    var urlGuardar = editor.dataset.urlGuardar;

    var eliminados = [];

    function csrfHeaders() {
        var headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        headers[window.APP.csrfHeader] = window.APP.csrfToken;
        return headers;
    }

    function actualizarContador() {
        var total = body.querySelectorAll('.ambiente-row').length;
        contadores.forEach(function (el) { el.textContent = total; });
    }

    /** Inicializa Switchery en los checkboxes que aún no lo tengan */
    function initSwitchery(scope) {
        if (typeof window.Switchery === 'undefined') {
            return;
        }
        (scope || document).querySelectorAll('.ambiente-activo').forEach(function (chk) {
            if (chk.dataset.sw === '1') {
                return;
            }
            chk.dataset.sw = '1';
            // eslint-disable-next-line no-new
            new window.Switchery(chk, { size: 'small', color: '#34C759', secondaryColor: '#8e8e93' });
        });
    }

    function nuevaFila() {
        return template.content.firstElementChild.cloneNode(true);
    }

    function agregarAmbiente() {
        var fila = nuevaFila();
        body.appendChild(fila);
        initSwitchery(fila);
        actualizarContador();
        fila.querySelector('.ambiente-descripcion').focus();
    }

    function eliminarAmbiente(fila) {
        var id = fila.dataset.id;
        if (id) {
            eliminados.push(parseInt(id, 10));
        }
        fila.parentNode.removeChild(fila);
        actualizarContador();
    }

    function mostrarErrores(lista) {
        if (!lista || !lista.length) {
            errores.classList.add('d-none');
            errores.innerHTML = '';
            return;
        }
        var html = '<ul class="mb-0 pl-3">';
        lista.forEach(function (msg) { html += '<li>' + msg + '</li>'; });
        html += '</ul>';
        errores.innerHTML = html;
        errores.classList.remove('d-none');
        errores.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function recolectar() {
        var globales = [];
        var propios = [];

        body.querySelectorAll('.ambiente-row').forEach(function (fila) {
            var activo = fila.querySelector('.ambiente-activo').checked;

            if (fila.dataset.global === '1') {
                globales.push({
                    ambiente_id: parseInt(fila.dataset.id, 10),
                    activo: activo
                });
            } else {
                propios.push({
                    id: fila.dataset.id || '',
                    descripcion: fila.querySelector('.ambiente-descripcion').value.trim(),
                    activo: activo
                });
            }
        });

        return { globales: globales, propios: propios, deleted: eliminados };
    }

    function guardar() {
        mostrarErrores(null);
        btnGuardar.disabled = true;

        fetch(urlGuardar, {
            method: 'POST',
            headers: csrfHeaders(),
            body: JSON.stringify(recolectar())
        })
            .then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            })
            .then(function (result) {
                if (!result.ok) {
                    mostrarErrores(result.data.errors || ['Error al guardar.']);
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                mostrarErrores(['Error de conexión. Intenta de nuevo.']);
            })
            .finally(function () {
                btnGuardar.disabled = false;
            });
    }

    btnAgregar.addEventListener('click', agregarAmbiente);
    btnGuardar.addEventListener('click', guardar);

    body.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-eliminar-ambiente');
        if (btn) {
            eliminarAmbiente(btn.closest('.ambiente-row'));
        }
    });

    initSwitchery(document);
    actualizarContador();
}());
