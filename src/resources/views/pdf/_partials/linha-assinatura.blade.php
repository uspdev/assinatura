<p><b> {{ $nomeUsuario }} </b><br/>@empty($nusp) <b>E-mail</b>: {{ $email }} <br/> @else <b>N.º USP</b>: {{ $nusp }} @endempty <br/><b>Data:</b> {{ $dataAss }} </p>