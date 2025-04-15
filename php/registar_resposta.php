<?php
session_start();
require_once '/home/seguram1/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit();
}

$pin         = isset($_POST['pin']) ? intval($_POST['pin']) : 0;
$idPergunta  = isset($_POST['id_pergunta']) ? intval($_POST['id_pergunta']) : 0;
$resposta    = isset($_POST['resposta']) ? trim($_POST['resposta']) : null;
$tempoGasto  = isset($_POST['tempo']) ? floatval($_POST['tempo']) : null;

if (!$pin || !$idPergunta || $resposta === null || $tempoGasto === null) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros incompletos.']);
    exit();
}

$idUtilizador = $_SESSION['idUsuario'] ?? null;
$nickname     = null;

if ($idUtilizador) {
    $nickname = $_SESSION['nomeUsuario'] ?? $_SESSION['username'] ?? "Jogador_{$idUtilizador}";
} else {
    $nickname = $_SESSION['nicknameTemporario'] ?? 'Convidado';
}

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Verifica se o jogador já respondeu
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM RespostasJogador 
    WHERE PIN = ? AND ID_Pergunta = ? AND Nickname = ?
");
$stmt->bind_param("iis", $pin, $idPergunta, $nickname);
$stmt->execute();
$stmt->bind_result($jaRespondeu);
$stmt->fetch();
$stmt->close();

if ($jaRespondeu > 0) {
    http_response_code(409);
    echo json_encode(['erro' => 'Resposta já foi registada anteriormente.']);
    $conn->close();
    exit();
}

error_log("[DEBUG] PIN={$pin}, ID_Pergunta={$idPergunta}, Resposta={$resposta}, Tempo={$tempoGasto}, ID_User={$idUtilizador}, Nick={$nickname}");

// Buscar resposta correta
$stmt = $conn->prepare("SELECT Resposta_Correta, Pontos FROM Pergunta WHERE ID_Pergunta = ?");
$stmt->bind_param("i", $idPergunta);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['erro' => 'Pergunta não encontrada.']);
    $conn->close();
    exit();
}

$pergunta = $result->fetch_assoc();
$respostaCorreta = trim($pergunta['Resposta_Correta']);
$pontosMaximos   = intval($pergunta['Pontos']);
$stmt->close();

// Calcular pontuação
$correta = strcasecmp($resposta, $respostaCorreta) === 0 ? 1 : 0;
$pontuacaoRecebida = 0;

if ($correta) {
    $tempoLimite = 60.0;
    $bonus = max(0.5, 1 - ($tempoGasto / $tempoLimite));
    $pontuacaoRecebida = round($pontosMaximos * $bonus);
}

// Gravar resposta
$stmt = $conn->prepare("
    INSERT INTO RespostasJogador 
    (PIN, ID_Utilizador, Nickname, ID_Pergunta, Resposta_Dada, Correta, Tempo_Resposta, Pontos_Obtidos)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("iisisiid", $pin, $idUtilizador, $nickname, $idPergunta, $resposta, $correta, $tempoGasto, $pontuacaoRecebida);
$stmt->execute();
$stmt->close();

// Se utilizador estiver autenticado e acertou, atualizar perfil e missões
if ($idUtilizador && $correta) {
    $connUtilizadores = getDBUtilizadores();
    $connUtilizadores->set_charset("utf8mb4");

    $update = $connUtilizadores->prepare("
        UPDATE Perfil SET Pontuacao_Total = Pontuacao_Total + ? 
        WHERE ID_Utilizador = ?
    ");
    $update->bind_param("ii", $pontuacaoRecebida, $idUtilizador);
    $update->execute();
    $update->close();

    $missao = $connUtilizadores->prepare("
        UPDATE Missao_Semanal 
        SET Progresso = Progresso + 1 
        WHERE ID_Utilizador = ? AND Objetivo LIKE '%responder%'
    ");
    $missao->bind_param("i", $idUtilizador);
    $missao->execute();
    $missao->close();

    $connUtilizadores->close();
}

$conn->close();

echo json_encode([
    'sucesso' => true,
    'correta' => $correta,
    'pontos'  => $pontuacaoRecebida
]);
exit();
