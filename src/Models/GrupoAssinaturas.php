<?php

namespace Uspdev\Assinatura\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoAssinaturas extends Model
{
    use HasFactory;
    protected $table = "grupo_assinaturas";

    /**
     * Pegar os assinantes que pertenem ao grupo
     */
    public function assinantes()
    {
        //return $this->belongsTo(Post::class, 'foreign_key', 'owner_key');
        return $this->belongsTo(Assinantes::class);
    }

    /**
     * Pegar os arquivos que pertencem ao grupo
     */
    public function arquivos()
    {
        //return $this->belongsTo(Post::class, 'foreign_key', 'owner_key');
        return $this->belongsTo(Arquivos::class);
    }
}
