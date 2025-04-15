<?php
require_once '/home/seguram1/config.php';

function atualizarConquista($idUtilizador) {
    $conn = getDBUtilizadores();
    $conn->set_charset("utf8mb4");

    // Obter pontuação atual
    $stmt = $conn->prepare("SELECT Pontuacao_Total FROM Perfil WHERE ID_Utilizador = ?");
    $stmt->bind_param("i", $idUtilizador);
    $stmt->execute();
    $stmt->bind_result($pontuacao);
    $stmt->fetch();
    $stmt->close();

    // Lista de conquistas
    $conquista = "Sem Conquistas";
    if ($pontuacao >= 1000) {
        $conquista = "Lenda Suprema, Guardião da Segurança";
    } elseif ($pontuacao >= 750) {
        $conquista = "Elite Cibernética, Mestre Hacker";
    } elseif ($pontuacao >= 500) {
        $conquista = "Protetor Digital, Vencedor Nato";
    } elseif ($pontuacao >= 300) {
        $conquista = "Veterano Online, Caçador de Bugs";
    } elseif ($pontuacao >= 150) {
        $conquista = "Explorador Virtual, Curioso Nato";
    } elseif ($pontuacao >= 50) {
        $conquista = "Novato Digital, Primeiro Passo";
    }

    // Atualizar conquista no perfil
    $stmt = $conn->prepare("UPDATE Perfil SET Conquistas = ? WHERE ID_Utilizador = ?");
    $stmt->bind_param("si", $conquista, $idUtilizador);
    $stmt->execute();
    $stmt->close();

    $conn->close();
}
?>
