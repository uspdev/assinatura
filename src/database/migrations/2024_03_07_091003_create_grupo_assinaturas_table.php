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
            $table->tinyText('assinantes');
            $table->timestamps();
        });

        Schema::table('assinaturas', function (Blueprint $table) {
            $table->foreign('grupo_assinatura_id')->references('id')->on('grupo_assinaturas');
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
