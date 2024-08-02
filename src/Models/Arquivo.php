<?php

namespace Uspdev\Assinatura\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arquivo extends Model
{
    use HasFactory;
    protected $filled = ['path_arquivo', 'original_name'];

    /**
     * Assinaturas
     */
    public function assinaturas()
    {
        //return $this->hasMany(Rent::class, "foreign_key", "local_key");
        return $this->hasMany(Assinatura::class,'arquivo_id','id');
    }
}
