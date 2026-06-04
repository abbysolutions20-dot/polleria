<?php

return [
    'app' => [
        'name' => 'Polleria POS Pro XAMPP',
        'base_url' => '',
        'timezone' => 'America/Lima',
        'currency' => 'S/',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'polleria_pos_pro',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'auth' => [
        'session_key' => 'polleria_pos_user',
        'legacy_default_password' => '123456',
    ],
];
