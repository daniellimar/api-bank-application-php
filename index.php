<?php
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$requestUri = rtrim($requestUri, '/');

if ($requestUri === '' || $requestUri === '/api') {
    echo json_encode(['message' => 'API root']);
    exit;
}

if (str_starts_with($requestUri, '/api/contas')) {
    // api/contas ou /api/contas/123
    $parts = explode('/', $requestUri);
    $id = $parts[3] ?? null;

    require __DIR__ . '/api/contas.php';


    exit;
}

if (str_starts_with($requestUri, '/api/transacoes')) {
    $parts = explode('/', $requestUri);
    $id = $parts[3] ?? null;

    require __DIR__ . '/api/transacoes.php';

    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada']);
