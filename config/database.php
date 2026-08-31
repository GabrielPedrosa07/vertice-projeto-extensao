<?php
/**
 * Configuração de Banco de Dados — Vértice
 * Projeto de Extensão ADS / FASM
 * PHP Puro + PDO
 */

return [
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => '3306',
    'dbname'    => 'vertice_db',
    'user'      => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
