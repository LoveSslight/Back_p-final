<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $table = 'mesas';

    protected $fillable = [
        'numero',
        'capacidad',
        'estado',
    ];

    protected $casts = [
        'capacidad' => 'integer',
    ];

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'mesa_id');
    }

    public function estaDisponible(): bool
    {
        return $this->estado === 'disponible';
    }

    public function estaFueraDeServicio(): bool
    {
        return $this->estado === 'fuera_servicio';
    }
}
