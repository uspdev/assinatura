
<?php

use Illuminate\Support\Facades\Route;
use Uspdev\Assinatura\Http\Controllers\ArquivoController;

Route::get('assinaturas/arquivo/{arquivo}',[ArquivoController::class,'show'])->name('assinatura.arquivo.original');

Route::get('assinatura/arquivoAssinado/{idArquivo}',[Uspdev\Assinatura\Http\Controllers\AssinaturaController::class,'obterArquivoAssinado'])->name('assinatura.arquivo.assinado');
Route::get('assinatura/geraAssinatura/{idArquivo}/{email}',[Uspdev\Assinatura\Http\Controllers\AssinaturaController::class,'geraAssinatura'])->name('assinatura.geraassinatura');

Route::post('assinatura/cadastro',[Uspdev\Assinatura\Http\Controllers\ArquivosController::class,'store'])->name('assinatura.cadastro');
Route::post('assinatura/geraAssinatura',[Uspdev\Assinatura\Http\Controllers\AssinaturaController::class,'geraAssinaturas'])->name('assinatura.geraassinaturas');

/*Route::get('testeemail', function(){

    $assinatura = new Uspdev\Assinatura\Models\Assinatura;
    $assinatura->nome = "Fulano da Silva";
    $assinatura->email = "email@teste.com.br";

    $hash = "abCD-HIJk-LMNO-PqrsT";
    
    
    return new Uspdev\Assinatura\Mail\NotificacaoAssinatura($assinatura,$hash);
});*/