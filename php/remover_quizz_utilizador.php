<?php
session_start();
require_once '/home/seguram1/config.php';

// Verificar se o utilizador está autenticado
if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

$idUsuario = (int) $_SESSION['idUsuario'];

// Validar ID do quiz recebido
if (!isset($_POST['id_quizz']) || !is_numeric($_POST['id_quizz'])) {
    header("Location: quizzes_criados.php?erro=invalid_id");
    exit();
}

$id_quizz = intval($_POST['id_quizz']);

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Verificar se o quiz pertence ao utilizador
$stmt = $conn->prepare("SELECT ID_Jogo FROM Jogo WHERE ID_Jogo = ? AND Criado_por = ?");
$stmt->bind_param("ii", $id_quizz, $idUsuario);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // O quiz não pertence a este utilizador
    $stmt->close();
    $conn->close();
    header("Location: quizzes_criados.php?erro=unauthorized");
    exit();
}
$stmt->close();

// Eliminar o quiz
$stmt = $conn->prepare("DELETE FROM Jogo WHERE ID_Jogo = ?");
$stmt->bind_param("i", $id_quizz);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: quizzes_criados.php?sucesso=removido");
    exit();
} else {
    $stmt->close();
    $conn->close();
    header("Location: quizzes_criados.php?erro=delete_fail");
    exit();
}
?>
