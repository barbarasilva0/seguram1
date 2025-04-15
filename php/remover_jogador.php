<?php
session_start();
require_once '/home/seguram1/config.php';

header('Content-Type: application/json');

// Permitir apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit();
}

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

$pin = isset($_POST['pin']) ? intval($_POST['pin']) : 0;
$nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : '';

if ($pin <= 0 || $nickname === '') {
    http_response_code(400);
    echo json_encode(['erro' => 'PIN ou nickname inválido']);
    exit();
}

// Limite de segurança
$nickname = substr($nickname, 0, 100);

// Verifica se o quiz já começou
$check = $conn->prepare("SELECT Iniciado FROM Lobby WHERE PIN = ?");
$check->bind_param("i", $pin);
$check->execute();
$check->bind_result($iniciado);
$check->fetch();
$check->close();

if ($iniciado) {
    http_response_code(403);
    echo json_encode(['erro' => 'Não é possível remover jogador após início do quiz.']);
    exit();
}

// Verificar se jogador existe e obter tipo
$stmt = $conn->prepare("SELECT Tipo, ID_Utilizador FROM JogadoresLobby WHERE PIN = ? AND Nickname = ?");
$stmt->bind_param("is", $pin, $nickname);
$stmt->execute();
$stmt->bind_result($tipo, $idUtilizadorTarget);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    http_response_code(404);
    echo json_encode(['erro' => 'Jogador não encontrado no lobby']);
    $conn->close();
    exit();
}

// Verificar se quem está a tentar remover é autorizado
$sessionNickname = $_SESSION['nicknameTemporario'] ?? $_SESSION['nomeUsuario'] ?? $_SESSION['username'] ?? '';
$sessionId = $_SESSION['idUsuario'] ?? null;
$isSelf = ($nickname === $sessionNickname);
$isKickRequest = $_SESSION['isKickRequest'] ?? false;
$isAdmin = false;

// Verificar se o utilizador atual é o criador do lobby
$stmt = $conn->prepare("SELECT Criado_por FROM Lobby WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->bind_result($criador);
$stmt->fetch();
$stmt->close();

if ($sessionId && $sessionId == $criador) {
    $isAdmin = true;
}

// Regras de permissão
if ($tipo === 'registado' && !$isSelf && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sem permissão para remover jogador registado']);
    $conn->close();
    exit();
}

// Remover jogador
$stmt = $conn->prepare("DELETE FROM JogadoresLobby WHERE PIN = ? AND Nickname = ?");
$stmt->bind_param("is", $pin, $nickname);
$stmt->execute();
$stmt->close();

// Se o lobby ficou vazio, apagar lobby e estado
$stmt = $conn->prepare("SELECT COUNT(*) FROM JogadoresLobby WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->bind_result($numRestantes);
$stmt->fetch();
$stmt->close();

if ($numRestantes === 0) {
    $stmt = $conn->prepare("DELETE FROM EstadoQuiz WHERE PIN = ?");
    $stmt->bind_param("i", $pin);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM Lobby WHERE PIN = ?");
    $stmt->bind_param("i", $pin);
    $stmt->execute();
    $stmt->close();

    error_log("[INFO] Lobby $pin encerrado automaticamente (vazio).");
}

$conn->close();
echo json_encode(['sucesso' => true]);
exit();
