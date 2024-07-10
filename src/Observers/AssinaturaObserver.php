<?php

namespace Uspdev\Assinatura\Observers;

use Illuminate\Support\Facades\Mail;

use Uspdev\Assinatura\Models\Assinatura;
use Uspdev\Assinatura\Mail\NotificacaoAssinatura;

class AssinaturaObserver
{
    /**
     * Handle the Assinatura "created" event.
     *
     * @param  Assinatura  $assinatura
     * @return void
     */
    public function created(Assinatura $assinatura)
    {
        /*$mensagem = null;
        $collection_assinatura = Assinatura::where('arquivo_id',$assinatura->id)->get();
        if ($collection_assinatura->isEmpty()) {
            $assinatura->codigo_validacao = $this->geraCodigo();
        } else {
            $assinatura->codigo_validacao = $collection_assinatura->first()->codigo_validacao;
        }
        $assinatura->save();

        if (empty($assinatura->codpes)) {
            //Implementar o e-mail com URL temporária aqui
            //Aqui precisa implementar a URL temporária
            $mensagem = "Sua assinatura precisa ser confirmada através do link <a href='".env('APP_URL')."'>LINK</a>";
            
        } else {
            $mensagem = "Existe um novo arquivo para assinar, entre no sistema ".env("APP_NAME");
        }
        Mail::to($assinatura->email, $assinatura->nome)
            ->queue(new NotificacaoAssinatura($assinatura, $mensagem));
        */
    }

    /**
     * Handle the Assinatura "updated" event.
     *
     * @param Assinatura  $assinatura
     * @return void
     */
    public function updated(Assinatura $assinatura)
    {
        $assinatura->hash = password_hash($assinatura->email,PASSWORD_ARGON2ID);
        $assinatura->update();
    }

    /**
     * Handle the Assinatura "deleted" event.
     *
     * @param  Uspdev\Assinatura\Models\Assinatura  $assinatura
     * @return void
     */
    public function deleted(Assinatura $assinatura)
    {
        //
    }

    /**
     * Handle the Assinatura "restored" event.
     *
     * @param  \App\Models\Assinatura  $assinatura
     * @return void
     */
    public function restored(Assinatura $assinatura)
    {
        //
    }

    /**
     * Handle the Assinatura "force deleted" event.
     *
     * @param  \App\Models\Assinatura  $assinatura
     * @return void
     */
    public function forceDeleted(Assinatura $assinatura)
    {
        //
    }

    /**
    * Gera código aleatório para validação da assinatura.
    * @return String
    */
    protected function geraCodigo() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 16; $i++) {
            $index = rand(0, strlen($characters) - 1);
            if($i%4 == 0 && $i > 0) $randomString .= "-";
            $randomString .= $characters[$index];
        }
        return $randomString;
    }
}
