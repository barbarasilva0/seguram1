<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '/home/seguram1/config.php';

$connUtilizadores = getDBUtilizadores();
$connUtilizadores->set_charset("utf8mb4");

$nomeUsuario = isset($_SESSION['nomeUsuario']) ? htmlspecialchars($_SESSION['nomeUsuario'], ENT_QUOTES, 'UTF-8') : "Visitante";
$idUsuario = isset($_SESSION['idUsuario']) ? intval($_SESSION['idUsuario']) : 0;
$fotoGoogle = "";

// Buscar a foto do utilizador (Google ou não)
if ($idUsuario > 0) {
    $stmt = $connUtilizadores->prepare("SELECT Foto_Google FROM Utilizador WHERE ID_Utilizador = ?");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $stmt->bind_result($fotoGoogle);
    $stmt->fetch();
    $stmt->close();
}

$connUtilizadores->close();
?>
