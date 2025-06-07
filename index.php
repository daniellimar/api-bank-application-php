<?php
$requestUri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];

if ($requestUri === '' || $requestUri === '/api') {
    require __DIR__ . '/views/index.php';
    exit;
}

$routes = [
    '/api/test' => '/api/test.php',
    '/api/contas' => '/api/contas.php',
    '/api/transacoes' => '/api/transacoes.php',
    '/api/transferencia' => '/api/transferencia.php',
];

foreach ($routes as $route => $file) {
    if (str_starts_with($requestUri, $route)) {
        $parts = explode('/', $requestUri);
        $_GET['id'] = $parts[3] ?? null;
        require __DIR__ . $file;
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada']);