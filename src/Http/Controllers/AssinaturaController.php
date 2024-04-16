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
            $assinatura = Assinatura::where('email',$email)->whereIsNull('data_assinatura')->get();
            if ($assinatura->isEmpty()) {
                return Redirect::back()
                            ->withErrors(['assinatura' => 'Não há assinaturas pendentes para o e-mail '.$email])
                            ->withInput();
            }
            $this->email = $email;
            return view('assinatura::form_check_assinatura',['assinaturas' => $assinatura]);
        }

        /**
         * Retona a última versão do arquivo assinado
         * @param BigInteger idArquivo Id do arquivo a ser baixado
         * 
         * @return \Illuminate\Http\Response
         */
        public function obterArquivoAssinado($idArquivo) {
            $assinatura = Assinatura::where('arquivo_id',$idArquivo)->whereNotNull('path_arquivo_assinado')->orderBy('data_assinatura','asc')->get();
            $arquivos = $assinatura->fisrt()->arquivos();
            return Storage::download($assinatura->first()->path_arquivo_assinado, $arquivos->fisrt()->original_name);
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
         * @return String
         */
        public function geraAssinatura(Int $idArquivo, String $email) {
            $arquivo = Arquivo::with('assinaturas')->where('id',$idArquivo)->get();
            if ($arquivo->isEmpty()) {
                return 'Arquivo informado não encontrado';
            }
            $this->documento= $arquivo->path_arquivo;
            $this->email    = $email;

            $assinatura = Assinatura::where('arquivo_id',$idArquivo)->where('email',$email)->get();
            if ($assinatura->isEmpty()) {
                return 'E-mail '.$email.' não encontrado como assinante deste arquivo';
            }

            if (!empty($assinatura->first()->data_assinatura)) {
                return 'Documento já assinado por '.$assinatura->first()->nome;
            }

            if ($assinatura->first()->ordem_assinatura > 1) {
                $assOrdem = Assinatura::where('arquivo_id',$idArquivo)
                                        ->where('ordem_assinatura','<>', $assinatura->first()->ordem_assinatura)
                                        ->orderBy('ordem_assinatura')
                                        ->get();
                
                $assOrdem->each(function ($item, $key) {
                    if (empty($item['data_assinatura'])) {
                        return 'Documento não pode ser assinado antes de '.$item['nome'].' assinar';
                    }
                });
                
            } 
            if (!empty($assinatura->first()->codpes) && !Auth::check()) {
                return 'Para assinar é preciso estar logado no sistema';

            } elseif (!empty($assinatura->first()->codpes) && auth()->user()->codpes <> $assinatura->first()->codpes) {
                return 'O usuário precisa estar logado com o mesmo número USP do assinante registrado';
            } else {
                //Aqui precisa implementar o retorno do envio do e-mail com a URL temporária
                
                return 'Aguardando implementação de URL temporária';
            }

            $dataAssinatura = date_create(date('Y-m-d H:i:s'));
            $txtAssinatura  = null;
            $nomeArquivoAss = "html".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";
            $nomeArquivo    = "doc".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";
            $mensagem       = null;
            $data           = array();

            $txtAssinatura.= $this->geraTxtAssinatura($idArquivo);
            $data = ['codigo_validacao' => $assinatura->first()->codigo_validacao
                    ,'nomeUsuario'      => $assinatura->first()->nome
                    ,'nusp'             => $assinatura->first()->codpes
                    ,'email'            => $assinatura->first()->email
                    ,'dataAss'          => date_create($dataAssinatura)->format('d/m/Y H:i:s')
                    ];
            $txtAssinatura.= view('assinatura::pdf._partials.linha-assinatura',$data);
            $txt = view('assinatura::pdf.assinatura', $data);

            $urlArquivoAssinado = $this->geraArquivoAssinatura($txt.$txtAssinatura,$nomeArquivoAss,$nomeArquivo);

            $assinatura->first()->path_arquivo_assinado = $urlArquivoAssinado;
            $assinatura->first()->dataAssinatura        = $dataAssinatura;
            $assinatura->first()->update();

            return "Documento(s) assinado(s) por ".$assinatura->first()->nome;
        }

        /**
        * Gera a assinaturas em lote através de formulário POST
        * @param \Illuminate\Http\Request $request
        *
        * @return \Illuminate\Http\Response
        * 
        */
        public function geraAssinaturas(Request $request) {

            $rule = ['arq_assinatura', 'required' //este campo pode ser numérico ou array
                    ,'email', 'required|email'];
            $msg = ['require' => 'Campo :attribute precisa ser informado'];

            $request->validate($rule, $msg);

            $arr_arquivos = array();

            if (!is_array($request->input('arq_assinatura'))) {
                if (is_numeric($request->input('arq_assinatura')) && $request->input('arq_assinatura') > 0) {
                    $arr_arquivos = [$request->input('arq_assinatura')];
                } else {
                    return Redirect::back()
                                ->withErrors(['assinatura' => '1.Arquivo não informado'])
                                ->withInput();
                }
            } else {
                $arr_arquivos = $request->input('arq_assinatura');
            }

            if (count($arr_arquivos) == 0) {
                return Redirect::back()
                                ->withErrors(['assinatura' => '2.Arquivo não informado'])
                                ->withInput();
            }

            foreach ($arr_arquivos as $idArquivo) { 
                $mensagem = $this->geraAssinatura($idArquivo,$request->input('email'));
            }
            //return redirect()->action([UserController::class, 'index']);
            return Redirect::back()
                            ->with(['assinatura' => $mensagem]);
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
                                            ,'dataAss'          => date_create($doc->dataAssinatura)->format('d/m/Y H:i:s')
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
            $pdf->save("upload/assinaturas/html/".$nmArquivo);

            $oMerger = PDFMerger::init();

            $oMerger->addPDF($this->documento,'all');
            $oMerger->addPDF("upload/assinaturas/html/".$nmArquivo, 'all');

            $oMerger->merge();
            $oMerger->save("upload/assinaturas/docAssinado/".$nomeDocAssinado);

            File::delete("upload/assinaturas/html/".$nmArquivo);

            //return env("APP_URL")."/upload/assinaturas/docAssinado/".$nomeDocAssinado;
            return "upload/assinaturas/docAssinado/".$nomeDocAssinado;
        }
}
