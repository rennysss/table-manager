<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Reservas extends BaseConfig
{
    /** Base URL de OneReservations API v1 */
    public string $apiUrl = 'https://one-reservations.com/app-reservas-ws/v1';

    /** API Key (header X-API-Key). Solo backend — nunca exponer al navegador. */
    public string $apiKey = '';

    public function __construct()
    {
        parent::__construct();

        $url = env('reservas.apiUrl', '');
        if ($url !== '') {
            $this->apiUrl = $url;
        }

        // Compatibilidad con nombre anterior en .env
        $this->apiKey = env('reservas.apiKey', env('reservas.apiToken', ''));
    }
}
