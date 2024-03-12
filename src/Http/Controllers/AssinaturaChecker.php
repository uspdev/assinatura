<?php

namespace Uspdev\Assinatura\Http\Controllers;

//use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use Barryvdh\DomPDF\Facade\Pdf;
use Uspdev\Replicado\Pessoa;
use Uspdev\Assinatura\Models\Assinatura;
use Uspdev\Assinatura\Models\GrupoAssinaturas;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class AssinaturaChecker {

        protected $documento;
        protected $email;

        /**
         * Gera formulário para checar assinatura Externa
         */
        public function formCheckAssinatura() {
            if (!Auth::check()) {
                return "Usuário precisa estar logado no sistema";
            }

            return view('assinatura::form_check_assinatura',['email' => Auth::user()->email]);
        }

        /**
        * Checa a assinatura de pessoas com vínculo USP e envia e-mail de validação para pessoas externas
        * @param String documento path do arquivo a ser assinado
        * @param int codpes número USP do assinante - opcional, deve ser informado caso não seja informado e-mail
        * @param String email e-mail do assinante - opcional, deve ser informado caso o n.º USP não seja
        * @param String nomeAssinante nome do assinante, deve ser informado para pessoas externas à USP
        * @param int idGrupo -> informar se num mesmo documento constará mais de uma assinatura, o grupo deverá estar informado na tabela de grupos. Este id é gerado pela tabela grupo_assinantes, portanto a 
        *            aplicação de origem deve guardar essa informação.
        */
        public function check($documento, $codpes = 0, $email = null, $nomeAssinante = null, $idGrupo = 0) {
            $this->documento    = $documento;
            $this->email        = $email;
            $pdf                = null;
            $dataAssinatura     = date_create(date('Y-m-d H:i:s'));
            $txtAssinatura      = null;
            $grupoAssinaturas   = null;
            $assinantes         = array();
            $pessoa             = !empty($codpes)?Pessoa::dump($codpes):array();
            $nomeArquivoAss     = null;
            $nomeArquivo        = null;
            $hash               = null;

            if ($codpes==0 && empty($this->email)) {
                return "É preciso informar um número USP ou um e-mail para validação da assinatura";
            }

            if ($idGrupo) {
                $grupoAssinaturas = GrupoAssinaturas::find($idGrupo);
                if (isset($grupoAssinaturas->id)) {
                    $txtAssinatura.= $this->geraAssinaturasGrupo($grupoAssinaturas->id);
                    $hash = $this->buscaHashGrupo($idGrupo);
                    $assinantes = explode(",",$grupoAssinaturas->assinantes);

                    if (!in_array($this->email,$assinantes) && (count($pessoa) && !in_array($codpes,$assinantes))) {
                        return "Assinante não faz parte do grupo de assinaturas";
                    }
                    $registroAssinatura = Assinatura::where('pathArquivoOriginal',$this->documento)
                                                    ->where('grupo_assinatura_id',$idGrupo)
                                                    ->where('emailAssinatura',$this->email)
                                                    ->orWhere('nuspAssinatura',$codpes)
                                                    ->get();

                    if (!$registroAssinatura->isEmpty()) {
                        return "1-Documento já assinado por ".$registroAssinatura->first()->nomeAssinatura;
                    }
                } else {
                    return "Grupo de Assinaturas não encontrado";
                }
            } else {
                $registroAssinatura = Assinatura::where('pathArquivoOriginal',$this->documento)
                                                ->where('emailAssinatura',$this->email)
                                                ->orWhere('nuspAssinatura',$codpes)
                                                ->get();

                if (!$registroAssinatura->isEmpty()) {
                    return "2-Documento já assinado por ".$registroAssinatura->first()->nomeAssinatura;
                }
            }
            $hash = empty($hash)?$this->geraHash():$hash;

            if ($codpes) {
                if (count($pessoa)) {
                    $this->email = empty($this->email)?Pessoa::email($codpes):$this->email;
                    $nomeArquivoAss = "html".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";
                    $nomeArquivo = "doc".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";

                    
                    $data = ['hash'        => $hash
                            ,'nomeUsuario' => $pessoa['nompes']
                            ,'nusp'        => $pessoa['codpes']
                            ,'dataAss'     => $dataAssinatura->format('d/m/Y H:i:s')
                            ];

                    $txt = view('assinatura::pdf.assinatura', $data);
                    $txtAssinatura = $txt.$txtAssinatura;
                        
                    $urlArquivoAssinado = $this->geraArquivoAssinaturas($txtAssinatura,$nomeArquivoAss,$nomeArquivo);

                    $registroAssinatura = new Assinatura();
                    $registroAssinatura->pathArquivoOriginal= $this->documento;
                    $registroAssinatura->pathArquivo        = $urlArquivoAssinado;
                    $registroAssinatura->hashValidacao      = $hash;
                    $registroAssinatura->hashPrivado        = password_hash($hash,PASSWORD_ARGON2ID);
                    $registroAssinatura->nomeAssinatura     = $pessoa['nompes'];
                    $registroAssinatura->emailAssinatura    = Pessoa::email($pessoa['codpes']);
                    $registroAssinatura->nuspAssinatura     = $pessoa['codpes'];
                    $registroAssinatura->dataAssinatura     = $dataAssinatura;
                    $registroAssinatura->confirmEmail       = "NÃO SE APLICA";
                    if (!empty($idGrupo)) $registroAssinatura->grupo_assinatura_id = $idGrupo;
                    $registroAssinatura->save();

                    return redirect($urlArquivoAssinado);

                } else {
                    return "Pessoa não encontrada na base de dados da USP";
                }
            } else {
                $registroAssinatura = new Assinatura();
                $registroAssinatura->pathArquivoOriginal= $this->documento;
                $registroAssinatura->hashValidacao      = $hash;
                $registroAssinatura->hashPrivado        = password_hash($hash,PASSWORD_ARGON2ID);
                $registroAssinatura->nomeAssinatura     = $nomeAssinante;
                $registroAssinatura->emailAssinatura    = $this->email;
                $registroAssinatura->confirmEmail       = "N";
                $registroAssinatura->save();

                return "Mandar e-mail de confirmação de assinatura com o hash para confirmação";
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

            $this->email = $request->input('email');
            $this->documento = $request->input('documento');

            $dataAssinatura = date_create(date('Y-m-d H:i:s'));
            $nomeArquivoAss = "html".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";
            $nomeArquivo    = "doc".$this->email."-".date_timestamp_get($dataAssinatura).".pdf";
            $txtAssinatura  = null;
            $assinantes     = array();

            $registroAssinatura = Assinatura::where('emailAssinatura',$this->email)
                                            ->where('hashValidacao', $request->input('hash'))
                                            ->where('confirmEmail',"N")
                                            ->get();

            if ($registroAssinatura->empty()) {
                return "ERRO: assinatura inválida, verifique o hash digitado.";
            } elseif (!empty($registroAssinatura->first()->grupo_assinatura_id)) {
                $grupoAssinaturas = GrupoAssinaturas::find($registroAssinatura->first()->grupo_assinatura_id);
                $assinantes = explode(",",$grupoAssinaturas->assinantes);
                if (!in_array($this->email,$assinantes)) {
                    return "Assinante não faz parte do grupo de assinaturas";
                }
                $txtAssinatura.= $this->geraAssinaturasGrupo($registroAssinatura->first()->grupo_assinatura_id);
            } elseif ($this->documento != $registroAssinatura->first()->pathArquivoOriginal) {
                return "Documento inválido";
            }

            $data = ['hash'        => $request->input('hash')
                    ,'nomeUsuario' => Auth::user()->name
                    ,'email'       => $this->email
                    ,'dataAss'     => $dataAssinatura->format('d/m/Y H:i:s')
                    ];

            $txtAssinatura.= view('assinatura::pdf.assinatura', $data);

            $urlArquivoAssinado = $this->geraArquivoAssinaturas($txtAssinatura,$nomeArquivoAss,$nomeArquivo);

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
        private function geraAssinaturasGrupo($idGrupo) {
            $txtAssinatura = null;

            $documento = Assinatura::where('grupo_assinatura_id',$idGrupo)
                                        ->orderBy('dataAssinatura','desc')
                                        ->get();

            foreach ($documento as $doc) {
                    $txtAssinatura.= view('assinatura::pdf._partials.linha-assinatura', 
                                            ['hash'        => $doc->hashValidacao
                                            ,'nomeUsuario' => $doc->nomeAssinatura
                                            ,'nusp'        => $doc->nuspAssinatura
                                            ,'email'       => $doc->emailAssinatura
                                            ,'dataAss'     => date_create($doc->dataAssinatura)->format('d/m/Y H:i:s')
                                            ]);
            }

            return $txtAssinatura;
        }

        /**
         * Busca hash da assinatura do grupo
         * @param String idGrupo - código do grupo de assinaturas
         * @return String
         */
        private function buscaHashGrupo($idGrupo) {
            $documento = Assinatura::where('grupo_assinatura_id',$idGrupo)
                                    ->orderBy('dataAssinatura','desc')
                                    ->get();

            if (!$documento->isEmpty()) {
                return $documento->first()->hashValidacao;
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
            $pdf->save(public_path()."/upload/assinaturas/html/".$nmArquivo);

            $oMerger = PDFMerger::init();

            $oMerger->addPDF($this->documento,'all');
            $oMerger->addPDF(public_path()."/upload/assinaturas/html/".$nmArquivo, 'all');

            $oMerger->merge();
            $oMerger->save(public_path()."/upload/assinaturas/docAssinado/".$nomeDocAssinado);

            return env("APP_URL")."/upload/assinaturas/docAssinado/".$nomeDocAssinado;
        }


}