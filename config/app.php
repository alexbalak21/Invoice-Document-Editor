<?php

use App\Core\Env;

return [
    'name'  => Env::get('APP_NAME',  'DocEditor'),
    'env'   => Env::get('APP_ENV',   'production'),
    'debug' => Env::get('APP_DEBUG', 'false') === 'true',

    'db' => [
        'host'     => Env::get('DB_HOST',     '127.0.0.1'),
        'port'     => Env::get('DB_PORT',     '3306'),
        'database' => Env::get('DB_DATABASE', 'doceditor'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset'  => Env::get('DB_CHARSET',  'utf8mb4'),
    ],

    // Issuer defaults — could also go in .env if they change per deployment
    'issuer' => [
        'name'    => 'NOVOCIB SAS',
        'address' => 'BD de Chatillon, Quai Jean Voisin',
        'city'    => '62200 Boulogne-sur-Mer — France',
        'email'   => 'lbalakireva@novocib.com',
        'legal'   => "SAS, société par actions simplifiée — Share capital: 260 158,00 €\nEORI# FR48237937700047\nVAT# FR90 482 379 377",
    ],
];
