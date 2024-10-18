<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Cursos;
use App\Models\Problemas;
use App\Models\banco_problemas_certamenes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certamenes extends Model
{
    use HasFactory;

    public static $rules = 
    [
        "nombre" => ["string", "required"],
        "descripcion" =>["string", "required","min:50"],
        "fecha_inicio" => ["date", "before_or_equal:fecha_termino", "required"],
        "fecha_termino" => ["date", "after_or_equal:fecha_inicio", "required"],
        "curso" => ["required", "numeric"],
        "penalizacion_error" => ["nullable", "min:0"]
    ];
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Cursos::class, 'id_curso');
    }

    public function problemas(): BelongsToMany
    {
        return $this->belongsToMany(Problemas::class, 'banco_problemas_certamenes', 'id_certamen', 'id_problema')->withTimestamps()->withPivot(['puntaje', 'id'])->using(banco_problemas_certamenes::class);
    }
}
