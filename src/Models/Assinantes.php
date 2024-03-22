<?php

namespace Uspdev\Assinatura\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assinantes extends Model
{
    use HasFactory;

    /**
     * Arquivos
     */
    public function arquivos()
    {
        //return $this->belongsToMany(Role::class, 'tabela_intermediaria', 'fk', 'owner_fk');
        return $this->belongsToMany(Arquivos::class,'grupo_assinanturas');
    }

    /**
     * Grupo Assinatura
     */
    public function grupo_assinaturas() 
    {
        //return $this->hasMany(Comment::class, 'foreign_key', 'local_key');
        return $this->hasMany(GrupoAssinaturas::class,'grupo_assinaturas_id');
    }
}
