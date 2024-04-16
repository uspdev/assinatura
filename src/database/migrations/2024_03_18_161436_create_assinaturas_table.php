<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssinaturasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assinaturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('arquivo_id');
            $table->string('nome',100);
            $table->string('email',100);
            $table->bigInteger('codpes')->nullable();
            $table->integer('ordem_assinatura');
            $table->string('codigo_validacao',20)->nullable();
            $table->date('data_assinatura')->nullable();
            $table->tinyText('hash')->nullable();
            $table->string('path_arquivo_assinado',100)->nullable();
            $table->timestamps();

            $table->foreign('arquivo_id')->references('id')->on('arquivos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('usp_assinaturas');
        Schema::enableForeignKeyConstraints();
    }
}
