<?php

$url = 'http://localhost:8080/transferencia.php';

function enviarTransferencia($url, $contaOrigem, $contaDestino, $valor)
{
    $data = [
        'conta_origem_id' => $contaOrigem,
        'conta_destino_id' => $contaDestino,
        'valor' => $valor,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "Erro: $error\n";
    } else {
        echo "Resposta: $response\n";
    }
}


$multiHandle = curl_multi_init();
$curlHandles = [];

for ($i = 1; $i <= 50; $i++) {
    $contaOrigem = rand(1, 10);
    $contaDestino = rand(11, 20);
    $valor = rand(1, 100);

    $data = json_encode([
        'conta_origem_id' => $contaOrigem,
        'conta_destino_id' => $contaDestino,
        'valor' => $valor,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[] = $ch;
}

$running = 0;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

foreach ($curlHandles as $ch) {
    $response = curl_multi_getcontent($ch);
    echo "Resposta: $response\n";
    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);
