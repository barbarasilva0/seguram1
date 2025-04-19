<?php
session_start();
require_once '/home/seguram1/config.php';

// Verificar se é admin
if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Verificar se foi enviado um ID de quiz válido
if (!isset($_POST['id_quizz']) || !is_numeric($_POST['id_quizz'])) {
    header("Location: admin_lista_jogos.php?erro=invalid_id");
    exit();
}

$id_quizz = intval($_POST['id_quizz']);

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Verifica se o quiz existe antes de tentar apagar
$stmt = $conn->prepare("SELECT Nome FROM Jogo WHERE ID_Jogo = ?");
$stmt->bind_param("i", $id_quizz);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: admin_lista_jogos.php?erro=not_found");
    exit();
}
$stmt->close();

// Eliminar o quiz
$stmt = $conn->prepare("DELETE FROM Jogo WHERE ID_Jogo = ?");
$stmt->bind_param("i", $id_quizz);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: admin_lista_jogos.php?sucesso=removido");
    exit();
} else {
    $stmt->close();
    $conn->close();
    header("Location: admin_lista_jogos.php?erro=delete_fail");
    exit();
}
?>
