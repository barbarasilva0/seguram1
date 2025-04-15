<?php
require_once '/home/seguram1/config.php';
header('Content-Type: application/json');

// Validar o PIN
if (!isset($_GET['pin']) || !is_numeric($_GET['pin'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'PIN inválido']);
    exit();
}

$pin = intval($_GET['pin']);

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Verificar se o lobby ainda existe e se já foi iniciado
$stmt = $conn->prepare("SELECT Iniciado FROM Lobby WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->bind_result($iniciadoRaw);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    $conn->close();
    http_response_code(404);
    echo json_encode(['erro' => 'Lobby não encontrado']);
    exit();
}

$iniciado = (bool)$iniciadoRaw;



// Obter lista atualizada de jogadores
$players = [];
$stmt = $conn->prepare("SELECT Nickname FROM JogadoresLobby WHERE PIN = ? ORDER BY ID_Entrada ASC");
$stmt->bind_param("i", $pin);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $players[] = $row['Nickname'];
}
$stmt->close();
$conn->close();

// Resposta
echo json_encode([
    'players' => $players,
    'iniciado' => $iniciado
]);
