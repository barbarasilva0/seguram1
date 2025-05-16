<?php
session_start();
require_once '/home/seguram1/config.php';
require_once __DIR__ . '/../utils/missoes.php';

$idUsuario = $_SESSION['idUsuario'] ?? null;
$nickname = $_SESSION['nomeUsuario'] ?? $_SESSION['username'] ?? 'Jogador';

if (!$idUsuario || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$idJogo = intval($_GET['id']);
$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Verificar se o Jogo existe
$stmt = $conn->prepare("SELECT COUNT(*) FROM Jogo WHERE ID_Jogo = ?");
$stmt->bind_param("i", $idJogo);
$stmt->execute();
$stmt->bind_result($existeJogo);
$stmt->fetch();
$stmt->close();

if ($existeJogo == 0) {
    $conn->close();
    die("Erro: O jogo com ID $idJogo não existe.");
}

// Verificar se já existe histórico
$stmt = $conn->prepare("SELECT COUNT(*) FROM Historico WHERE ID_Utilizador = ? AND ID_Jogo = ?");
$stmt->bind_param("ii", $idUsuario, $idJogo);
$stmt->execute();
$stmt->bind_result($existe);
$stmt->fetch();
$stmt->close();

// Calcular total de perguntas e pontuação máxima
$stmt = $conn->prepare("SELECT COUNT(*), SUM(Pontos) FROM Pergunta WHERE ID_Jogo = ?");
$stmt->bind_param("i", $idJogo);
$stmt->execute();
$stmt->bind_result($totalPerguntas, $pontuacaoMaxima);
$stmt->fetch();
$stmt->close();

$totalPerguntas = $totalPerguntas ?? 0;
$pontuacaoMaxima = $pontuacaoMaxima ?? 0;

// Simular pontuação do jogador
$pontuacaoJogador = $_SESSION['pontuacaoSolo'] ?? round($pontuacaoMaxima * 0.1); 

$stmt = $conn->prepare("INSERT INTO Historico (Data, Pontuacao_Obtida, Estado, ID_Utilizador, ID_Jogo)
                        VALUES (NOW(), ?, 'Concluído', ?, ?)");
$stmt->bind_param("iii", $pontuacaoJogador, $idUsuario, $idJogo);
$stmt->execute();
$stmt->close();

// MISSÕES
if (!verificarMissoesExistentes($idUsuario, $conn)) {
    gerarMissoesIniciais($idUsuario, $conn);
}

// Missão "Jogue 3 quizzes"
atualizarMissoesTodosJogadoresSolo($idUsuario, $conn);

// Missão "80% de acertos"
$percentagem = ($pontuacaoMaxima > 0) ? ($pontuacaoJogador / $pontuacaoMaxima) * 100 : 0;

if ($percentagem >= 80) {
    $stmt = $conn->prepare("UPDATE Missao_Semanal
                            SET Progresso = 1
                            WHERE ID_Utilizador = ? AND LOWER(Nome) LIKE '%80%'");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Resumo do Quiz Solo</title>
    <link rel="stylesheet" href="../css/ranking_final.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="scoreboard-container">
    <h1>Quiz Solo Concluído!</h1>
    <p>Parabéns, <?= htmlspecialchars($nickname) ?>! O teu progresso foi registado no histórico.</p>

    <div class="quiz-fim-resumo">
        <p><strong>Total de perguntas:</strong> <?= intval($totalPerguntas) ?></p>
        <p><strong>Nota:</strong> O modo solo é educativo — esta pontuação não é contabilizada no ranking.</p>
        <p><strong>Missões:</strong> Missões semanais atualizadas com base no desempenho.</p>
    </div>

    <div style="text-align:center; margin-top:40px;">
        <a href="dashboard.php" class="btn-next">Voltar ao Dashboard</a>
    </div>
</div>
</body>
</html>
