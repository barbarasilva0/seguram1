<?php
require_once '/home/seguram1/config.php';

$connJogos = getDBJogos();

function resetarMissoesSemanais($connJogos) {
    // Deletar missões antigas
    $connJogos->query("DELETE FROM Missao_Semanal");

    // Buscar todos os usuários
    $usuarios = $connJogos->query("SELECT ID_Utilizador FROM Utilizador");

    while ($row = $usuarios->fetch_assoc()) {
        gerarMissoesIniciais($row['ID_Utilizador'], $connJogos);
    }
}

// Executar o reset
resetarMissoesSemanais($connJogos);

echo "Missões semanais resetadas!";
?>
