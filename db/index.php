<?php
require_once '../db.php';

try {
    $pdo = conectar();

    $sqlContas = <<<SQL
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'CONTAS')
BEGIN
    CREATE TABLE CONTAS (
        id INT IDENTITY(1,1) PRIMARY KEY,
        numero_conta VARCHAR(20) NOT NULL UNIQUE,
        agencia VARCHAR(10) NOT NULL,
        titular VARCHAR(100) NOT NULL,
        saldo DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        criado_em DATETIME DEFAULT GETDATE(),
        atualizado_em DATETIME DEFAULT GETDATE()
    );
END
SQL;

    $sqlTransacoes = <<<SQL
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'TRANSACOES')
BEGIN
    CREATE TABLE TRANSACOES (
        id INT IDENTITY(1,1) PRIMARY KEY,
        conta_origem_id INT NOT NULL,
        conta_destino_id INT NOT NULL,
        valor DECIMAL(15,2) NOT NULL CHECK (valor > 0),
        data_transacao DATETIME DEFAULT GETDATE(),
        descricao VARCHAR(255),
        status VARCHAR(20) NOT NULL DEFAULT 'PENDENTE',
        criado_em DATETIME DEFAULT GETDATE(),
        atualizado_em DATETIME DEFAULT GETDATE(),

        CONSTRAINT FK_ContaOrigem FOREIGN KEY (conta_origem_id) REFERENCES CONTAS(id),
        CONSTRAINT FK_ContaDestino FOREIGN KEY (conta_destino_id) REFERENCES CONTAS(id)
    );
END
SQL;

    $pdo->exec($sqlContas);
    $pdo->exec($sqlTransacoes);

    echo "Tabelas criadas com sucesso!";

} catch (PDOException $e) {
    echo "Erro ao criar tabelas: " . $e->getMessage();
}
