<?php

require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) : null;

try {
    $pdo = conectar();

    $handlers = [
        'GET' => fn() => handleGet($pdo, $id),
        'POST' => fn() => handlePost($pdo),
        'PUT' => fn() => handlePut($pdo, $id),
        'DELETE' => fn() => handleDelete($pdo, $id),
    ];

    isset($handlers[$method]) ? $handlers[$method]() : sendResponse(405, ['error' => 'Método HTTP não permitido']);
} catch (PDOException $e) {
    sendResponse(500, ['error' => 'Erro no banco de dados', 'details' => $e->getMessage()]);
}

function handleGet(PDO $pdo, ?int $id): void
{
    $response = $id ? findContaById($pdo, $id) : findAllContas($pdo);

    sendResponse($response ? 200 : 404, $response ?: ['error' => 'Conta não encontrada']);
}

function handlePost(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!validatePostData($data, $errors)) {
        sendResponse(400, ['error' => 'Dados inválidos', 'details' => $errors]);
        return;
    }

    if (findContaByNumeroConta($pdo, $data['numero_conta'])) {
        sendResponse(409, ['error' => 'Conta já existe. Verifique os dados e tente novamente.']);
        return;
    }

    $saldo = isset($data['saldo']) ? floatval($data['saldo']) : 0.00;

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            "INSERT INTO CONTAS (numero_conta, agencia, titular, saldo) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            trim($data['numero_conta']),
            trim($data['agencia']),
            trim($data['titular']),
            $saldo
        ]);
        $pdo->commit();

        sendResponse(201, ['message' => 'Conta criada com sucesso', 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendResponse(500, ['error' => 'Erro ao criar conta', 'details' => $e->getMessage()]);
    }
}

function handlePut(PDO $pdo, ?int $id): void
{
    if (!$id) {
        sendResponse(400, ['error' => 'ID da conta é necessário para atualizar']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!validatePutData($data, $errors)) {
        sendResponse(400, ['error' => 'Dados inválidos para atualização', 'details' => $errors]);
        return;
    }

    $fields = buildUpdateFields($data, ['saldo', 'titular']);

    if (empty($fields)) {
        sendResponse(400, ['error' => 'É necessário informar pelo menos saldo ou titular para atualizar']);
        return;
    }

    $fields['atualizado_em'] = 'GETDATE()';

    $setClauses = [];
    $values = [];
    foreach ($fields as $key => $value) {
        $setClauses[] = $key === 'atualizado_em' ? "$key = $value" : "$key = ?";
        if ($key !== 'atualizado_em') {
            $values[] = $value;
        }
    }

    $values[] = $id;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE CONTAS SET " . implode(', ', $setClauses) . " WHERE id = ?");
        $stmt->execute($values);

        $pdo->commit();

        sendResponse(200, ['message' => 'Conta atualizada com sucesso']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendResponse(500, ['error' => 'Erro ao atualizar conta', 'details' => $e->getMessage()]);
    }
}

function handleDelete(PDO $pdo, ?int $id): void
{
    if (!$id) {
        sendResponse(400, ['error' => 'ID da conta é necessário para deletar']);
        return;
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM CONTAS WHERE id = ?");
        $stmt->execute([$id]);
        $pdo->commit();
        http_response_code(204);
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendResponse(500, ['error' => 'Erro ao deletar conta', 'details' => $e->getMessage()]);
    }
}

function sendResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function validatePostData(?array $data, ?array &$errors = null): bool
{
    $errors = [];

    if (!is_array($data)) {
        $errors[] = 'JSON inválido ou não enviado';
        return false;
    }

    foreach (['numero_conta', 'agencia', 'titular'] as $field) {
        if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
            $errors[] = "Campo obrigatório inválido: $field";
        }
    }

    if (isset($data['numero_conta']) && !ctype_digit($data['numero_conta'])) {
        $errors[] = "Campo numero_conta deve conter apenas números";
    }

    if (isset($data['agencia']) && !ctype_digit($data['agencia'])) {
        $errors[] = "Campo agencia deve conter apenas números";
    }

    if (isset($data['saldo']) && !is_numeric($data['saldo'])) {
        $errors[] = "Campo saldo deve ser numérico";
    }

    return count($errors) === 0;
}


function validatePutData(?array $data, ?array &$errors = null): bool
{
    $errors = [];

    if (!is_array($data)) {
        $errors[] = 'JSON inválido ou não enviado';
        return false;
    }

    if (array_key_exists('saldo', $data) && !is_numeric($data['saldo'])) {
        $errors[] = "Campo saldo deve ser numérico";
    }

    if (array_key_exists('titular', $data) && (!is_string($data['titular']) || trim($data['titular']) === '')) {
        $errors[] = "Campo titular deve ser texto não vazio";
    }

    return count($errors) === 0;
}

function buildUpdateFields(array $data, array $allowedFields): array
{
    return array_filter(
        array_intersect_key($data, array_flip($allowedFields)),
        fn($value) => $value !== null && $value !== ''
    );
}

function findContaById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, numero_conta, agencia, titular, saldo, criado_em, atualizado_em FROM CONTAS WHERE id = ?");
    $stmt->execute([$id]);
    $conta = $stmt->fetch(PDO::FETCH_ASSOC);
    return $conta ? formatConta($conta) : null;
}

function findContaByNumeroConta(PDO $pdo, string $numeroConta): ?array
{
    $stmt = $pdo->prepare("SELECT id FROM CONTAS WHERE numero_conta = ?");
    $stmt->execute([trim($numeroConta)]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function findAllContas(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, numero_conta, agencia, titular, saldo, criado_em, atualizado_em FROM CONTAS ORDER BY id");
    return array_map('formatConta', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function formatConta(array $conta): array
{
    $conta['saldo'] = number_format((float)$conta['saldo'], 2, ',', '.');
    return $conta;
}
