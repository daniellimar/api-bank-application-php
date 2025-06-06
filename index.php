<?php

$request = $_SERVER['REQUEST_URI'];

switch ($request) {
    case '/':
    case '':
        echo json_encode(['message' => 'API root']);
        break;

    case '/test':
        require __DIR__ . '/api/test.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Rota não encontrada']);
        break;
}
