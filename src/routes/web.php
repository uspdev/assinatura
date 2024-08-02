
<?php

use Illuminate\Support\Facades\Route;
use Uspdev\Assinatura\Http\Controllers\ArquivoController;
use Uspdev\Assinatura\Http\Controllers\AssinaturaController;

Route::prefix('assinaturas')->group(function () {
    Route::get('arquivo/{arquivo}',[ArquivoController::class,'show'])->name('assinatura.arquivo.original');
    Route::get('arquivoAssinado/{idArquivo}',[AssinaturaController::class,'obterArquivoAssinado'])->name('assinatura.arquivo.assinado');
    
    Route::post('cadastro',[ArquivoController::class,'store'])->name('assinatura.cadastro');
    Route::post('geraAssinatura',[AssinaturaController::class,'geraAssinaturas'])->name('assinatura.geraassinaturas');

    Route::get('pendentes/{email}',[AssinaturaController::class,'formCheckAssinatura'])->name('assinatura.pendente');
});

/*Route::get('testeemail', function(){

    $assinatura = new Uspdev\Assinatura\Models\Assinatura;
    $assinatura->nome = "Fulano da Silva";
    $assinatura->email = "email@teste.com.br";

    $hash = "abCD-HIJk-LMNO-PqrsT";
    
    
    return new Uspdev\Assinatura\Mail\NotificacaoAssinatura($assinatura,$hash);
});*/