@component('mail::message')
# Prezado {{ $assinante->nome }}

Sua assinatura precisa ser confirmada através do link <a href="{{ env('APP_URL') }}">LINK</a>

Digite o seguinte código: {{ $hash }}

@component('mail::button', ['url' => env('APP_URL') ])
Confirmar Assinatura
@endcomponent

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
