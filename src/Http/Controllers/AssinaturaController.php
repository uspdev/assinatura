<?php

namespace Uspdev\Assinatura\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

use Barryvdh\DomPDF\Facade\Pdf;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

use Uspdev\Assinatura\Models\Assinatura;
use Uspdev\Assinatura\Models\Arquivo;

class AssinaturaController extends Controller
{
    protected $email;
    protected $documento;

    /**
     * Gera formulário para mostrar assinaturas pendentes
     * @param String email E-mail da pessoa que vai assinar o documento
     */
    public function formCheckAssinatura($email) {
        $assinatura = Assinatura::with('arquivos')->where('email',$email)->whereNull('data_assinatura')->get();
        $this->email = $email;
        if ($assinatura->isNotEmpty()) $this->documento = Storage::path(config('assinatura.localArquivo')."/".$assinatura->first()->arquivos->path_arquivo);
        return view('assinatura::form_check_assinatura',['assinaturas' => $assinatura, 'email' => $email]);
    }

    /**
     * Retona a URL da última versão do arquivo assinado
     * @param BigInteger idArquivo Id do arquivo a ser baixado
     * 
     * @return \Illuminate\Http\Response
     */
    public function obterUrlArquivoAssinado($idArquivo) {
        $assinatura = Assinatura::where('arquivo_id',$idArquivo)->whereNotNull('path_arquivo_assinado')->orderBy('data_assinatura','desc')->get();
        return Storage::url($assinatura->first()->path_arquivo_assinado, $assinatura->first()->arquivos->first()->original_name);
    }

    /**
     * Retona a última versão do arquivo assinado
     * @param BigInteger idArquivo Id do arquivo a ser baixado
     * 
     * @return \Illuminate\Http\Response
     */
    public function obterArquivoAssinado($idArquivo) {
        $assinatura = Assinatura::where('arquivo_id',$idArquivo)->whereNotNull('path_arquivo_assinado')->orderBy('data_assinatura','desc')->get();
        return Storage::download($assinatura->first()->path_arquivo_assinado, $assinatura->first()->arquivos->first()->original_name);
    }

    /**
     * Método que carrega formulário com lista de arquivos assinados por determinado assinante
     * @param String email E-mail do assinante
     * 
     * @return \Illuminate\Http\Response
     */
    public function listaArquivosAssinados(String $email) {
        $assinaturas = Assinatura::with('arquivos')
                                ->where('email',$email)
                                ->whereNotNull('data_assinatura');

        return view('assinatura::form_check_assinatura',['assinaturas' => $assinaturas]);
    }

