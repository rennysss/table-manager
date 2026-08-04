<?php

namespace App\Models;

use CodeIgniter\Model;

class TagCategoriaModel extends Model
{
    protected $table            = 'tm_tag_categorias';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'nombre',
        'alcance',
        'mostrar_en_reserva',
        'mostrar_en_chit',
        'orden',
        'estatus',
        'created_at',
    ];
    protected $useTimestamps = false;

    /**
     * Categorías activas ordenadas, con sus tags.
     *
     * @return list<array<string, mixed>>
     */
    public function conTags(): array
    {
        $categorias = $this->orderBy('orden', 'ASC')
            ->orderBy('nombre', 'ASC')
            ->findAll();

        $tagModel = model(TagModel::class);
        $tags = $tagModel->orderBy('orden', 'ASC')->orderBy('nombre', 'ASC')->findAll();

        $porCategoria = [];
        foreach ($tags as $tag) {
            $cid = (int) ($tag['categoria_id'] ?? 0);
            $porCategoria[$cid][] = $tag;
        }

        foreach ($categorias as &$cat) {
            $cat['tags'] = $porCategoria[(int) $cat['id']] ?? [];
        }
        unset($cat);

        return $categorias;
    }

    /**
     * Subtítulo de categoría: "Global, Mostrar en reserva, Mostrar en comanda".
     */
    public static function subtitulo(array $categoria): string
    {
        $partes = [
            ($categoria['alcance'] ?? 'global') === 'local' ? 'Local' : 'Global',
        ];

        if (! empty($categoria['mostrar_en_reserva'])) {
            $partes[] = 'Mostrar en reserva';
        }
        if (! empty($categoria['mostrar_en_chit'])) {
            $partes[] = 'Mostrar en comanda';
        }

        return implode(', ', $partes);
    }
}
