<?php
require_once '/home/seguram1/config.php';
header('Content-Type: application/json');

if (!isset($_GET['pin']) || !is_numeric($_GET['pin']) ||
    !isset($_GET['id_pergunta']) || !is_numeric($_GET['id_pergunta'])) {
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit();
}

$pin = intval($_GET['pin']);
$idPergunta = intval($_GET['id_pergunta']);

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Obter todos os jogadores que estão efetivamente ativos ou responderam algo antes
$nicknames = [];

// Jogadores no lobby (ainda presentes)
$stmt = $conn->prepare("SELECT Nickname FROM JogadoresLobby WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $nicknames[$row['Nickname']] = true;
}
$stmt->close();

// Jogadores que já responderam antes (mesmo que tenham saído)
$stmt = $conn->prepare("SELECT DISTINCT Nickname FROM RespostasJogador WHERE PIN = ? AND ID_Pergunta < ?");
$stmt->bind_param("ii", $pin, $idPergunta);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $nicknames[$row['Nickname']] = true;
}
$stmt->close();

$totalJogadores = count($nicknames);

// Agora conta quantos já responderam a esta pergunta
$stmt = $conn->prepare("SELECT COUNT(DISTINCT Nickname) FROM RespostasJogador WHERE PIN = ? AND ID_Pergunta = ?");
$stmt->bind_param("ii", $pin, $idPergunta);
$stmt->execute();
$stmt->bind_result($respostasDadas);
$stmt->fetch();
$stmt->close();

$conn->close();

echo json_encode([
    'todosResponderam' => $respostasDadas >= $totalJogadores
]);
