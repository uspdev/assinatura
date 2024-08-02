<?php

namespace Uspdev\Assinatura\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

use Uspdev\Assinatura\Observers\AssinaturaObserver;

class Assinatura extends Model
{
    use HasFactory;
    protected $filled = ['nome','email','codpes','ordem_assinatura'];

    /**
     * Consultar Grupo de assinantes
     */
    public function arquivos()
    {
        //return $this->belongsTo(Tenant::class'foreign_key', 'owner_key');
        return $this->belongsTo(Arquivo::class,'arquivo_id','id');
    }

}