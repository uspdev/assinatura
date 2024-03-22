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
            $table->unsignedBigInteger('grupo_assinaturas_id');
            $table->string('pathArquivoOriginal',100);
            $table->string('pathArquivoAssinado',100)->nullable();
            $table->string('codigoValidacao',20);
            $table->tinyText('hash');
            $table->datetime('dataAssinatura')->nullable();
            $table->enum('confirmEmail',['S','N','NÃO SE APLICA'])->default('N');
            $table->timestamps();

            $table->foreign('grupo_assinaturas_id')->references('id')->on('grupo_assinaturas');
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
        Schema::dropIfExists('assinaturas');
        Schema::enableForeignKeyConstraints();
    }
}
