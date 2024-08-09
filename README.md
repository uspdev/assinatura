### O que a aplicação que usa a biblioteca vai fazer?

##Para instalar o pacote basta rodar

``` composer required uspdev/assinatura





1 - Guardar o arquivo original na pasta storage da aplicação, suponha que o arquivo venha de um formulaŕio *$request->file('file')* exemplo:

    $path_arquivo = $request->file('file')->store('SUA-PASTA-NO-STORAGE');


2 - Colocar metadados do arquivo original na tabela *arquivos* da biblioteca assinaturas, exemplo:

    $original_name = $request->file('file')->getClientOriginalName();
    $checksum = # VERIFICAR COMO CALCULAR o checksum!!!

    use Uspdev\Assinatura\Models\Arquivo;
    $arquivo = new Arquivo;
    $arquivo->path_arquivo = $path_arquivo;
    $arquivo->original_name = $original_name;
    $arquivo->checksum = $checksum;
    $arquivo->save();

3 - Ainda na aplicação, informar as pessoas que vão assinar o documento *$arquivo_id*. Exemplo com um assinante USP e um externo:

    use Uspdev\Assinatura\Models\Assinatura;
    $assinante_usp = new Assinatura;
    $assinante_usp->arquivo_id = $arquivo_id;
    $assinante_usp->nome = $nome_usp;
    $assinante_usp->email = $email_usp;
    $assinante_usp->codpes = $codpes_usp;
    $assinante_usp->ordem_assinatura = 1;
    $assinante_usp->save();

    $assinante_externo = new Assinatura;
    $assinante_externo->nome = $nome_externo;
    $assinante_externo->email = $email_externo;
    $assinante_externo->arquivo_id = $arquivo_id;
    $assinante_externo->ordem_assinatura = 2;
    $assinante_externo->save();

### O que a biblioteca vai fazer?

Ao ser registrado uma nova linha na tabela *assinaturas*, um Observer vai disparar uma ação para cada linha nova:

- Geração do código de validação: verificar se há alguma linha anterior com o mesmo código de arquivo, se existir, copiar o código de validação. Se não existir, gerar um código de validação. 

- Caso 1 - tem o campo número USP no novo registro: 

  1. Disparar um email para essa pessoa avisando que há um documento para ser assinado
  2. Na biblioteca assinatura, deve existir uma rota, controller e view para listas os documentos que uma determinada pessoa tem para assinar.
  3. Na mesma página acima ter um botão para assinar, preenchendo: data_assinatura, gerar um arquivo novo (a partir do arquivo original) com as assinaturas e guardar no storage, populando o campo caminho_arquivo_assinado. 
  4. Guardar o hash/checksum do arquivo gerado.

- Caso 2 - não tem o campo número USP no novo registro:

  1. Gerar uma UrlTemporaria para essa pessoa assinar e enviar essa url por email
  2. Deve existir uma rota, controller e view assinar AQUELE documento específico que a pessoa que entrou pela UrlTemporaria para gerar o arquivo assinado pelo sistema.
  3. Guardar o hash/checksum do arquivo gerado.

- Página de consulta:

A biblioteca deve fornecer uma rota/controller/view que retorna o arquivo mais recente assinado baseado no codigo_validacao. No arquivo assinado fornecer esse link para consulta {{ env('APP_URL') }}/consulta;

### Voltando a aplicação

Como a aplicação pega o documento assinado tendo o *$arquivo_id*:

    use Uspdev\Assinatura\AssinaturaService;

    $ultimo_documento = AssinaturaService::ultimo_documento($arquivo_id);