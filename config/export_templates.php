<?php

return [
    'converter' => [
        'binary' => env('LIBREOFFICE_BINARY', 'soffice'),
        'timeout' => (int) env('LIBREOFFICE_TIMEOUT', 120),
    ],
];
