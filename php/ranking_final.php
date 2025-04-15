<?php
session_start();
require_once '/home/seguram1/config.php';

if (!isset($_GET['pin']) || !is_numeric($_GET['pin'])) {
    die("PIN inválido.");
}

$pin = intval($_GET['pin']);
$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Obter EstadoQuiz
$stmt = $conn->prepare("SELECT ID_Jogo, Pergunta_Atual FROM EstadoQuiz WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->bind_result($idJogo, $ultimaPergunta);
$stmt->fetch();
$stmt->close();

if (!$idJogo || !$ultimaPergunta) {
    $conn->close();
    die("Erro: Estado do quiz não encontrado.");
}

// Verificar se todos já responderam
$stmt = $conn->prepare("SELECT COUNT(*) FROM JogadoresLobby WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->bind_result($totalJogadores);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(DISTINCT Nickname) FROM RespostasJogador WHERE PIN = ? AND ID_Pergunta = ?");
$stmt->bind_param("ii", $pin, $ultimaPergunta);
$stmt->execute();
$stmt->bind_result($respondidos);
$stmt->fetch();
$stmt->close();

if ($respondidos < $totalJogadores) {
    echo "<p style='text-align:center;font-size:22px;margin-top:100px;'>Aguardando todos os jogadores responderem...</p>";
    echo "<script>setTimeout(() => location.reload(), 3000);</script>";
    $conn->close();
    exit();
}

// Obter todos os jogadores do lobby
$stmt = $conn->prepare("
    SELECT jl.Nickname, jl.ID_Utilizador, COALESCE(SUM(rj.Pontos_Obtidos), 0) AS TotalPontos
    FROM JogadoresLobby jl
    LEFT JOIN RespostasJogador rj ON jl.PIN = rj.PIN AND jl.Nickname = rj.Nickname
    WHERE jl.PIN = ?
    GROUP BY jl.Nickname, jl.ID_Utilizador
");
$stmt->bind_param("i", $pin);
$stmt->execute();
$result = $stmt->get_result();

$jogadores = [];
while ($row = $result->fetch_assoc()) {
    $jogadores[] = $row;

    // Jogadores registados
    if (!empty($row['ID_Utilizador'])) {
        $idUtilizador = intval($row['ID_Utilizador']);
        $pontuacaoOriginal = intval($row['TotalPontos']);
        $pontuacao = round($pontuacaoOriginal * 0.4); 
    
        // Historico
        $h = $conn->prepare("INSERT INTO Historico (Data, Pontuacao_Obtida, Estado, ID_Utilizador, ID_Jogo)
                             VALUES (NOW(), ?, 'Concluído', ?, ?)");
        $h->bind_param("iii", $pontuacao, $idUtilizador, $idJogo);
        $h->execute();
        $h->close();
    
        // Ranking
        $r = $conn->prepare("SELECT ID_Ranking FROM Ranking WHERE ID_Utilizador = ?");
        $r->bind_param("i", $idUtilizador);
        $r->execute();
        $r->store_result();
    
        if ($r->num_rows > 0) {
            $u = $conn->prepare("UPDATE Ranking SET Pontuacao = Pontuacao + ? WHERE ID_Utilizador = ?");
            $u->bind_param("ii", $pontuacao, $idUtilizador);
            $u->execute();
            $u->close();
        } else {
            $i = $conn->prepare("INSERT INTO Ranking (Posicao, Pontuacao, ID_Utilizador) VALUES (0, ?, ?)");
            $i->bind_param("ii", $pontuacao, $idUtilizador);
            $i->execute();
            $i->close();
        }
        $r->close();
    
        // Conexão com a base de utilizadores
        $connUtilizadores = getDBUtilizadores();

        // Atualizar pontuação total no perfil do utilizador
        $p = $connUtilizadores->prepare("UPDATE Perfil SET Pontuacao_Total = Pontuacao_Total + ? WHERE ID_Utilizador = ?");
        $p->bind_param("ii", $pontuacao, $idUtilizador);
        $p->execute();
        $p->close();
        
        $connUtilizadores->close();
    }
}
$stmt->close();

// Atualizar posições
$conn->query("SET @rownum := 0");
$conn->query("UPDATE Ranking r JOIN (
    SELECT ID_Ranking, @rownum := @rownum + 1 AS pos
    FROM Ranking ORDER BY Pontuacao DESC
) ranked ON r.ID_Ranking = ranked.ID_Ranking
SET r.Posicao = ranked.pos");

// Atualizar missões
require_once __DIR__ . '/../utils/missoes.php';
$connJogos = getDBJogos();
if ($connJogos) {
    atualizarMissoesTodosJogadores($pin, $connJogos);
    $connJogos->close();
}

$conn->close();

// Ordenar
usort($jogadores, function($a, $b) {
    if ($b['TotalPontos'] === $a['TotalPontos']) {
        return $a['TempoMaisRapido'] <=> $b['TempoMaisRapido'];
    }
    return $b['TotalPontos'] - $a['TotalPontos'];
});

// Verificar se utilizador está registado
$anonimo = empty($_SESSION['idUsuario']);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Ranking Final</title>
    <link rel="stylesheet" href="../css/ranking_final.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="scoreboard-container">
    <h1>Ranking Final do Quiz</h1>

    <table class="scoreboard-table">
        <thead>
        <tr>
            <th>Posição</th>
            <th>Jogador</th>
            <th>Pontuação Total</th>
        </tr>
        </thead>
            <tbody>
            <?php 
            $pos = 1;
            $connUtil = getDBUtilizadores();
            
            foreach ($jogadores as $j): 
                $conquistas = '';
                if (!empty($j['ID_Utilizador'])) {
                    $stmtC = $connUtil->prepare("SELECT Conquistas FROM Perfil WHERE ID_Utilizador = ?");
                    $stmtC->bind_param("i", $j['ID_Utilizador']);
                    $stmtC->execute();
                    $stmtC->bind_result($conquistas);
                    $stmtC->fetch();
                    $stmtC->close();
                }
            ?>
                <tr>
                    <td><?= $pos++ ?></td>
                    <td>
                        <div>
                            <?= htmlspecialchars($j['Nickname']) ?>
                            <?php if (!empty($conquistas)): ?>
                                <div class="conquistas">
                                    <?= htmlspecialchars($conquistas) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= intval($j['TotalPontos']) ?> pts</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
    </table>

    <div style="text-align:center; margin-top:40px;">
        <?php if (!$anonimo): ?>
            <a href="dashboard.php" class="btn-next">Voltar ao Dashboard</a>
        <?php endif; ?>
    </div>

    <?php if ($anonimo): ?>
        <div style="text-align:center; margin-top:30px;">
            <p>Gostaste do jogo? Cria uma conta para acompanhar tua pontuação e ganhar conquistas! 🎉</p>
            <a href="register.php" class="btn-next">Criar Conta</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
