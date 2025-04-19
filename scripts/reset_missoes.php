<?php
require_once '/home/seguram1/config.php';

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Resetar progresso das missões semanais existentes
$sql = "UPDATE Missao_Semanal SET Progresso = 0";
if ($conn->query($sql) !== TRUE) {
    die("Erro ao resetar missões: " . $conn->error);
}

// (Opcional) Garantir que todos os utilizadores têm missões atribuídas
function gerarMissoesIniciais($idUsuario, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Missao_Semanal WHERE ID_Utilizador = ?");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $stmt->bind_result($qtd);
    $stmt->fetch();
    $stmt->close();

    if ($qtd < 4) {
        // Exemplo de missões básicas
        $missoes = [
            ['Jogue 3 quizzes esta semana', null, 3],
            ['Obtenha 80% de acertos em um quiz', null, 1],
            ['Participe de um jogo multiplayer', null, 1],
            ['Criar um quiz', null, 1]
        ];

        $stmt = $conn->prepare("INSERT INTO Missao_Semanal (Nome, Descricao, Objetivo, Progresso, ID_Utilizador) VALUES (?, ?, ?, 0, ?)");

        foreach ($missoes as $missao) {
            $stmt->bind_param("ssii", $missao[0], $missao[1], $missao[2], $idUsuario);
            $stmt->execute();
        }

        $stmt->close();
    }
}

// Garantir que todos os utilizadores tenham missões
$result = $conn->query("SELECT ID_Utilizador FROM Utilizador");
while ($row = $result->fetch_assoc()) {
    gerarMissoesIniciais($row['ID_Utilizador'], $conn);
}

$conn->close();

echo "✅ Missões semanais reiniciadas com sucesso.";
?>
