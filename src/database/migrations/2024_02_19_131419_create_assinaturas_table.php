<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
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
            $table->string('pathArquivoOriginal',100);
            $table->string('pathArquivo',100)->nullable();
            $table->string('hashValidacao',20);
            $table->tinyText('hashPrivado');
            $table->string('nomeAssinatura',255);
            $table->unsignedBigInteger('nuspAssinatura')->nullable();
            $table->string('emailAssinatura',100)->nullable();
            $table->datetime('dataAssinatura')->nullable();
            $table->enum('confirmEmail',['S','N','NÃO SE APLICA'])->default('N');
            $table->unsignedBigInteger('grupo_assinaturas_id')->nullable();
            $table->timestamps();
            
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
};
