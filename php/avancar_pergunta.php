<?php
session_start();
require_once '/home/seguram1/config.php';
header('Content-Type: application/json');

$logPath = '/home/seguram1/logs/avancar.log';

// LOG DE INÍCIO
file_put_contents($logPath, "\n=== NOVA REQUISIÇÃO ===\n", FILE_APPEND);
file_put_contents($logPath, "Timestamp: " . date("Y-m-d H:i:s") . "\n", FILE_APPEND);
file_put_contents($logPath, "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "\n", FILE_APPEND);
file_put_contents($logPath, "Session User ID: " . ($_SESSION['idUsuario'] ?? 'anonimo') . "\n", FILE_APPEND);
file_put_contents($logPath, "POST: " . json_encode($_POST) . "\n", FILE_APPEND);

// Validação
if (!isset($_POST['pin']) || !is_numeric($_POST['pin'])) {
    $erro = ['erro' => 'PIN inválido.'];
    http_response_code(400);
    echo json_encode($erro);
    file_put_contents($logPath, "ERRO: " . json_encode($erro) . "\n", FILE_APPEND);
    exit();
}

$pin = intval($_POST['pin']);
$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Estado atual
$stmt = $conn->prepare("SELECT Pergunta_Atual, ID_Jogo FROM EstadoQuiz WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->bind_result($perguntaAtual, $idJogo);
$temEstado = $stmt->fetch();
$stmt->close();

if (!$temEstado || $perguntaAtual === null || $idJogo === null) {
    $erro = ['erro' => 'Estado do quiz não encontrado para o PIN fornecido.'];
    http_response_code(404);
    echo json_encode($erro);
    file_put_contents($logPath, "ERRO: " . json_encode($erro) . "\n", FILE_APPEND);
    $conn->close();
    exit();
}

// Obter perguntas do jogo
$stmt = $conn->prepare("SELECT ID_Pergunta FROM Pergunta WHERE ID_Jogo = ? ORDER BY ID_Pergunta ASC");
$stmt->bind_param("i", $idJogo);
$stmt->execute();
$result = $stmt->get_result();

$perguntas = [];
while ($row = $result->fetch_assoc()) {
    $perguntas[] = (int)$row['ID_Pergunta'];
}
$stmt->close();

file_put_contents($logPath, "Perguntas encontradas: " . json_encode($perguntas) . "\n", FILE_APPEND);

// Determinar próxima pergunta
$indexAtual = array_search((int)$perguntaAtual, $perguntas);
file_put_contents($logPath, "Pergunta Atual: $perguntaAtual (Index: $indexAtual)\n", FILE_APPEND);

if ($indexAtual === false) {
    $erro = ['erro' => 'Pergunta atual não pertence ao jogo.'];
    http_response_code(500);
    echo json_encode($erro);
    file_put_contents($logPath, "ERRO: " . json_encode($erro) . "\n", FILE_APPEND);
    $conn->close();
    exit();
}

// Verificar fim
if (!isset($perguntas[$indexAtual + 1])) {
    $fim = ['fim' => true, 'mensagem' => 'O quiz chegou ao fim.'];
    echo json_encode($fim);
    file_put_contents($logPath, "FIM: " . json_encode($fim) . "\n", FILE_APPEND);
    $conn->close();
    exit();
}

$novaPergunta = $perguntas[$indexAtual + 1];
file_put_contents($logPath, "Avançar para: $novaPergunta\n", FILE_APPEND);

//️ Atualizar EstadoQuiz
$stmt = $conn->prepare("UPDATE EstadoQuiz SET Pergunta_Atual = ?, Atualizado_em = CURRENT_TIMESTAMP WHERE PIN = ?");
$stmt->bind_param("ii", $novaPergunta, $pin);

if ($stmt->execute()) {
    $resposta = [
        'sucesso' => true,
        'proxima_pergunta' => $novaPergunta,
        'mensagem' => 'Pergunta avançada com sucesso.'
    ];
    echo json_encode($resposta);
    file_put_contents($logPath, "SUCESSO: " . json_encode($resposta) . "\n", FILE_APPEND);
} else {
    $erro = ['erro' => 'Erro ao atualizar a próxima pergunta.'];
    http_response_code(500);
    echo json_encode($erro);
    error_log("Erro MySQL: " . $stmt->error);
    file_put_contents($logPath, "ERRO MYSQL: " . $stmt->error . "\n", FILE_APPEND);
}

$stmt->close();
$conn->close();
exit();