    /**
     * Gera cada uma das assinaturas
     * @param Int $id_arquivo Id do arquivo a ser assinado
     * @param String $email E-mail do assinante
     * 
     * @return \Illuminate\Http\Response
     */
    public function geraAssinatura(Request $request) {
        
        $regra = [
            'arq_assinatura' => 'required|array',
            'codigo_validacao1' => 'array|required',
            'codigo_validacao2' => 'array|required',
            'codigo_validacao3' => 'array|required',
            'codigo_validacao4' => 'array|required'
        ];

        $msg = [
            'required' => 'O campo :attribute deve ser informado',
            'max' => 'O campo :attribute deve conter até :max caracteres'
        ];
        
        $request->validate($regra,$msg);
        $msg_retorno = null;

        foreach($request->arq_assinatura as $key=>$idArquivo) {
            
            $arquivo = Arquivo::with('assinaturas')->where('id',$idArquivo)->get();
            if ($arquivo->isEmpty()) {
                $msg_retorno.='Arquivo informado não encontrado /n';
                continue;
            }
            $arquivo = $arquivo->first();
            $this->documento= Storage::path(config('assinatura.localArquivo')."/".$arquivo->path_arquivo);
            $this->email    = $request->email;

            $assinatura = Assinatura::where('arquivo_id',$idArquivo)->where('email',$request->email)->get();
            if ($assinatura->isEmpty()) {
                $msg_retorno.= 'E-mail '.$request->email.' não encontrado como assinante do arquivo '.$arquivo->path_arquivo;
                continue;
            }

            $codigo_validacao = $request->codigo_validacao1[$key]."-".$request->codigo_validacao2[$key]."-".$request->codigo_validacao3[$key]."-".$request->codigo_validacao4[$key];
            if ($codigo_validacao != $assinatura->first()->codigo_validacao) {
                $msg_retorno.= "Reveja o código de validação $codigo_validacao do arquivo ".$arquivo->path_arquivo.", clique no link e tente novamente /n";
                continue;
            }

            if (!empty($assinatura->first()->data_assinatura)) {
                $msg_retorno.= 'Documento '.$arquivo->path_arquivo.' já assinado por '.$assinatura->first()->nome.' /n';
                continue;
            }

            if ($assinatura->first()->ordem_assinatura > 1) {
                $assOrdem = Assinatura::where('arquivo_id',$idArquivo)
                                        ->where('ordem_assinatura','<>', $assinatura->first()->ordem_assinatura)
                                        ->whereNull('data_assinatura')
                                        ->orderBy('ordem_assinatura')
                                        ->get();

                if ($assOrdem->isNotEmpty() && $assOrdem->first()->ordem_assinatura < $assinatura->first()->ordem_assinatura) {
                    $msg_retorno.= 'Documento não pode ser assinado antes de '.$assOrdem->first()->nome.' assinar /n';
                }                    
            } 
            if (!empty($assinatura->first()->codpes) && !Auth::check()) {
                $msg_retorno.= 'Para assinar o documento '.$arquivo->path_arquivo.' é preciso estar logado no sistema /n';
                continue;
            } elseif (!empty($assinatura->first()->codpes) && auth()->user()->codpes <> $assinatura->first()->codpes) {
                $msg_retorno.= 'Para assinar o documento '.$arquivo->path_arquivo.' o usuário precisa estar logado com o mesmo número USP do assinante registrado /n';
                continue;
            } elseif ($request->email != $assinatura->first()->email) {
                $msg_retorno.= 'Para assinar o documento '.$arquivo->path_arquivo.' o e-mail informado deve ser o mesmo do assinante registrado /n';
                continue;
            } 

            $dataAssinatura = date_create('now');
            $txtAssinatura  = null;
            $nomeArquivoAss = "html".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";
            $nomeArquivo    = "doc".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";
            $data           = array();

            $txtAssinatura.= $this->geraTxtAssinatura($idArquivo);
            $data = ['codigo_validacao' => $assinatura->first()->codigo_validacao
                    ,'nomeUsuario'      => $assinatura->first()->nome
                    ,'nusp'             => $assinatura->first()->codpes
                    ,'email'            => $assinatura->first()->email
                    ,'dataAss'          => $dataAssinatura->format('d/m/Y H:i:s')
                    ];
            $txtAssinatura.= view('assinatura::pdf._partials.linha-assinatura',$data);
            $txt = view('assinatura::pdf.assinatura', $data);
            
            $urlArquivoAssinado = $this->geraArquivoAssinatura($txt.$txtAssinatura,$nomeArquivoAss,$nomeArquivo);

            $assinatura->first()->path_arquivo_assinado = $urlArquivoAssinado;
            $assinatura->first()->data_assinatura       = $dataAssinatura;
            $assinatura->first()->update();

            $msg_retorno.="Documento $arquivo->path_arquivo assinado por ".$assinatura->first()->nome;
        }

        $assinaturas = Assinatura::with('arquivos')
                                ->where('email',$request->email)
                                ->whereNotNull('data_assinatura');

        return view('assinatura::lista_assinatura',['assinaturas'=>$assinaturas, 'msg'=>$msg_retorno]);
    }

    /**
     * Método para gerar texto com grupo de assinaturas
     * @param String idGrupo - código do grupo de assinaturas
     * @return String
     */
    private function geraTxtAssinatura($idArquivo) {
        $txtAssinatura = null;

        $documento = Assinatura::where('arquivo_id',$idArquivo)
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
                                        ,'dataAss'          => date_create($doc->data_assinatura)->format('d/m/Y H:i:s')
                                        ]);
        }

        return $txtAssinatura;
    }

    /**
     * Gera o arquivo assinado
     * @param String htmlAssinatura contém HTML da linha da assinatura
     * @param String nmArquivo nome do arquivo intermediário
     * @param String nomeDocAssinado nome do arquivo do documento assinado
     * 
     * @return String path do arquivo assinado.
     */
    private function geraArquivoAssinatura($htmlAssinatura,$nmArquivo,$nomeDocAssinado) {
        $pdf = Pdf::loadHTML($htmlAssinatura);                             
        $pdf->save(Storage::path(config('assinatura.localArquivo')."/"."html/".$nmArquivo));

        $oMerger = PDFMerger::init();

        $oMerger->addPDF($this->documento,'all');
        $oMerger->addPDF(Storage::path(config('assinatura.localArquivo')."/"."html/".$nmArquivo, 'all'));

        $oMerger->merge();
        $oMerger->save(Storage::path(config('assinatura.localArquivo')."/"."docAssinado/".$nomeDocAssinado));

        File::delete(Storage::path(config('assinatura.localArquivo')."/"."html/".$nmArquivo));

        //return env("APP_URL")."/upload/assinaturas/docAssinado/".$nomeDocAssinado;
        return config('assinatura.localArquivo')."/"."docAssinado/".$nomeDocAssinado;
    }
}
