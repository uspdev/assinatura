<form action="" method="POST">
    @csrf
    <h1>Confirmação de Assinatura</h1>
    E-mail <input type="email" name="email" value="{{ $email ?? "" }}"><br/>
    Informe o código recebido por e-mail: <input type="text" name="hash" size="20"><br/>
    <input type="submit" value="Confirmar">
</form>