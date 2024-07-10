<?php

namespace Uspdev\Assinatura\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Uspdev\Assinatura\Models\Arquivo;

class ArquivoController extends Controller
{
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Integer Código do arquivo
     */
    public function store(Request $request)
    {
        
        $rules =['arquivo'  => 'required|file|mimes:pdf'];

        $messages = ['required'     => "Favor informar o campo :attribute."
                    ,'arquivo.file' => "O arquivo informado não é válido"
                    ,'arquivo.mimes'=> "O arquivo deve ser do tipo PDF"
                    ];

        $request->validate($rules,$messages);
        $path = $request->input('arquivo')->store('assinatura/original');

        $arquivo = new Arquivo();
        $arquivo->path_arquivo = $path;
        $arquivo->original_name = $request->input('arquivo')->getClientOriginalName();
        $arquivo->save();
        
        return $arquivo->id;
    }

    /**
     * Retorna o conteúdo do arquivo.
     *
     * @param  Integer $id Id do arquivo
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $arquivo = Arquivo::find($id);
        return Storage::download($arquivo->path_arquivo,$arquivo->original_name); 
    }

    /**
     * Valida arquivo utilizando webservice REST ValidaDoc
     * @param String path Caminho do arquivo a ser validado
     * @return stdClass
     */
    static function validaDocumento(String $path, $token = null) {
        
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

        return json_decode($resultado);
    }

    /**
     * Método para verificar se um documento é válido através de WS ValidaDoc
     * @param String cod_validacao pode ser o código de validação ou checksum do documento
     * @return stdClass
     */
    static function verificaDocumento(String $cod_validacao) {
        $ch = curl_init();

        curl_setopt_array($ch, [

            CURLOPT_URL => 'https://uspdigital.usp.br/wsusp/validadoc/verificar/'.$cod_validacao,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => 'UTF-8',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',

            CURLOPT_HTTPHEADER => [
                'X-USERNAME: '.env('ASSINATURA_USERNAME'),
                'X-PASSWORD: '.env('ASSINATURA_PASSWORD')
            ],

            //CURLOPT_PROTOCOLS => CURLPROTO_HTTPS
        ]);

        $resultado = curl_exec($ch);
        curl_close($ch);

        //Storage::disk('local')->put('assinatura/original/');

        return json_decode($resultado);
    }

    /**
     * Método para uso em Rotas
     * @param String path Caminho do arquivo a ser validado
     * @return stdClass
     */
    public function validaDoc($path, $token) {
        return ArquivosController::validaDocumento($path, $token);
    }


}
