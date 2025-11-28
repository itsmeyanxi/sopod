<?php

return [
    'pdf' => [
        'enabled' => true,
        'binary'  => '"C:/Program Files/wkhtmltopdf/bin/wkhtmltopdf.exe"',
        'timeout' => false,
        'options' => [],
        'env'     => [],
    ],
    'image' => [
        'enabled' => true,
        'binary'  => '"C:/Program Files/wkhtmltopdf/bin/wkhtmltoimage.exe"',
        'timeout' => false,
        'options' => [],
        'env'     => [],
    ],
];