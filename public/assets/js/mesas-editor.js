/**
 * Editor de mesas por sucursal (estilo SevenRooms).
 * Agregar, duplicar, eliminar y guardar por lote (Mesa / Mini Pax / Max Pax / Ambiente),
 * y alta de ambientes propios de la sucursal en línea.
 */
(function () {
    'use strict';

    var editor = document.getElementById('mesasEditor');
    if (!editor) {
        return;
    }

    var body = document.getElementById('mesasBody');
    var template = document.getElementById('mesaRowTemplate');
    var errores = document.getElementById('mesasErrores');
    var contadores = document.querySelectorAll('.mesas-count');
    var btnAgregar = document.getElementById('btnAgregarMesa');
    var btnGuardar = document.getElementById('btnGuardarMesas');
    var urlGuardar = editor.dataset.urlGuardar;
    var urlAmbiente = editor.dataset.urlAmbiente;

    // IDs de mesas existentes que el usuario eliminó y deben borrarse al guardar
    var eliminadas = [];

    function csrfHeaders(json) {
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (json) {
            headers['Content-Type'] = 'application/json';
        }
        headers[window.APP.csrfHeader] = window.APP.csrfToken;
        return headers;
    }

    function actualizarContador() {
        var total = body.querySelectorAll('.mesa-row').length;
        contadores.forEach(function (el) { el.textContent = total; });
        var vacio = document.getElementById('mesasVacio');
        if (vacio) {
            vacio.classList.toggle('d-none', total > 0);
        }
    }

    function nuevaFila() {
        return template.content.firstElementChild.cloneNode(true);
    }

    function agregarMesa() {
        var fila = nuevaFila();
        body.appendChild(fila);
        actualizarContador();
        fila.querySelector('.mesa-numero').focus();
    }

    function duplicarMesa(filaOriginal) {
        var fila = nuevaFila();
        // Se copian los valores salvo el número de mesa (no se debe duplicar)
        fila.querySelector('.mesa-numero').value = '';
        fila.querySelector('.mesa-minimo').value = filaOriginal.querySelector('.mesa-minimo').value;
        fila.querySelector('.mesa-maximo').value = filaOriginal.querySelector('.mesa-maximo').value;
        fila.querySelector('.mesa-ambiente').value = filaOriginal.querySelector('.mesa-ambiente').value;

        if (filaOriginal.nextSibling) {
            body.insertBefore(fila, filaOriginal.nextSibling);
        } else {
            body.appendChild(fila);
        }
        actualizarContador();
        fila.querySelector('.mesa-numero').focus();
    }

    function eliminarMesa(fila) {
        var id = fila.dataset.id;
        if (id) {
            eliminadas.push(parseInt(id, 10));
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
        lista.forEach(function (msg) {
            html += '<li>' + msg + '</li>';
        });
        html += '</ul>';
        errores.innerHTML = html;
        errores.classList.remove('d-none');
        errores.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function recolectarFilas() {
        var filas = [];
        body.querySelectorAll('.mesa-row').forEach(function (fila) {
            filas.push({
                id: fila.dataset.id || '',
                numero: fila.querySelector('.mesa-numero').value.trim(),
                minimo: parseInt(fila.querySelector('.mesa-minimo').value, 10) || 0,
                maximo: parseInt(fila.querySelector('.mesa-maximo').value, 10) || 0,
                ambiente_id: parseInt(fila.querySelector('.mesa-ambiente').value, 10) || 0
            });
        });
        return filas;
    }

    function guardar() {
        mostrarErrores(null);
        btnGuardar.disabled = true;

        fetch(urlGuardar, {
            method: 'POST',
            headers: csrfHeaders(true),
            body: JSON.stringify({ rows: recolectarFilas(), deleted: eliminadas })
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

    /** Agrega un ambiente nuevo (de sucursal) a todos los selects de la tabla */
    function agregarOpcionAmbiente(ambiente) {
        body.querySelectorAll('.mesa-ambiente').forEach(function (select) {
            agregarOpcionAGrupo(select, ambiente);
        });
        // También a la plantilla, para futuras filas
        var selectTpl = template.content.querySelector('.mesa-ambiente');
        if (selectTpl) {
            agregarOpcionAGrupo(selectTpl, ambiente);
        }
    }

    function agregarOpcionAGrupo(select, ambiente) {
        var grupo = Array.prototype.find.call(select.querySelectorAll('optgroup'), function (g) {
            return g.label === 'De la sucursal';
        });
        if (!grupo) {
            grupo = document.createElement('optgroup');
            grupo.label = 'De la sucursal';
            select.appendChild(grupo);
        }
        var opt = document.createElement('option');
        opt.value = ambiente.id;
        opt.textContent = ambiente.descripcion;
        grupo.appendChild(opt);
    }

    function abrirModalAmbiente() {
        var input = document.getElementById('ambienteDescripcion');
        var err = document.getElementById('ambienteError');
        input.value = '';
        err.classList.add('d-none');
        window.jQuery('#modalAmbiente').modal('show');
        setTimeout(function () { input.focus(); }, 300);
    }

    function guardarAmbiente() {
        var input = document.getElementById('ambienteDescripcion');
        var err = document.getElementById('ambienteError');
        var btn = document.getElementById('btnGuardarAmbiente');
        err.classList.add('d-none');

        if (input.value.trim().length < 2) {
            err.textContent = 'Escribe una descripción válida.';
            err.classList.remove('d-none');
            return;
        }

        btn.disabled = true;
        var fd = new FormData();
        fd.append('descripcion', input.value.trim());

        fetch(urlAmbiente, { method: 'POST', headers: csrfHeaders(false), body: fd })
            .then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            })
            .then(function (result) {
                if (!result.ok || !result.data.success) {
                    var msgs = result.data.errors;
                    err.textContent = msgs
                        ? (Array.isArray(msgs) ? msgs.join(' ') : Object.values(msgs).join(' '))
                        : 'No se pudo crear el ambiente.';
                    err.classList.remove('d-none');
                    return;
                }
                agregarOpcionAmbiente(result.data.ambiente);
                window.jQuery('#modalAmbiente').modal('hide');
            })
            .catch(function () {
                err.textContent = 'Error de conexión.';
                err.classList.remove('d-none');
            })
            .finally(function () {
                btn.disabled = false;
            });
    }

    /** Carga inicial: si no hay mesas, prepara 5 filas vacías para llenar */
    function prepararFilasIniciales() {
        if (body.querySelectorAll('.mesa-row').length > 0) {
            return;
        }
        for (var i = 0; i < 5; i++) {
            body.appendChild(nuevaFila());
        }
        actualizarContador();
    }

    btnAgregar.addEventListener('click', agregarMesa);
    btnGuardar.addEventListener('click', guardar);
    document.getElementById('btnNuevoAmbiente').addEventListener('click', abrirModalAmbiente);
    document.getElementById('btnGuardarAmbiente').addEventListener('click', guardarAmbiente);

    body.addEventListener('click', function (e) {
        var btnEliminar = e.target.closest('.btn-eliminar-mesa');
        if (btnEliminar) {
            eliminarMesa(btnEliminar.closest('.mesa-row'));
            return;
        }
        var btnDuplicar = e.target.closest('.btn-duplicar-mesa');
        if (btnDuplicar) {
            duplicarMesa(btnDuplicar.closest('.mesa-row'));
        }
    });

    prepararFilasIniciales();
}());
