
<?php
use Illuminate\Support\Facades\Route;

Route::get('assinatura/autenticacao/',[Uspdev\Assinatura\Http\Controllers\AssinaturaChecker::class,'autenticaAssinatura'])->name('assinatura.autenticacao');
Route::post('assinatura/checkassinatura',[Uspdev\Assinatura\Http\Controllers\AssinaturaChecker::class,'checkAssinatura'])->name('assinatura.checkassinatura');
Route::get('assinatura/formulario-assinatura',[Uspdev\Assinatura\Http\Controllers\AssinaturaChecker::class,'formCheckAssinatura'])->name('assinatura.formulario');

Route::get('testeassinatura', function(){
    return 'Hello World do seu Pacote!';
});