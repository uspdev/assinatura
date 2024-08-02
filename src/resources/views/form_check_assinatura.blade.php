@auth

<form id="form_check_assinaturas" action="" method="POST">
    @csrf
    <h1>Assinaturas Pendentes</h1>
    <h2>Selecione os documentos que quer assinar</h2>
    <input type="hidden" name="email" value="{{ $email }}">
    @foreach ($assinaturas as $assinatura) 
        <p>Nome do assinante: {{ $assinatura->nome }}</p>
        <p><input type="checkbox" name="arq_assinatura[]" value="{{ $assinatura->arquivo->id }}"><a href="{{ route('assinatura.view',['arquivo'=>$assinatura->arquivo->id]) }}">{{ $assinatura->arquivo->original_name }}</a></p>
        <p>
        Informe o código de validação do arquivo que foi informado no e-mail: <input type="text" size="5" maxlength="4" value="{{ old('codigo_validacao1') }}" id="codigo_validacao1" name="codigo_validacao1">-
        <input type="text" size="5" maxlength="4" value="{{ old('codigo_validacao2') }}" id="codigo_validacao2" name="codigo_validacao2">-
        <input type="text" size="5" maxlength="4" value="{{ old('codigo_validacao3') }}" id="codigo_validacao3" name="codigo_validacao3">-
        <input type="text" size="5" maxlength="4" value="{{ old('codigo_validacao4') }}" id="codigo_validacao4" name="codigo_validacao4">
        </p>
    @endforeach
    <input type="submit" value="Confirmar">
</form>

@endauth

@guest
  <p>É preciso estar logado no sistema para visualizar esse formulário</p>   
@endguest