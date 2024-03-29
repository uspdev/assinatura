<?php

namespace Uspdev\Assinatura\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assinatura extends Model
{
    use HasFactory;

    /**
     * Consultar Grupo de assinantes
     */
    public function assinantes()
    {
        //return $this->hasMany(Comment::class, 'foreign_key', 'local_key');
        return $this->hasMany(GrupoAssinaturas::class,'grupo_assinaturas_id');
    }

}