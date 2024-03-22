<?php

namespace Uspdev\Assinatura\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Uspdev\Assinatura\Models\Assinantes;

class NotificacaoAssinatura extends Mailable
{
    use Queueable, SerializesModels;

    public $assinante;
    public $hash;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Assinantes $assinante,$hash)
    {
        $this->assinante = $assinante;
        $this->hash = $hash;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('assinatura::emails.assinatura_check')
                    ->subject("[SISTEMA DE ESTAGIOS FCF] Notificação de Assinatura");
    }
}
