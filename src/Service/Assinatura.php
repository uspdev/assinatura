<?php

namespace Uspdev\Assinatura\Service;

use Uspdev\Assinatura\Models\Assinatura as AssinaturaModel;
use Uspdev\Assinatura\Models\Arquivo;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class Assinatura {

    public static function create(string $arquivo, array $assinaturas){


        $file = new File($arquivo);

        // salvando arquivo
        $path = Storage::putFile(config('assinatura.localArquivo'),$file);


        // gravando na tabela arquivos
        $arquivo = new Arquivo();
        $arquivo->path_arquivo = $path;
        $arquivo->original_name = basename($file);
        $arquivo->save();

        // Salvando as assinaturas

        foreach($assinaturas as $assinatura){
            $assinatura_model = new AssinaturaModel;
            $assinatura_model->arquivo_id = $arquivo->id;
            $assinatura_model->nome = $assinatura['nome'];
            $assinatura_model->email = $assinatura['email'];

            if (array_key_exists('codpes', $assinatura)) {
                $assinatura_model->codpes = $assinatura['codpes'];
            }

            if (array_key_exists('ordem_assinatura', $assinatura)) {
                $assinatura_model->ordem_assinatura = $assinatura['ordem_assinatura'];
            }

            $assinatura_model->save();
        }



    }

}