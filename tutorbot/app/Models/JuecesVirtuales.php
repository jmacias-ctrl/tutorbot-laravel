<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\EnvioSolucionProblema;
class JuecesVirtuales extends Model
{
    use HasFactory;

    public static function generateBodyRequest(JuecesVirtuales $juez){
        if($juez->autenticacion == "x-auth-token"){
            return [
                'x-auth-key' => $juez->api_token,
            ];
        }else{
            return [
                'x-rapidapi-host' => $juez->host,
                'x-rapidapi-key' => $juez->api_token
                ];
        }
    }

    public function envios(): HasMany
    {
        return $this->hasMany(EnvioSolucionProblema::class, 'id_juez');
    }
}