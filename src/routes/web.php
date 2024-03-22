
<?php
use Illuminate\Support\Facades\Route;

Route::get('assinatura/autenticacao/',[Uspdev\Assinatura\Http\Controllers\AssinaturaChecker::class,'autenticaAssinatura'])->name('assinatura.autenticacao');
Route::post('assinatura/checkassinatura',[Uspdev\Assinatura\Http\Controllers\AssinaturaChecker::class,'checkAssinatura'])->name('assinatura.checkassinatura');
Route::get('assinatura/formulario-assinatura',[Uspdev\Assinatura\Http\Controllers\AssinaturaChecker::class,'formCheckAssinatura'])->name('assinatura.formulario');

Route::get('arquivo/validacao/{pathArquivo?}',[Uspdev\Assinatura\Http\Controllers\ArquivosController::class,'validaDocumento']);

Route::get('testeassinatura', function(){
    return 'Hello World do seu Pacote!';
});

Route::get('testeemail', function(){

    $assinante = new Uspdev\Assinatura\Models\Assinantes;
    $assinante->nome = "Fulano da Silva";
    $assinante->email = "email@teste.com.br";

    $hash = "abCD-HIJk-LMNO-PqrsT";
    
    
    return new Uspdev\Assinatura\Mail\NotificacaoAssinatura($assinante,$hash);
});