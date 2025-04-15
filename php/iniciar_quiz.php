<?php
require_once '/home/seguram1/config.php';
header('Content-Type: application/json');

// Validar PIN
if (!isset($_POST['pin']) || !is_numeric($_POST['pin'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'PIN inválido.']);
    exit();
}

$pin = intval($_POST['pin']);
$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Atualizar estado do lobby
$stmt = $conn->prepare("UPDATE Lobby SET Iniciado = 1 WHERE PIN = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao preparar query UPDATE Lobby.']);
    $conn->close();
    exit();
}
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->close();

if ($conn->affected_rows <= 0) {
    http_response_code(404);
    echo json_encode(['erro' => 'Lobby não encontrado ou já iniciado.']);
    $conn->close();
    exit();
}

// Verificar se já existe EstadoQuiz
$stmt = $conn->prepare("SELECT COUNT(*) FROM EstadoQuiz WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->bind_result($existe);
$stmt->fetch();
$stmt->close();

// Só criar se não existir
if ($existe == 0) {
    // Obter ID_Jogo
    $stmt = $conn->prepare("SELECT ID_Jogo FROM Lobby WHERE PIN = ?");
    $stmt->bind_param("i", $pin);
    $stmt->execute();
    $stmt->bind_result($idJogo);
    $stmt->fetch();
    $stmt->close();

    if (empty($idJogo)) {
        http_response_code(500);
        echo json_encode(['erro' => 'ID do jogo não encontrado para este lobby.']);
        $conn->close();
        exit();
    }

    // Obter primeira pergunta
    $stmt = $conn->prepare("SELECT ID_Pergunta FROM Pergunta WHERE ID_Jogo = ? ORDER BY ID_Pergunta ASC LIMIT 1");
    $stmt->bind_param("i", $idJogo);
    $stmt->execute();
    $stmt->bind_result($primeiraPergunta);
    $stmt->fetch();
    $stmt->close();

    if (empty($primeiraPergunta)) {
        http_response_code(500);
        echo json_encode(['erro' => 'Não foi encontrada a primeira pergunta do quiz.']);
        $conn->close();
        exit();
    }

    // Criar EstadoQuiz
    $stmt = $conn->prepare("INSERT INTO EstadoQuiz (PIN, ID_Jogo, Pergunta_Atual) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $pin, $idJogo, $primeiraPergunta);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao criar Estado do Quiz.']);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Log completo após execução
    error_log("[DEBUG] EstadoQuiz criado: PIN=$pin, ID_Jogo=$idJogo, PrimeiraPergunta=$primeiraPergunta");
} else {
    error_log("[DEBUG] EstadoQuiz já existia para PIN=$pin");
}

$conn->close();
echo json_encode(['sucesso' => true]);
exit();
