<?php

return [
    'pdf' => [
        'enabled' => true,
        'binary'  => '/usr/bin/wkhtmltopdf', // Ruta correcta del binario
        'timeout' => false,
        'options' => [],
        'env'     => [],
    ],
    'image' => [
        'enabled' => true,
        'binary'  => '/usr/bin/wkhtmltoimage', // Asegúrate de que wkhtmltoimage esté instalado y en la ruta correcta
        'timeout' => false,
        'options' => [],
        'env'     => [],
    ],
];