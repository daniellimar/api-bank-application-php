<?php

require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = conectar();

    if ($method === 'POST') {
        processarTransferencia($pdo);
    } else {
        throw new Exception('Método HTTP não permitido', 405);
    }
} catch (PDOException $e) {
    sendResponse(500, ['error' => 'Erro no banco de dados', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    sendResponse($e->getCode() ?: 400, ['error' => $e->getMessage()]);
}

function processarTransferencia(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);
    $dadosValidados = validarDadosTransferencia($data);

    $contaOrigemId = $dadosValidados['conta_origem_id'];
    $contaDestinoId = $dadosValidados['conta_destino_id'];
    $valor = $dadosValidados['valor'];

    try {
        $pdo->beginTransaction();

        $contasOrdenadas = [$contaOrigemId, $contaDestinoId];
        sort($contasOrdenadas);

        foreach ($contasOrdenadas as $contaId) {
            $stmt = $pdo->prepare("SELECT id, saldo FROM CONTAS WITH (UPDLOCK, ROWLOCK) WHERE id = ?");
            $stmt->execute([$contaId]);
            $conta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conta) {
                throw new Exception("Conta com ID $contaId não encontrada");
            }
            ${$contaId === $contaOrigemId ? 'contaOrigem' : 'contaDestino'} = $conta;
        }

        $saldoOrigem = $contaOrigem['saldo'];
        $saldoDestino = $contaDestino['saldo'];

        if (bccomp($saldoOrigem, (string)$valor, 2) < 0) {
            registrarTransacaoCanceladaSeparada($contaOrigemId, $contaDestinoId, $valor, 'Saldo insuficiente para transferência', 'CANCELADA');
            $pdo->rollBack();
            sendResponse(400, ['error' => 'Saldo insuficiente para transferência']);
        }

        $novoSaldoOrigem = bcsub($saldoOrigem, (string)$valor, 2);
        $novoSaldoDestino = bcadd($saldoDestino, (string)$valor, 2);

        atualizarSaldoConta($pdo, $contaOrigemId, $novoSaldoOrigem);
        atualizarSaldoConta($pdo, $contaDestinoId, $novoSaldoDestino);

        registrarTransacao($pdo, $contaOrigemId, $contaDestinoId, $valor, 'Transferência realizada com sucesso', 'CONFIRMADA');

        $pdo->commit();

        sendResponse(201, ['message' => 'Transferência realizada com sucesso']);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendResponse(500, ['error' => 'Erro ao processar transferência', 'details' => $e->getMessage()]);
    }
}

function registrarTransacao(PDO $pdo, int $contaOrigemId, int $contaDestinoId, float $valor, string $descricao, string $status): void
{
    $stmt = $pdo->prepare("INSERT INTO TRANSACOES (conta_origem_id, conta_destino_id, valor, descricao, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$contaOrigemId, $contaDestinoId, $valor, $descricao, $status]);
}

function atualizarSaldoConta(PDO $pdo, int $contaId, float $novoSaldo): void
{
    $stmt = $pdo->prepare("UPDATE CONTAS SET saldo = ? WHERE id = ?");
    $stmt->execute([$novoSaldo, $contaId]);
}

function sendResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validarDadosTransferencia(array $data): array
{
    if (
        !isset($data['conta_origem_id'], $data['conta_destino_id'], $data['valor']) ||
        !filter_var($data['conta_origem_id'], FILTER_VALIDATE_INT) ||
        !filter_var($data['conta_destino_id'], FILTER_VALIDATE_INT)
    ) {
        sendResponse(400, ['error' => 'Dados inválidos para transferência']);
    }

    try {
        $valor = formatarValorParaString($data['valor']);
    } catch (Exception $e) {
        sendResponse(400, ['error' => $e->getMessage()]);
    }

    if (bccomp($valor, '0.00', 2) <= 0) {
        sendResponse(400, ['error' => 'Valor deve ser maior que zero']);
    }

    return [
        'conta_origem_id' => (int)$data['conta_origem_id'],
        'conta_destino_id' => (int)$data['conta_destino_id'],
        'valor' => $valor,
    ];
}

function formatarValorParaString(string $valorStr): string
{
    $valorStr = str_replace(',', '.', $valorStr);
    $valorStr = preg_replace('/[^0-9.]/', '', $valorStr);

    if (!is_numeric($valorStr)) {
        throw new Exception('Valor inválido para transferência');
    }

    return number_format((float)$valorStr, 2, '.', '');
}

function registrarTransacaoCanceladaSeparada(int $contaOrigemId, int $contaDestinoId, float $valor, string $descricao, string $status): void
{
    $pdo2 = conectar();
    try {
        $pdo2->beginTransaction();

        $stmt = $pdo2->prepare("INSERT INTO TRANSACOES (conta_origem_id, conta_destino_id, valor, descricao, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$contaOrigemId, $contaDestinoId, $valor, $descricao, $status]);

        $pdo2->commit();
    } catch (Exception $e) {
        $pdo2->rollBack();
        throw $e;
    }
}
