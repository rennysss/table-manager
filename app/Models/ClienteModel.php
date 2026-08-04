<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Perfil de huésped identificado por email (tags entre visitas).
 */
class ClienteModel extends Model
{
    protected $table            = 'tm_clientes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['email', 'nombre', 'telefono', 'tags_json'];
    protected $useTimestamps    = true;

    public static function normalizarEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    public function porEmail(string $email): ?array
    {
        $email = self::normalizarEmail($email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $this->where('email', $email)->first();
    }

    /**
     * Mapa email → tags (array) para un lote de correos.
     *
     * @param  list<string> $emails
     * @return array<string, list<array<string, mixed>>>
     */
    public function tagsPorEmails(array $emails): array
    {
        $normalizados = [];
        foreach ($emails as $email) {
            $e = self::normalizarEmail($email);
            if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $normalizados[$e] = true;
            }
        }

        if ($normalizados === []) {
            return [];
        }

        $filas = $this->whereIn('email', array_keys($normalizados))->findAll();
        $mapa = [];

        foreach ($filas as $fila) {
            $mapa[$fila['email']] = $this->decodificarTags($fila['tags_json'] ?? null);
        }

        return $mapa;
    }

    /**
     * Crea o actualiza el perfil y guarda los tags del catálogo.
     *
     * @param  list<array<string, mixed>> $tags
     */
    public function upsertTags(string $email, array $tags, array $extra = []): ?array
    {
        $email = self::normalizarEmail($email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $payload = [
            'email'     => $email,
            'tags_json' => json_encode(array_values($tags), JSON_UNESCAPED_UNICODE),
        ];

        if (! empty($extra['nombre'])) {
            $payload['nombre'] = (string) $extra['nombre'];
        }
        if (array_key_exists('telefono', $extra) && $extra['telefono'] !== null && $extra['telefono'] !== '') {
            $payload['telefono'] = (string) $extra['telefono'];
        }

        $existente = $this->porEmail($email);
        if ($existente) {
            $this->update($existente['id'], $payload);

            return $this->find($existente['id']);
        }

        $this->insert($payload);

        return $this->find($this->getInsertID());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function decodificarTags(mixed $tagsJson): array
    {
        if ($tagsJson === null || $tagsJson === '') {
            return [];
        }

        if (is_array($tagsJson)) {
            return array_values($tagsJson);
        }

        $decoded = json_decode((string) $tagsJson, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }
}
