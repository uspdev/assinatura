<?php

namespace Uspdev\Assinatura\Service;

use Uspdev\Assinatura\Models\Assinatura as AssinaturaModel;
use Uspdev\Assinatura\Models\Arquivo;

use Barryvdh\DomPDF\Facade\Pdf;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

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
        return $arquivo->id;
    }

    public static function arquivoInfo(Int $idArquivo) {
        return Arquivo::with('assinaturas')->where('id',$idArquivo)->first();
    }

    public static function geraAssinatura(Int $idArquivo, String $email) 
    {
        $arquivo = Arquivo::with('assinaturas')->where('id',$idArquivo)->get();
        if ($arquivo->isEmpty()) {
            return 'Arquivo informado não encontrado';
        }

        $assinatura = AssinaturaModel::where('arquivo_id',$idArquivo)->where('email',$email)->get();
        if ($assinatura->isEmpty()) {
            return 'E-mail '.$email.' não encontrado como assinante deste arquivo';
        }

        $assinatura = $assinatura->first();
        if (!empty($assinatura->data_assinatura)) {
            return 'Documento já assinado por '.$assinatura->nome;
        }

        if ($assinatura->ordem_assinatura > 1) {
            $assOrdem = AssinaturaModel::where('arquivo_id',$idArquivo)
                                        ->where('ordem_assinatura','<', $assinatura->ordem_assinatura)
                                        ->whereNull('data_assinatura')
                                        ->orderBy('ordem_assinatura')
                                        ->get();
                
            $assOrdem->each(function ($item, $key) {
                return 'Documento não pode ser assinado antes de '.$item['nome'].' assinar';
            });
        } 

        if (!empty($assinatura->codpes) && auth()->check()) {
            return 'Para assinar é preciso estar logado no sistema';
        } elseif (!empty($assinatura->codpes) && auth()->user()->codpes <> $assinatura->codpes) {
            return 'O usuário precisa estar logado com o mesmo número USP do assinante registrado';
        } else {
            //Aqui precisa implementar o retorno do envio do e-mail com a URL temporária
            
            return 'Aguardando implementação de URL temporária';
        }

        $dataAssinatura = new \DateTime('now');
        $txtAssinatura  = null;
        $nomeArquivoAss = "html".$email."-".date_timestamp_get($dataAssinatura).".pdf";
        $nomeArquivo    = "doc".$email."-".date_timestamp_get($dataAssinatura).".pdf";
        $data           = array();

        $txtAssinatura.= Assinatura::geraTxtAssinatura($idArquivo);
        $data = ['codigo_validacao' => $assinatura->codigo_validacao
                ,'nomeUsuario'      => $assinatura->nome
                ,'nusp'             => $assinatura->codpes
                ,'email'            => $assinatura->email
                ,'dataAss'          => $dataAssinatura->format('d/m/Y H:i:s')
                ];
        
        $txtAssinatura.= view('assinatura::pdf._partials.linha-assinatura',$data);
        $txt = view('assinatura::pdf.assinatura', $data);

        $urlArquivoAssinado = Assinatura::geraArquivoAssinatura($arquivo->path_arquivo,$txt.$txtAssinatura,$nomeArquivoAss,$nomeArquivo);

        $assinatura->path_arquivo_assinado = $urlArquivoAssinado;
        $assinatura->dataAssinatura        = $dataAssinatura;
        $assinatura->update();

        return "Documento(s) assinado(s) por ".$assinatura->first()->nome;
    }

    /**
     * Método para gerar texto com grupo de assinaturas
     * @param String idGrupo - código do grupo de assinaturas
     * @return String
     */
    private static function geraTxtAssinatura($idArquivo) {
        $txtAssinatura = null;

        $documento = AssinaturaModel::where('arquivo_id',$idArquivo)
                                    ->whereNotNull('data_assinatura')
                                    ->orderBy('ordem_assinatura')
                                    ->orderBy('data_assinatura','desc')
                                    ->get();

        foreach ($documento as $doc) {
                $txtAssinatura.= view('assinatura::pdf._partials.linha-assinatura', 
                                        ['codigo_validacao' => $doc->codigoValidacao
                                        ,'nomeUsuario'      => $doc->nome
                                        ,'nusp'             => $doc->codpes
                                        ,'email'            => $doc->email
                                        ,'dataAss'          => date_create($doc->dataAssinatura)->format('d/m/Y H:i:s')
                                        ]);
        }

        return $txtAssinatura;
    }

    /**
     * Gera o arquivo assinado
     * @param String path do arquivo
     * @param String htmlAssinatura contém HTML da linha da assinatura
     * @param String nmArquivo nome do arquivo intermediário
     * @param String nomeDocAssinado nome do arquivo do documento assinado
     * 
     * @return String path do arquivo assinado.
     */
    private function geraArquivoAssinatura($pathArquivo,$htmlAssinatura,$nmArquivo,$nomeDocAssinado) {
        $pdf = Pdf::loadHTML($htmlAssinatura);                             
        $pdf->save("upload/assinaturas/html/".$nmArquivo);

        $oMerger = PDFMerger::init();

        $oMerger->addPDF($pathArquivo,'all');
        $oMerger->addPDF("upload/assinaturas/html/".$nmArquivo, 'all');

        $oMerger->merge();
        $oMerger->save("upload/assinaturas/docAssinado/".$nomeDocAssinado);

        Storage::delete("upload/assinaturas/html/".$nmArquivo);

        //return env("APP_URL")."/upload/assinaturas/docAssinado/".$nomeDocAssinado;
        return "upload/assinaturas/docAssinado/".$nomeDocAssinado;
    }

}