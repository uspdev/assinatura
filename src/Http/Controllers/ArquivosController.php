<?php

namespace Uspdev\Assinatura\Http\Controllers;

use Illuminate\Http\Request;

use Uspdev\Assinatura\Models\Arquivos;

class ArquivosController extends Controller
{
    
    /**
     * Valida arquivo utilizando webservice REST ValidaDoc
     * @param String path Caminho do arquivo a ser validado
     * @return stdClass
     */
    static function validaDocumento(String $path) {
        
        $token = 'GmeJodJTaOz_6QqLJDJshgjeQKa4deXRb96U7_jv4yTKCcMqSiDKzKBzDD8_S8jzCIDuNkQ9B_GeIiFsgvrP67yRoRWHwM31';
        $ch = curl_init();

        curl_setopt_array($ch, [

            CURLOPT_URL => 'https://uspdigital.usp.br/wsusp/validadoc/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => 'UTF-8',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',

            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'X-USERNAME: '.env('ASSINATURA_USERNAME'),
                'X-PASSWORD: '.env('ASSINATURA_PASSWORD')
            ],

            CURLOPT_POSTFIELDS => [
                                    'documentopdf' => new \CURLFILE($path),
                                    'nomesistemaorigem' => 'Sistema de Estágios FCF'
                                  ],

            //CURLOPT_PROTOCOLS => CURLPROTO_HTTPS
        ]);

        $resultado = curl_exec($ch);
        curl_close($ch);
        return var_dump(json_decode($resultado));
    }

    /**
     * Método para uso em Rotas
     * @param String path Caminho do arquivo a ser validado
     * @return stdClass
     */
    public function validaDoc($path) {
        return ArquivosController::validaDocumento($path);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $rules = ['arquivo' => 'required|file|mimes:pdf'];

        $messages = ['required'             => "Favor informar o campo :attribute."
                    ,'aqrquivo.file'        => "O arquivo informado não é válido"
                    ,'path_arq_tcc.mimes'   => "O arquivo deve ser do tipo PDF"
                    ];

        $request->validate($rules,$messages);
        
        $validaDoc = ArquivosController::validaDocumento($request->input('arquivo'));
        $validacaoArquivo = new Arquivos();
        $validacaoArquivo->nome_unidade             = $validaDoc->nomeUnidade;
        $validacaoArquivo->codigo_validacao         = $validaDoc->codigoValidacao;
        $validacaoArquivo->checksum                 = $validaDoc->checksumDocumento;
        $validacaoArquivo->codigo_pessoa_cadastro   = $validaDoc->codigoPessoaCadastro;
        $validacaoArquivo->nome_documento           = $validaDoc->nomeDocumento;
        $validacaoArquivo->codigo_unidade           = $validaDoc->codigoUnidade;
        $validacaoArquivo->data_validacao           = $validaDoc->dataValidacao;
        $validacaoArquivo->path_original            = $request->input('arquivo')->save('upload/assinaturas/original');             
        $validacaoArquivo->nome_original            = $request->input('arquivo')->getClientOriginalName();
        
        return $validacaoArquivo->id;
    }

}
