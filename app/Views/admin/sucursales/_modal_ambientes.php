<?php /** Modal: catálogo global de ambientes */ ?>
<p class="text-muted mb-3 small">
    Los ambientes se administran en <strong>Configuraciones → Ambientes</strong>.
</p>

<div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ambientes)): ?>
            <tr><td colspan="2" class="text-muted text-center py-3">Sin ambientes registrados.</td></tr>
            <?php else: ?>
            <?php foreach ($ambientes as $a): ?>
            <tr>
                <td><?= esc($a['descripcion']) ?></td>
                <td><?= esc($a['estatus']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
