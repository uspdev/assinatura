<?php

namespace Uspdev\Assinatura\Http\Controllers;

//use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

use Uspdev\Assinatura\Mail\NotificacaoAssinatura;

use Barryvdh\DomPDF\Facade\Pdf;
use Uspdev\Replicado\Pessoa;
use Uspdev\Assinatura\Models\Assinatura;
use Uspdev\Assinatura\Models\GrupoAssinaturas;
use Uspdev\Assinatura\Models\Assinantes;
use Uspdev\Assinatura\Models\Arquivos;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class AssinaturaChecker {

        protected $email;
        protected $documento;

        /**
         * Gera formulário para checar assinatura Externa
         */
        public function formCheckAssinatura($email = null) {
            $this->email = $email;
            return view('assinatura::form_check_assinatura',['email' => $this->email]);
        }

        /**
        * Checa a assinatura de pessoas com vínculo USP e envia e-mail de validação para pessoas externas
        * @param String documento path do arquivo a ser assinado
        * @param int idArquivo código do arquivo para busca de grupo de assinantes, pode conter uma ou mais pessoas.
        * @param int codpes número USP do assinante - opcional, deve ser informado caso não seja informado e-mail
        * @param String email e-mail do assinante - opcional, deve ser informado caso o n.º USP não seja
        * @param String nomeAssinante nome do assinante, deve ser informado para pessoas externas à USP
        * 
        */
        public function check($idArquivo, $email) {
            $this->email        = $email;

            $dataAssinatura     = date_create(date('Y-m-d H:i:s'));
            $txtAssinatura      = null;
            $pessoa             = array();
            $hash               = null;
            $nomeArquivoAss     = "html".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";
            $nomeArquivo        = "doc".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";

            $validacaoArquivo   = Arquivos::find($idArquivo);
            if (empty($validacaoArquivo->id)) {
                return "Arquivo não validado, verifique se a validação está sendo realizada no sistema de origem";
            }

            $this->documento    = $validacaoArquivo->path_original;            
            $assinante          = Assinantes::where('email',$this->email)->whereRelation('grupo_assinaturas','arquivo_id',$idArquivo);
            if ($assinante->isEmpty()) {
                return "Assinante não encontrado para documento informado";
            }

            $grupoAssinaturas   = GrupoAssinaturas::where('assinante_id',$assinante->fisrt()->id)->get();

            if (!$grupoAssinaturas->isEmpty()) {
                $hash = $this->buscaHashGrupo($idArquivo);
                
                if (Assinatura::where('grupo_assinaturas_id',$grupoAssinaturas->first()->id)->count() > 0){
                    return "Assinante ".$assinante->first()->nome." já assinou o documento";
                }

                $txtAssinatura.= $this->geraAssinaturasGrupo($idArquivo);
                $hash = empty($hash)?$this->geraHash():$hash;

                if (!empty($assinante->first()->codpes)) {
                    $pessoa = Pessoa::dump($assinante->first()->codpes);
                    if (count($pessoa)) {
                        if (Pessoa::email($pessoa['codpes']) != $this->email) {
                            return "E-mail informado para o assinante não é o mesmo cadastrado como principal nos sistemas USP";
                        }
                    } else {
                        return "Pessoa não encontrada na base USP";
                    }
                    
                    $data = ['codigo_validacao' => $hash
                            ,'nomeUsuario'      => $assinante->first()->nome
                            ,'nusp'             => $assinante->first()->codpes
                            ,'email'            => $assinante->first()->email
                            ,'dataAss'          => date_create($dataAssinatura)->format('d/m/Y H:i:s')
                            ];
                    
                    $txtAssinatura.= view('assinatura::pdf._partials.linha-assinatura',$data);
                    $txt = view('assinatura::pdf.assinatura', $data);
                                
                    $urlArquivoAssinado = $this->geraArquivoAssinaturas($txt.$txtAssinatura,$nomeArquivoAss,$nomeArquivo);
        
                    $registroAssinatura = new Assinatura();
                    $registroAssinatura->pathArquivoOriginal= $this->documento;
                    $registroAssinatura->pathArquivo        = $urlArquivoAssinado;
                    $registroAssinatura->codigoValidacao    = $hash;
                    $registroAssinatura->hash               = password_hash($hash,PASSWORD_ARGON2ID);
                    $registroAssinatura->dataAssinatura     = $dataAssinatura;
                    $registroAssinatura->confirmEmail       = "NÃO SE APLICA";
                    $registroAssinatura->grupo_assinatura_id= $grupoAssinaturas->first()->id;
                    $registroAssinatura->save();

                } else {

                    $registroAssinatura = new Assinatura();
                    $registroAssinatura->pathArquivoOriginal= $this->documento;
                    $registroAssinatura->codigoValidacao    = $hash;
                    $registroAssinatura->hash               = password_hash($hash,PASSWORD_ARGON2ID);
                    $registroAssinatura->grupo_assinatura_id= $grupoAssinaturas->first()->id;
                    $registroAssinatura->confirmEmail       = "N";
                    $registroAssinatura->save();

                    Mail::to($assinante->first()->email, $assinante->first()->nome)
                        ->queue(new NotificacaoAssinatura($assinante,$hash));

                    return "E-mail para confirmação de assinatura enviado para ".$assinante->first()->email;
                    
                }
                
            } else {
                return "Não foram encontrados responsáveis pela assinatura do documento";
            }

        }

        /**
        * Gera Hash aleatório para validação da assinatura.
        * @return String
        */
        protected function geraHash() {
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $randomString = '';
                for ($i = 0; $i < 16; $i++) {
                     $index = rand(0, strlen($characters) - 1);
                     if($i%4 == 0 && $i > 0) $randomString .= "-";
                     $randomString .= $characters[$index];
                }
                return $randomString;
        }

        /**
        * Formulário para confirmação da autenticidade da assinatura com hash
        */
        public function autenticaAssinatura() {
            //
        }

        /**
        * Confirmação de assinatura por e-mail para pessoas externas à USP
        * @param  \Illuminate\Http\Request $request
        * @return \Illuminate\Http\Response
        */
        public function checkAssinatura(Request $request) {

            $rules = ['email'            => 'required|email'
                     ,'codigo_validacao' => 'required'];

            $request->validate($rules);
            
            $this->email    = $request->input('email');
            
            $dataAssinatura = date_create(date('Y-m-d H:i:s'));
            $nomeArquivoAss = "html".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";
            $nomeArquivo    = "doc".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";
            $txtAssinatura  = null;
            $assinante      = new \StdClass();

            $registroAssinatura = Assinatura::where('emailAssinatura',$this->email)
                                            ->where('codigoValidacao', $request->input('codigo_validacao'))
                                            ->where('confirmEmail',"N")
                                            ->get();

            if ($registroAssinatura->empty()) {
                return "ERRO: assinatura inválida, verifique o código de validação digitado.";
            } else {
                $grupoAssinaturas = GrupoAssinaturas::find($registroAssinatura->first()->grupo_assinatura_id);
                if (empty($grupoAssinaturas->id)) {
                    return "Erro ao buscar assinantes";
                }
                $txtAssinatura.= $this->geraAssinaturasGrupo($grupoAssinaturas->arquivo_id);

                $assinante = Assinantes::find($grupoAssinaturas->assinante_id);
                if (empty($assinante->id)) {
                    return "Assinante não faz parte do grupo de assinaturas";
                }

                $arquivo = Arquivos::find($grupoAssinaturas->arquivo_id); 
                $this->documento = $arquivo->path_original;

                $txtAssinatura.= $this->geraAssinaturasGrupo($grupoAssinaturas->arquivo_id);
            } 

            $data = ['codigo_validacao' => $request->input('codigo_validacao')
                    ,'nomeUsuario'      => $assinante->first()->nome
                    ,'nusp'             => $assinante->first()->codpes
                    ,'email'            => $assinante->first()->email
                    ,'dataAss'          => date_create($dataAssinatura)->format('d/m/Y H:i:s')
                    ];

            $txtAssinatura.= view('assinatura::pdf._partials.linha-assinatura',$data);
            $txt = view('assinatura::pdf.assinatura', $data);

            $urlArquivoAssinado = $this->geraArquivoAssinaturas($txt.$txtAssinatura,$nomeArquivoAss,$nomeArquivo);

            $registroAssinatura->first()->pathArquivo        = $urlArquivoAssinado;
            $registroAssinatura->first()->dataAssinatura     = $dataAssinatura;
            $registroAssinatura->first()->confirmEmail       = "S";
            if (!empty($registroAssinatura->first()->idGrupo)) $registroAssinatura->grupo_assinatura_id = $registroAssinatura->first()->idGrupo;
            $registroAssinatura->first()->update();

            return redirect(env("APP_URL")."/upload/assinaturas/docAssinado/$nomeArquivo");
        }

        /**
         * Método para gerar texto com grupo de assinaturas
         * @param String idGrupo - código do grupo de assinaturas
         * @return String
         */
        private function geraAssinaturasGrupo($idArquivo) {
            $txtAssinatura = null;

            $documento = Assinatura::with('assinantes')
                                    ->whereRelation('grupo_assinaturas','arquivo_id',$idArquivo)
                                    ->orderBy('dataAssinatura','desc')
                                    ->get();

            foreach ($documento as $doc) {
                    $txtAssinatura.= view('assinatura::pdf._partials.linha-assinatura', 
                                            ['codigo_validacao' => $doc->codigoValidacao
                                            ,'nomeUsuario'      => $doc->assinantes->first()->assinantes->first()->nome
                                            ,'nusp'             => $doc->assinantes->first()->assinantes->first()->codpes
                                            ,'email'            => $doc->assinantes->first()->assinantes->first()->email
                                            ,'dataAss'          => date_create($doc->dataAssinatura)->format('d/m/Y H:i:s')
                                            ]);
            }

            return $txtAssinatura;
        }

        /**
         * Busca hash da assinatura do grupo
         * @param integer idArquivo - código do arquivo para busca de grupo de assinaturas
         * @return String
         */
        private function buscaHashGrupo($idArquivo) {
            $documento = Assinatura::whereRelation('grupo_assinaturas','arquivo_id',$idArquivo)
                                    ->orderBy('dataAssinatura','desc')
                                    ->get();

            if (!$documento->isEmpty()) {
                return $documento->first()->codigo_validacao;
            }

            return "";
        }

        /**
         * Gera o arquivo assinado
         * @param String htmlAssinatura contém HTML da linha da assinatura
         * @param String nmArquivo nome do arquivo intermediário
         * @param String nomeDocAssinado nome do arquivo do documento assinado
         */
        private function geraArquivoAssinaturas($htmlAssinatura,$nmArquivo,$nomeDocAssinado) {
            $pdf = Pdf::loadHTML($htmlAssinatura);                             
            $pdf->save("upload/assinaturas/html/".$nmArquivo);

            $oMerger = PDFMerger::init();

            $oMerger->addPDF($this->documento,'all');
            $oMerger->addPDF("./upload/assinaturas/html/".$nmArquivo, 'all');

            $oMerger->merge();
            $oMerger->save("./upload/assinaturas/docAssinado/".$nomeDocAssinado);

            //return env("APP_URL")."/upload/assinaturas/docAssinado/".$nomeDocAssinado;
            return "Documento Assinado";
        }


}