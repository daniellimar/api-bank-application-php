<?php

require_once __DIR__ . '/../db.php';

try {
    $pdo = conectar();
    echo "Conexão bem-sucedida!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
