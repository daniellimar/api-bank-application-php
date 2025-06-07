<?php

$baseApiUrl = 'http://127.0.0.1:8080/api';
$urlContas = $baseApiUrl . '/contas';
$urlTransferencia = $baseApiUrl . '/transferencia.php';

function pegarContas($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        die("Erro ao buscar contas: $error\n");
    }

    $data = json_decode($response, true);
    if ($data === null) {
        die("Erro ao decodificar JSON das contas\n");
    }

    return $data;
}

$contasData = pegarContas($urlContas);

$contasIds = array_column($contasData, 'id');

if (count($contasIds) < 2) {
    die("Erro: É necessário pelo menos 2 contas para testar transferência.\n");
}

echo "Contas carregadas: " . implode(', ', $contasIds) . "\n";

function pegarContaDestino(array $contasIds, int $contaOrigem)
{
    $destinos = array_filter($contasIds, fn($id) => $id !== $contaOrigem);
    return $destinos[array_rand($destinos)];
}

$multiHandle = curl_multi_init();
$curlHandles = [];

for ($i = 0; $i < 50; $i++) {
    $contaOrigem = $contasIds[array_rand($contasIds)];
    $contaDestino = pegarContaDestino($contasIds, $contaOrigem);
    $valor = rand(1, 100);

    $data = json_encode([
        'conta_origem_id' => $contaOrigem,
        'conta_destino_id' => $contaDestino,
        'valor' => $valor,
    ]);

    $ch = curl_init($urlTransferencia);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[] = $ch;
}

$running = null;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

foreach ($curlHandles as $ch) {
    $response = curl_multi_getcontent($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "HTTP $httpCode - Resposta: $response\n";

    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);
