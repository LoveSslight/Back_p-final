<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'mesa_id',
        'fecha',
        'hora',
        'subtotal',
        'total',
        'estado',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'total'    => 'float',
    ];

    // La tabla no tiene columna "cantidad_total": se calcula y se agrega al JSON
    protected $appends = ['cantidad_total'];

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'pedido_id');
    }

    public function getCantidadTotalAttribute(): int
    {
        return (int) $this->detalles->sum('cantidad');
    }
}
