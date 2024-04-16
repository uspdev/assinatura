<?php

namespace Uspdev\Assinatura\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Uspdev\Assinatura\Models\Assinatura;

class NotificacaoAssinatura extends Mailable
{
    use Queueable, SerializesModels;

    public $assinatura;
    public $mensagem;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Assinatura $assinatura, $mensagem)
    {
        $this->assinatura = $assinatura;
        $this->mensagem = $mensagem;
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
