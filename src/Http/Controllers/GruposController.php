<?php

namespace Uspdev\Assinatura\Http\Controllers;

use Illuminate\Http\Request;

use Uspdev\Assinatura\Models\Arquivos;
use Uspdev\Assinatura\Models\Assinantes;
use Uspdev\Assinatura\Models\GrupoAssinaturas;
use Uspdev\Assinatura\Models\GruposAssinaturas;

class GruposController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = ['arquivo'        => 'required|file|mimes:pdf'
                 ,'nome_assinante' => 'required|min:3|max:100'
                 ,'email_assinante'=> 'required|email'];

        $messages = ['required'     => "Favor informar o campo :attribute."
                    ,'min'          => "Campo :attribute deve conter no mínimo :min caracteres"
                    ,'max'          => "Campo :attribute deve conter no máximo :max caracteres"
                    ,'arquivo.file' => "O arquivo informado não é válido"
                    ,'arquivo.mimes'=> "O arquivo deve ser do tipo PDF"
                    ];

        $request->validate($rules,$messages);

        $assinantes = array();
        
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
        
        if (!$validacaoArquivo->save()) {
            return false;
        }

        foreach($request->input('nome_assinante') as $key=>$value) {
            $objAssinante = new Assinantes();
            $objAssinante->nome  = $value;
            $objAssinante->email = $request->input('email_assinante')[$key];
            $objAssinante->codpes= $request->input('codpes')[$key];
            
            if (!$objAssinante->save()) {
                return false;
            }

            $grupoAssinatura = new GrupoAssinaturas();
            $grupoAssinatura->arquivo_id = $objAssinante->id;
            $grupoAssinatura->assinante_id = $validacaoArquivo->id;
            if (!$grupoAssinatura->save()) {
                return false;
            }
            $assinantes[] = ['dados_assinantes'=>$objAssinante,'grupo_id'=>$grupoAssinatura->id];
        }
        
        return ['assinantes'=>$assinantes,'arquivo_id'=>$validacaoArquivo->id];
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
