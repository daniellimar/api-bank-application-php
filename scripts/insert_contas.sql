SET
NOCOUNT ON;

DECLARE
@i INT = 1;
WHILE
@i <= 150
BEGIN
    DECLARE
@numero_conta VARCHAR(20) = RIGHT('000000' + CAST(ABS(CHECKSUM(NEWID())) % 900000 + 100000 AS VARCHAR(6)), 6);
    DECLARE
@agencia VARCHAR(10) = CAST(ABS(CHECKSUM(NEWID())) % 9000 + 1000 AS VARCHAR(4));
    DECLARE
@titular VARCHAR(100) = CONCAT('Titular ', @i);
    DECLARE
@saldo DECIMAL(15,2) = CAST(ABS(CHECKSUM(NEWID())) % 1501 + 500 AS DECIMAL(15,2));

INSERT INTO CONTAS (numero_conta, agencia, titular, saldo)
VALUES (@numero_conta, @agencia, @titular, @saldo);

SET
@i = @i + 1;
END;