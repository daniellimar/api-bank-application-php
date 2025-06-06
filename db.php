<?php

$config = require __DIR__ . '/config.php';

function conectar(): PDO
{
    global $config;

    try {
        $dsn = "{$config['db_driver']}:Server={$config['db_host']},1433;Database={$config['db_name']};Encrypt=yes;TrustServerCertificate=yes";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Erro ao conectar ao banco de dados',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}
