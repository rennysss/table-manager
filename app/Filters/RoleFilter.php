<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro RBAC: valida que el rol del usuario esté en la lista permitida.
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($arguments === null || $arguments === []) {
            return null;
        }

        $rol = session()->get('rol');

        if (! in_array($rol, $arguments, true)) {
            if ($request->isAJAX() || str_starts_with($request->getPath(), 'api/')) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['error' => 'Acceso denegado']);
            }

            return redirect()->to('/dashboard')
                ->with('error', 'No tienes permiso para acceder a esta sección.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
