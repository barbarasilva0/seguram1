<?php
session_start();
require_once '/home/seguram1/config.php';

if (!isset($_GET['pin']) || !is_numeric($_GET['pin'])) {
    die("PIN inválido.");
}

$pin = intval($_GET['pin']);
$perguntaUrl = isset($_GET['idPergunta']) ? intval($_GET['idPergunta']) : 0;

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Obter pergunta atual e ID do jogo
$stmt = $conn->prepare("SELECT Pergunta_Atual, ID_Jogo FROM EstadoQuiz WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->bind_result($perguntaAtual, $idJogo);
$stmt->fetch();
$stmt->close();

if (!$perguntaAtual || !$idJogo) {
    die("Erro: Estado do quiz não encontrado.");
}

// Redirecionar se já mudou de pergunta
if ($perguntaAtual !== $perguntaUrl) {
    header("Location: quizz.php?pin=$pin");
    exit();
}

// Verificar respostas
$stmt = $conn->prepare("SELECT COUNT(*) FROM JogadoresLobby WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$stmt->bind_result($totalJogadores);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(DISTINCT Nickname) FROM RespostasJogador WHERE PIN = ? AND ID_Pergunta = ?");
$stmt->bind_param("ii", $pin, $perguntaAtual);
$stmt->execute();
$stmt->bind_result($respostasDadas);
$stmt->fetch();
$stmt->close();

if ($respostasDadas < $totalJogadores) {
    echo "<p style='text-align:center; font-size:22px; margin-top:100px;'>Aguardando todos os jogadores responderem...</p>";
    echo "<script>setTimeout(() => location.reload(), 2000);</script>";
    $conn->close();
    exit();
}

// Ranking
$stmt = $conn->prepare("SELECT Nickname, SUM(Pontos_Obtidos) AS TotalPontos, MIN(Tempo_Resposta) AS TempoMaisRapido FROM RespostasJogador WHERE PIN = ? AND ID_Pergunta <= ? GROUP BY Nickname ORDER BY TotalPontos DESC, TempoMaisRapido ASC");
$stmt->bind_param("ii", $pin, $perguntaAtual);
$stmt->execute();
$result = $stmt->get_result();

$jogadores = [];
while ($row = $result->fetch_assoc()) {
    $jogadores[] = $row;
}
$stmt->close();

// Criador?
$ehCriador = false;
$idUsuario = $_SESSION['idUsuario'] ?? null;
if ($idUsuario) {
    $stmt = $conn->prepare("SELECT Criado_por FROM Lobby WHERE PIN = ?");
    $stmt->bind_param("i", $pin);
    $stmt->execute();
    $stmt->bind_result($criador);
    $stmt->fetch();
    $stmt->close();
    $ehCriador = ($idUsuario == $criador);
}


$nicknameAtual = $_SESSION['nomeUsuario'] ?? $_SESSION['username'] ?? ($_SESSION['nicknameTemporario'] ?? '');
$anonimo = !isset($_SESSION['idUsuario']);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Scoreboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/scoreboard.css">
</head>
<body>
<div class="scoreboard-container">
    <?php
    // Buscar todas as perguntas do jogo e encontrar o índice da pergunta atual
    $stmt = $conn->prepare("SELECT ID_Pergunta FROM Pergunta WHERE ID_Jogo = ? ORDER BY ID_Pergunta ASC");
    $stmt->bind_param("i", $idJogo);
    $stmt->execute();
    $res = $stmt->get_result();
    $index = 1;
    while ($row = $res->fetch_assoc()) {
        if ((int)$row['ID_Pergunta'] === $perguntaAtual) break;
        $index++;
    }
    $stmt->close();
    ?>
    <h1>Classificação da Pergunta <?= $index ?></h1>


    <div class="scoreboard">
        <?php $pos = 1; foreach ($jogadores as $jogador):
            $classes = [];
            if ($pos === 1) $classes[] = 'gold';
            elseif ($pos === 2) $classes[] = 'silver';
            elseif ($pos === 3) $classes[] = 'bronze';
            if (strcasecmp($jogador['Nickname'], $nicknameAtual) === 0) $classes[] = 'current-player';
        ?>
        <div class="player-card <?= implode(' ', $classes) ?>">
            <div class="player-position">#<?= $pos ?></div>
            <div class="player-info">
                <strong><?= htmlspecialchars($jogador['Nickname']) ?></strong>
                <span><?= intval($jogador['TotalPontos']) ?> pts</span>
                <small><?= number_format(floatval($jogador['TempoMaisRapido']), 2) ?>s</small>
            </div>
        </div>
        <?php $pos++; endforeach; ?>
    </div>

    <?php if ($ehCriador): ?>
        <form method="POST" action="avancar_pergunta.php" id="form-avancar">
            <input type="hidden" name="pin" value="<?= htmlspecialchars($pin) ?>">
            <button type="submit" class="btn-next" id="btn-next">➡ Próxima Pergunta</button>
        </form>
    <?php endif; ?>
</div>

<script>
<?php if ($ehCriador): ?>
document.getElementById("form-avancar").addEventListener("submit", async function (e) {
    e.preventDefault();
    const btn = document.getElementById("btn-next");
    if (btn.disabled) return;
    btn.disabled = true;
    btn.innerText = "Aguarde...";

    const formData = new FormData(this);
    const pin = formData.get("pin");

    try {
        const response = await fetch("avancar_pergunta.php", {
            method: "POST",
            body: formData
        });

        if (!response.ok) throw new Error("Erro HTTP");
        const result = await response.json();

        if (result.sucesso && result.proxima_pergunta) {
            setTimeout(() => {
                window.location.href = `quizz.php?pin=${pin}`;
            }, 1500); // Sincroniza com delay dos outros
        } else if (result.fim) {
            window.location.href = `ranking_final.php?pin=${pin}`;
        } else {
            alert("Erro ao avançar.");
            btn.disabled = false;
            btn.innerText = "➡ Próxima Pergunta";
        }
    } catch (err) {
        alert("Erro de comunicação com o servidor.");
        console.error(err);
        btn.disabled = false;
        btn.innerText = "➡ Próxima Pergunta";
    }
});
<?php else: ?>
// Jogadores não criadores monitoram continuamente o estado
const pin = <?= json_encode($pin) ?>;
const perguntaAtual = <?= json_encode($perguntaAtual) ?>;

setInterval(() => {
    fetch(`estado_quizz.php?pin=${pin}`)
        .then(res => res.json())
        .then(data => {
            if (data.perguntaAtual > perguntaAtual) {
                window.location.href = `quizz.php?pin=${pin}`;
            }

            // Se quiz terminou (perguntaAtual igual à última e já respondeu)
            if (data.fim === true) {
                window.location.href = `ranking_final.php?pin=${pin}`;
            }
        })
        .catch(err => console.error("Erro ao verificar estado do quiz:", err));
}, 2000);
<?php endif; ?>
</script>
</body>
</html>
