@auth
@empty(auth()->user()->codpes)
<p>Este formulário está acessível somente para quem tem vínculo USP. Pessoas externas à USP devem confirmar assinatura no e-mail enviado.</p>    
@endif
<form id="form_check_assinaturas" action="{{ route('assinatura.geraassinatura') }}" method="POST">
    @csrf
    <h1>Assinaturas Pendentes</h1>
    <h2>Selecione os documentos que quer assinar</h2>
    <input type="hidden" name="email" value="{{ auth()->user()->email }}">
    @foreach ($assinaturas->arquivos() as $arquivo) {
        <p><input type="checkbox" name="arq_assinatura[]" value="{{ $arquivo->id }}"><a href="{{ route('assinatura.arquivo.show',['id'=>$arquivo->id]) }}">{{ $arquivo->original_name }}</a></p>
    }
    <input type="submit" value="Confirmar">
</form>
@endempty
@endauth
@guest
  <p>É preciso estar logado no sistema para visualizar esse formulário</p>   
@endguest