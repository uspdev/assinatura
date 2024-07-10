<?php

namespace Uspdev\Assinatura\Observers;

use Uspdev\Assinatura\Models\Arquivo;

use Illuminate\Support\Facades\Storage;

class ArquivoObserver
{
    /**
     * Handle the Arquivo "created" event.
     *
     * @param  \App\Models\Arquivo  $arquivo
     * @return void
     */
    public function creating(Arquivo $arquivo)
    {
        $fullpath = Storage::path($arquivo->path_arquivo);
        $arquivo->checksum = hash_file('CRC32',$fullpath, FALSE);
    }

    /**
     * Handle the Arquivo "updated" event.
     *
     * @param  \App\Models\Arquivo  $arquivo
     * @return void
     */
    public function updated(Arquivo $arquivo)
    {
        //
    }

    /**
     * Handle the Arquivo "deleted" event.
     *
     * @param  \App\Models\Arquivo  $arquivo
     * @return void
     */
    public function deleted(Arquivo $arquivo)
    {
        //
    }

    /**
     * Handle the Arquivo "restored" event.
     *
     * @param  \App\Models\Arquivo  $arquivo
     * @return void
     */
    public function restored(Arquivo $arquivo)
    {
        //
    }

    /**
     * Handle the Arquivo "force deleted" event.
     *
     * @param  \App\Models\Arquivo  $arquivo
     * @return void
     */
    public function forceDeleted(Arquivo $arquivo)
    {
        //
    }
}
