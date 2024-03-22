<?php

namespace Uspdev\Assinatura\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arquivos extends Model
{
    use HasFactory;

    /**
     * Assinantes
     */
    public function assinantes()
    {
        //return $this->belongsToMany(Role::class, 'tabela_intermediaria', 'fk', 'owner_fk');
        return $this->belongsToMany(Assinantes::class,'grupo_assinanturas');
    }
}
