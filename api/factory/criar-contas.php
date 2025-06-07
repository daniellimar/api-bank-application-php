<?php
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

try {
    $pdo = conectar();

    $pdo->beginTransaction();

    $quantidade = 20;
    $contasCriadas = [];

    for ($i = 1; $i <= $quantidade; $i++) {
        $numero_conta = str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $agencia = rand(1000, 9999);
        $titular = "Titular $i";
        $saldo = rand(500, 2000);

        $stmt = $pdo->prepare("
            INSERT INTO master.dbo.CONTAS (numero_conta, agencia, titular, saldo, criado_em, atualizado_em)
            VALUES (:numero_conta, :agencia, :titular, :saldo, GETDATE(), GETDATE())
        ");

        $stmt->execute([
            ':numero_conta' => $numero_conta,
            ':agencia' => $agencia,
            ':titular' => $titular,
            ':saldo' => $saldo,
        ]);

        $contasCriadas[] = [
            'id' => $pdo->lastInsertId(),
            'numero_conta' => $numero_conta,
            'agencia' => $agencia,
            'titular' => $titular,
            'saldo' => $saldo
        ];
    }

    $pdo->commit();

    echo json_encode([
        'message' => 'Contas criadas com sucesso!',
        'contas' => $contasCriadas
    ]);
} catch (PDOException $e) {
    $pdo?->rollBack();
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao criar contas',
        'details' => $e->getMessage()
    ]);
}
