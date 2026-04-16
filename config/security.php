<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    | Controla quantas requisições um IP pode fazer por janela de tempo.
    | Ajuste conforme a necessidade do seu sistema.
    */
    'rate_limit' => [
        'max_attempts'  => env('RATE_LIMIT_MAX_ATTEMPTS', 60),  // requisições
        'decay_seconds' => env('RATE_LIMIT_DECAY_SECONDS', 60), // por minuto
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed IPs (whitelist)
    |--------------------------------------------------------------------------
    | Se preenchido, apenas os IPs listados poderão acessar a API.
    | Deixe vazio para permitir qualquer IP.
    | Exemplo: '192.168.1.10,10.0.0.5'
    */
    'allowed_ips' => array_filter(
        explode(',', env('API_ALLOWED_IPS', ''))
    ),

];
