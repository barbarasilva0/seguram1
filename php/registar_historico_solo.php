<?php
session_start();
require_once '/home/seguram1/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['idUsuario']) || !isset($_POST['idJogo']) || !isset($_POST['pontuacao'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros ausentes.']);
    exit();
}

$idUsuario = intval($_SESSION['idUsuario']);
$idJogo = intval($_POST['idJogo']);
$pontuacao = intval($_POST['pontuacao']);

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare("INSERT INTO Historico (Data, Pontuacao_Obtida, Estado, ID_Utilizador, ID_Jogo)
                        VALUES (NOW(), ?, 'Concluído', ?, ?)");
$stmt->bind_param("iii", $pontuacao, $idUsuario, $idJogo);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['sucesso' => true]);
exit();
