<?php

namespace Uspdev\Assinatura\Observers;

use Uspdev\Assinatura\Models\Arquivo;

class ArquivoObserver
{
    /**
     * Handle the Arquivo "created" event.
     *
     * @param  \App\Models\Arquivo  $arquivo
     * @return void
     */
    public function created(Arquivo $arquivo)
    {
        $arquivo->checksum = hash_file ('CRC32', $arquivo->path_arquivo , FALSE );
        $arquivo->save();
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
