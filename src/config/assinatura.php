<?php
return [
    'localArquivo' => env("ASSINATURA_LOCAL_ARQUIVO", public_path().'upload/assinaturas'),
    
    'providers' => [
        Webklex\PDFMerger\Providers\PDFMergerServiceProvider::class
    ],
    'aliases' => [
        'PDFMerger' => Webklex\PDFMerger\Facades\PDFMergerFacade::class
    ]
];