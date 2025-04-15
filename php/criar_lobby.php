<?php
session_start();
require_once '/home/seguram1/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['idUsuario'])) {
    echo json_encode(['erro' => 'Utilizador não autenticado']);
    exit();
}

$idUsuario = intval($_SESSION['idUsuario']);
$idJogo = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idJogo <= 0) {
    echo json_encode(['erro' => 'ID do jogo inválido']);
    exit();
}

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Eliminar lobbies antigos criados por este utilizador para este jogo
$stmtOld = $conn->prepare("SELECT PIN FROM Lobby WHERE ID_Jogo = ? AND Criado_por = ?");
$stmtOld->bind_param("ii", $idJogo, $idUsuario);
$stmtOld->execute();
$stmtOld->bind_result($pinAntigo);
if ($stmtOld->fetch()) {
    $stmtOld->close();

    // Apagar EstadoQuiz
    $delEstado = $conn->prepare("DELETE FROM EstadoQuiz WHERE PIN = ?");
    $delEstado->bind_param("i", $pinAntigo);
    $delEstado->execute();
    $delEstado->close();

    // Apagar JogadoresLobby
    $delJogadores = $conn->prepare("DELETE FROM JogadoresLobby WHERE PIN = ?");
    $delJogadores->bind_param("i", $pinAntigo);
    $delJogadores->execute();
    $delJogadores->close();

    // Apagar o lobby
    $delLobby = $conn->prepare("DELETE FROM Lobby WHERE PIN = ?");
    $delLobby->bind_param("i", $pinAntigo);
    $delLobby->execute();
    $delLobby->close();
} else {
    $stmtOld->close();
}

// Gerar PIN único
do {
    $pin = rand(100000, 999999);
    $check = $conn->prepare("SELECT 1 FROM Lobby WHERE PIN = ?");
    $check->bind_param("i", $pin);
    $check->execute();
    $check->store_result();
    $existe = $check->num_rows > 0;
    $check->close();
} while ($existe);

// Criar novo lobby
$insertLobby = $conn->prepare("INSERT INTO Lobby (ID_Jogo, PIN, Criado_por) VALUES (?, ?, ?)");
$insertLobby->bind_param("iii", $idJogo, $pin, $idUsuario);
if (!$insertLobby->execute()) {
    echo json_encode(['erro' => 'Erro ao criar lobby']);
    $insertLobby->close();
    $conn->close();
    exit();
}
$insertLobby->close();

// Obter primeira pergunta
$stmtPergunta = $conn->prepare("SELECT ID_Pergunta FROM Pergunta WHERE ID_Jogo = ? ORDER BY ID_Pergunta ASC LIMIT 1");
$stmtPergunta->bind_param("i", $idJogo);
$stmtPergunta->execute();
$stmtPergunta->bind_result($primeiraPergunta);
$stmtPergunta->fetch();
$stmtPergunta->close();

if (!$primeiraPergunta) {
    echo json_encode(['erro' => 'Quiz não contém perguntas.']);
    $conn->close();
    exit();
}

// Criar EstadoQuiz
$insertEstado = $conn->prepare("INSERT INTO EstadoQuiz (PIN, ID_Jogo, Pergunta_Atual) VALUES (?, ?, ?)");
$insertEstado->bind_param("iii", $pin, $idJogo, $primeiraPergunta);
if (!$insertEstado->execute()) {
    echo json_encode(['erro' => 'Erro ao inicializar EstadoQuiz.']);
    $insertEstado->close();
    $conn->close();
    exit();
}
$insertEstado->close();

$conn->close();
echo json_encode(['sucesso' => true, 'pin' => $pin]);
exit();
