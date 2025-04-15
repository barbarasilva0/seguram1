<?php
require_once '/home/seguram1/config.php';

header('Content-Type: application/json');

// 🔒 Validar e sanitizar o PIN
if (!isset($_GET['pin']) || !is_numeric($_GET['pin'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'PIN inválido.']);
    exit();
}

$pin = intval($_GET['pin']);

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// 🔍 Obter a pergunta atual
$stmt = $conn->prepare("SELECT Pergunta_Atual FROM EstadoQuiz WHERE PIN = ?");
$stmt->bind_param("i", $pin);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao obter estado atual do quiz.']);
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->bind_result($perguntaAtual);
$temEstado = $stmt->fetch();
$stmt->close();

if (!$temEstado) {
    http_response_code(404);
    echo json_encode(['erro' => 'Estado do quiz não encontrado.']);
    $conn->close();
    exit();
}

// 📊 Obter ranking até à pergunta atual
$stmt = $conn->prepare("
    SELECT Nickname, 
           SUM(Pontos_Obtidos) AS TotalPontos,
           MIN(Tempo_Resposta) AS TempoMaisRapido
    FROM RespostasJogador
    WHERE PIN = ? AND ID_Pergunta <= ?
    GROUP BY Nickname
    ORDER BY TotalPontos DESC, TempoMaisRapido ASC
");
$stmt->bind_param("ii", $pin, $perguntaAtual);
$stmt->execute();
$result = $stmt->get_result();

$ranking = [];
while ($row = $result->fetch_assoc()) {
    $ranking[] = [
        'nickname' => $row['Nickname'],
        'pontos'   => intval($row['TotalPontos']),
        'tempo'    => number_format((float)$row['TempoMaisRapido'], 2)
    ];
}

$stmt->close();
$conn->close();

// ✅ Resposta final
echo json_encode([
    'perguntaAtual' => (int)$perguntaAtual,
    'ranking' => $ranking
]);
