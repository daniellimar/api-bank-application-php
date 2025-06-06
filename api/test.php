<?php
require_once 'db.php';

try {
    $pdo = conectar();
    echo "Conexão bem-sucedida!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
