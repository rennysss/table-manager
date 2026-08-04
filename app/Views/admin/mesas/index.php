<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
// Separa ambientes globales (sucursal_id NULL) de los propios de la sucursal
$ambientesGlobales = [];
$ambientesSucursal = [];
foreach ($ambientes as $a) {
    if ($a['sucursal_id'] === null) {
        $ambientesGlobales[] = $a;
    } else {
        $ambientesSucursal[] = $a;
    }
}

// Genera el <select> de ambientes (con optgroups) marcando el seleccionado
$opcionesAmbientes = static function (?int $seleccionado = null) use ($ambientesGlobales, $ambientesSucursal): string {
    $opcion = static function (array $a) use ($seleccionado): string {
        $sel = ((int) $a['id'] === (int) $seleccionado) ? ' selected' : '';
        return '<option value="' . (int) $a['id'] . '"' . $sel . '>' . esc($a['descripcion']) . '</option>';
    };

    $html = '';
    if (! empty($ambientesGlobales)) {
        $html .= '<optgroup label="Globales">';
        foreach ($ambientesGlobales as $a) {
            $html .= $opcion($a);
        }
        $html .= '</optgroup>';
    }
    if (! empty($ambientesSucursal)) {
        $html .= '<optgroup label="De la sucursal">';
        foreach ($ambientesSucursal as $a) {
            $html .= $opcion($a);
        }
        $html .= '</optgroup>';
    }

    return $html;
};
?>
<div class="container-fluid py-4" id="mesasEditor"
     data-url-guardar="<?= base_url("admin/sucursales/{$sucursal['id']}/mesas/guardar-lote") ?>"
     data-url-ambiente="<?= base_url("admin/sucursales/{$sucursal['id']}/mesas/ambiente") ?>">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent px-0">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/sucursales') ?>">Sucursales</a></li>
            <li class="breadcrumb-item active">Mesas — <?= esc($sucursal['nombre']) ?></li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0 text-uppercase">
            Editar mesas <span class="text-muted">(<span class="mesas-count"><?= count($mesas) ?></span>)</span>
        </h1>
        <div class="d-flex">
            <button type="button" class="btn btn-outline-secondary" id="btnNuevoAmbiente">
                + Ambiente
            </button>
        </div>
    </div>

    <?php if (empty($ambientes)): ?>
    <div class="alert alert-warning">
        No hay ambientes disponibles. Crea un ambiente global en
        <a href="<?= base_url('admin/ambientes') ?>">Configuraciones → Ambientes</a>
        o agrega uno propio de la sucursal con el botón «+ Ambiente».
    </div>
    <?php endif; ?>

    <div class="alert alert-danger d-none" id="mesasErrores" role="alert"></div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 mesas-table">
                <thead>
                    <tr>
                        <th style="width:22%">Mesa</th>
                        <th style="width:18%">Mini Pax</th>
                        <th style="width:18%">Max Pax</th>
                        <th style="width:28%">Ambiente</th>
                        <th style="width:14%" class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="mesasBody">
                    <?php foreach ($mesas as $m): ?>
                    <tr class="mesa-row" data-id="<?= (int) $m['id'] ?>">
                        <td>
                            <input type="text" class="form-control mesa-numero" value="<?= esc($m['numero']) ?>"
                                   maxlength="20" placeholder="N.º">
                        </td>
                        <td>
                            <input type="number" class="form-control mesa-minimo" value="<?= (int) $m['minimo'] ?>" min="1">
                        </td>
                        <td>
                            <input type="number" class="form-control mesa-maximo" value="<?= (int) $m['maximo'] ?>" min="1">
                        </td>
                        <td>
                            <select class="form-control mesa-ambiente">
                                <?= $opcionesAmbientes((int) $m['ambiente_id']) ?>
                            </select>
                        </td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-mesa"
                                    title="Eliminar" aria-label="Eliminar mesa">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-duplicar-mesa"
                                    title="Duplicar" aria-label="Duplicar mesa">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-2">
        <button type="button" class="btn btn-link mesas-add-link p-0" id="btnAgregarMesa">
            Agregar mesa
        </button>
        <span class="text-muted ml-2">(<span class="mesas-count"><?= count($mesas) ?></span> mesas)</span>
    </div>

    <p class="text-muted small mt-2 <?= empty($mesas) ? '' : 'd-none' ?>" id="mesasVacio">
        Aún no hay mesas en esta sucursal. Usa «Agregar mesa» para empezar.
    </p>

    <div class="mt-4">
        <button type="button" class="btn btn-primary px-4" id="btnGuardarMesas">
            Guardar
        </button>
    </div>
</div>

<template id="mesaRowTemplate">
    <tr class="mesa-row" data-id="">
        <td>
            <input type="text" class="form-control mesa-numero" value="" maxlength="20" placeholder="N.º">
        </td>
        <td>
            <input type="number" class="form-control mesa-minimo" value="4" min="1">
        </td>
        <td>
            <input type="number" class="form-control mesa-maximo" value="8" min="1">
        </td>
        <td>
            <select class="form-control mesa-ambiente">
                <?= $opcionesAmbientes() ?>
            </select>
        </td>
        <td class="text-right text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-mesa"
                    title="Eliminar" aria-label="Eliminar mesa">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary btn-duplicar-mesa"
                    title="Duplicar" aria-label="Duplicar mesa">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
            </button>
        </td>
    </tr>
</template>

<div class="modal fade" id="modalAmbiente" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content app-modal-content">
            <div class="modal-header app-modal-header">
                <h5 class="modal-title">Nuevo ambiente de la sucursal</h5>
                <button type="button" class="close app-modal-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-0">
                    <label for="ambienteDescripcion">Descripción *</label>
                    <input type="text" class="form-control" id="ambienteDescripcion" maxlength="150"
                           placeholder="Ej. Terraza, VIP, Barra">
                    <div class="invalid-feedback d-block d-none" id="ambienteError"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarAmbiente">Crear ambiente</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/mesas-editor.js') ?>"></script>
<?= $this->endSection() ?>
