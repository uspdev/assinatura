
@if ($errors->any())
    <div id="div-error" class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    <script>
      setTimeout(function(){
        var a = document.getElementById("div-error");
        a.style="display:none"
      }, 3000);
    </script>
@endif

<form id="form_check_assinaturas" action="/assinatura/geraAssinatura" method="POST">
    @csrf
    <h1>Assinaturas Pendentes</h1>
    <h2>Selecione os documentos que quer assinar</h2>
    <input type="hidden" name="email" value="{{ $email }}">
    @foreach ($assinaturas as $assinatura) 
        <p>Nome do assinante: {{ $assinatura->nome }}</p>
        <p><input type="checkbox" name="arq_assinatura[]" value="{{ $assinatura->arquivos->id }}"><a href="{{ route('assinatura.view',['arquivo'=>$assinatura->arquivos->id]) }}">{{ $assinatura->arquivos->original_name }}</a></p>
        <p>
        Informe o código de validação do arquivo que foi informado no e-mail: 
        <input type="text" size="5" maxlength="4" value="{{ old('codigo_validacao1[]') }}" id="codigo_validacao1{{$assinatura->arquivos->id}}" name="codigo_validacao1[]">-
        <input type="text" size="5" maxlength="4" value="{{ old('codigo_validacao2[]') }}" id="codigo_validacao2{{$assinatura->arquivos->id}}" name="codigo_validacao2[]">-
        <input type="text" size="5" maxlength="4" value="{{ old('codigo_validacao3[]') }}" id="codigo_validacao3{{$assinatura->arquivos->id}}" name="codigo_validacao3[]">-
        <input type="text" size="5" maxlength="4" value="{{ old('codigo_validacao4[]') }}" id="codigo_validacao4{{$assinatura->arquivos->id}}" name="codigo_validacao4[]">
        </p>
    @endforeach
    <input type="submit" value="Confirmar">
</form>

