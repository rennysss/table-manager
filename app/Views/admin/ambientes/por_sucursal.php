<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendor/switchery/switchery.min.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4" id="ambientesEditor"
     data-url-guardar="<?= base_url("admin/sucursales/{$sucursal['id']}/ambientes/guardar-lote") ?>">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent px-0">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/sucursales') ?>">Sucursales</a></li>
            <li class="breadcrumb-item active">Ambientes — <?= esc($sucursal['nombre']) ?></li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0 text-uppercase">
            Editar ambientes <span class="text-muted">(<span class="ambientes-count"><?= count($ambientes) ?></span>)</span>
        </h1>
    </div>

    <p class="text-muted small mb-3">
        Los ambientes <strong>globales</strong> se cargan por defecto; puedes desactivarlos solo para esta
        sucursal. También puedes agregar ambientes propios de la sucursal.
    </p>

    <div class="alert alert-danger d-none" id="ambientesErrores" role="alert"></div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 mesas-table">
                <thead>
                    <tr>
                        <th style="width:46%">Ambiente</th>
                        <th style="width:22%">Origen</th>
                        <th style="width:18%">Activo</th>
                        <th style="width:14%" class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="ambientesBody">
                    <?php foreach ($ambientes as $a): ?>
                    <tr class="ambiente-row" data-id="<?= (int) $a['id'] ?>" data-global="<?= $a['es_global'] ? '1' : '0' ?>">
                        <td>
                            <?php if ($a['es_global']): ?>
                                <input type="text" class="form-control ambiente-descripcion"
                                       value="<?= esc($a['descripcion']) ?>" readonly>
                            <?php else: ?>
                                <input type="text" class="form-control ambiente-descripcion"
                                       value="<?= esc($a['descripcion']) ?>" maxlength="150" placeholder="Descripción">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($a['es_global']): ?>
                                <span class="badge badge-secondary">Global</span>
                            <?php else: ?>
                                <span class="badge badge-info">De la sucursal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="checkbox" class="ambiente-activo js-switch" <?= $a['activo_sucursal'] ? 'checked' : '' ?>>
                        </td>
                        <td class="text-right text-nowrap">
                            <?php if (! $a['es_global']): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-ambiente"
                                    title="Eliminar" aria-label="Eliminar ambiente">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-2">
        <button type="button" class="btn btn-link mesas-add-link p-0" id="btnAgregarAmbiente">
            Agregar ambiente
        </button>
        <span class="text-muted ml-2">(<span class="ambientes-count"><?= count($ambientes) ?></span>)</span>
    </div>

    <div class="mt-4">
        <button type="button" class="btn btn-primary px-4" id="btnGuardarAmbientes">
            Guardar
        </button>
    </div>
</div>

<template id="ambienteRowTemplate">
    <tr class="ambiente-row" data-id="" data-global="0">
        <td>
            <input type="text" class="form-control ambiente-descripcion" value="" maxlength="150" placeholder="Descripción">
        </td>
        <td>
            <span class="badge badge-info">De la sucursal</span>
        </td>
        <td>
            <input type="checkbox" class="ambiente-activo js-switch" checked>
        </td>
        <td class="text-right text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-ambiente"
                    title="Eliminar" aria-label="Eliminar ambiente">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>
        </td>
    </tr>
</template>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/switchery/switchery.min.js') ?>"></script>
<script src="<?= base_url('assets/js/ambientes-sucursal.js') ?>"></script>
<?= $this->endSection() ?>
