@extends('assinatura::layouts.app')

@section('content')
@if (!empty($msg))
    <div id="div-error" class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $msg }}
    </div>
    <script>
      setTimeout(function(){
        var a = document.getElementById("div-error");
        a.style="display:none"
      }, 5000);
    </script>
@endif

<h1> Lista de documentos assinados </h1>
<table cellspacing = '0' cellspadding='1' style="border:solid 1px black">
    <tr>
        <td>Arquivo</td>
    </tr>
    @foreach ($assinaturas as $assinatura) 
    <tr>
        <td><a href="{{ route('assinatura.arquivo.assinado',['idArquivo'=>$assinatura->arquivo_id]) }}" target="_blank">{{ $assinatura->arquivos->first()->original_name }}</a></td>
    </tr>
    @endforeach
</table>

@endsection