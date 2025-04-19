<?php
require_once '/home/seguram1/config.php';
session_start();

header('Content-Type: application/json');

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// 1. Verificação de sessão e permissões
if (!isset($_SESSION['idUsuario'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

$idUsuario = intval($_SESSION['idUsuario']);
$pin = isset($_POST['pin']) ? intval($_POST['pin']) : 0;
$nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : '';

if ($pin <= 0 || $nickname === '') {
    http_response_code(400);
    echo json_encode(['erro' => 'PIN ou nickname inválido.']);
    exit;
}

// 2. Confirmar se é o criador do lobby
$stmt = $conn->prepare("SELECT Criado_por FROM Lobby WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->bind_result($criador);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['erro' => 'Lobby não encontrado.']);
    $stmt->close();
    exit;
}
$stmt->close();

if ($criador !== $idUsuario) {
    http_response_code(403);
    echo json_encode(['erro' => 'Apenas o criador do lobby pode expulsar jogadores.']);
    exit;
}

// 3. Confirmar existência do jogador no lobby
$stmt = $conn->prepare("SELECT ID_Utilizador, Tipo FROM JogadoresLobby WHERE PIN = ? AND Nickname = ?");
$stmt->bind_param("is", $pin, $nickname);
$stmt->execute();
$stmt->bind_result($idAlvo, $tipoAlvo);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    http_response_code(404);
    echo json_encode(['erro' => 'Jogador não encontrado no lobby.']);
    exit;
}

// 4. Prevenir que o criador se expulse a si mesmo
if ($idAlvo == $idUsuario) {
    http_response_code(403);
    echo json_encode(['erro' => 'Você não pode expulsar a si próprio.']);
    exit;
}

// 5. Expulsar jogador
$stmt = $conn->prepare("DELETE FROM JogadoresLobby WHERE PIN = ? AND Nickname = ?");
$stmt->bind_param("is", $pin, $nickname);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo json_encode(['sucesso' => true, 'mensagem' => "Jogador '$nickname' expulso com sucesso."]);
    exit;
} else {
    error_log("Erro ao expulsar jogador do lobby: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao expulsar jogador.']);
    $stmt->close();
    $conn->close();
    exit;
}
