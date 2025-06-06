<?php
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) : null;

try {
    $pdo = conectar();

    if ($method !== 'GET') {
        sendResponse(405, ['error' => 'Método HTTP não permitido']);
    }

    handleGet($pdo, $id);

} catch (PDOException $e) {
    sendResponse(500, ['error' => 'Erro no banco de dados', 'details' => $e->getMessage()]);
}

function handleGet(PDO $pdo, ?int $id): void
{
    if ($id) {
        $transacao = findTransacaoById($pdo, $id);
        sendResponse($transacao ? 200 : 404, $transacao ?: ['error' => 'Transação não encontrada']);
    } else {
        $transacoes = findAllTransacoes($pdo);
        sendResponse(200, $transacoes);
    }
}

function findTransacaoById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, conta_origem_id, conta_destino_id, valor, data_transacao, descricao, status, criado_em, atualizado_em 
        FROM master.dbo.TRANSACOES WHERE id = ?");
    $stmt->execute([$id]);
    $transacao = $stmt->fetch(PDO::FETCH_ASSOC);
    return $transacao ?: null;
}

function findAllTransacoes(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, conta_origem_id, conta_destino_id, valor, data_transacao, descricao, status, criado_em, atualizado_em 
        FROM master.dbo.TRANSACOES ORDER BY data_transacao DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sendResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
