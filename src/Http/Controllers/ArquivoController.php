<?php

namespace Uspdev\Assinatura\Http\Controllers;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

use Uspdev\Assinatura\Models\Arquivo;

class ArquivoController extends Controller
{
    /**
     * Método para download do arquivo
     */
    public function show($id) {

        $arquivo = Arquivo::find($id);
        Storage::download(config('assinatura.localArquivo')."/".$arquivo->path_arquivo);

    }
    
}
