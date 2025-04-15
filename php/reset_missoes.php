<?php
require_once '/home/seguram1/config.php'; // Ajuste o caminho conforme necessário

// Conectar ao banco de dados de jogos
$conn = getDBJogos();

// Resetar missões semanais (ajuste conforme necessário)
$sql = "UPDATE Missao_Semanal SET Progresso = 0 WHERE 1";
if ($conn->query($sql) === TRUE) {
    echo "Missões semanais resetadas com sucesso.";
} else {
    echo "Erro ao resetar missões: " . $conn->error;
}

$conn->close();
?>
