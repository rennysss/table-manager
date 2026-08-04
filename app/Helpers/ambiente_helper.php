<?php

use App\Models\AmbienteModel;

/**
 * Opciones de ambientes para selects (desde BD).
 */
function ambientes_opciones(bool $soloActivos = true): array
{
    return model(AmbienteModel::class)->opcionesSelect($soloActivos);
}
