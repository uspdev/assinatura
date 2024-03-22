<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArquivosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('arquivos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->tinyText('nome_unidade');
            $table->tinyText('codigo_validacao');
            $table->tinyText('checksum');
            $table->integer('codigo_pessoa_cadastro');
            $table->tinyText('nome_documento');
            $table->tinyText('codigo_unidade');
            $table->tinyText('nome_original');
            $table->tinyText('path_original');
            $table->date('data_validacao');
            $table->date('data_invalidacao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('arquivos');
    }
}
