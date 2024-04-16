@component('mail::message')
# Prezado {{ $assinatura->nome }}

{{ $mensagem }}

@component('mail::button', ['url' => env('APP_URL') ])
Confirmar Assinatura
@endcomponent

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
