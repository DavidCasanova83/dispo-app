<?php

return [

    'pdf_compression' => [

        'enabled' => env('PDF_COMPRESSION_ENABLED', true),

        'ghostscript' => env('GHOSTSCRIPT_BIN', '/usr/bin/gs'),

        'preset' => '/ebook',

        'process_timeout' => 170,

        'min_savings_kb' => 50,

    ],

];
