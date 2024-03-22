<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrupoAssinaturasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grupo_assinaturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('arquivo_id');
            $table->unsignedBigInteger('assinante_id');
            $table->timestamps();

            $table->foreign('arquivo_id')->references('id')->on('arquivos');
            $table->foreign('assinante_id')->references('id')->on('assinantes');
            $table->unique(['arquivo_id','assinante_id']);
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
        Schema::dropIfExists('grupo_assinaturas');
        Schema::enableForeignKeyConstraints();
    }
}
