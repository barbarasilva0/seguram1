<?php
session_start();
require_once '/home/seguram1/config.php'; 
include '../components/user_info.php'; 

if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

$connJogos = getDBJogos();
$connUtilizadores = getDBUtilizadores();

// Definir charset UTF-8 para evitar problemas de caracteres
$connUtilizadores->set_charset("utf8mb4");
$connJogos->set_charset("utf8mb4");

$idJogo = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idJogo <= 0) {
    die("Erro: ID do quiz inválido.");
}

// Buscar informações do quiz
$queryQuiz = "
    SELECT J.Nome, J.Descricao, J.Data_Criacao, U.Nome AS Criador 
    FROM Jogo J 
    LEFT JOIN seguram1_segura_utilizadores.Utilizador U ON J.Criado_por = U.ID_Utilizador 
    WHERE J.ID_Jogo = ?";
$stmtQuiz = $connJogos->prepare($queryQuiz);
$stmtQuiz->bind_param("i", $idJogo);
$stmtQuiz->execute();
$resultQuiz = $stmtQuiz->get_result();
$quiz = $resultQuiz->fetch_assoc();
$stmtQuiz->close();

if (!$quiz) {
    die("Erro: Quiz não encontrado.");
}

// Buscar a soma dos pontos
$queryPontos = "SELECT SUM(Pontos) AS total_pontos FROM Pergunta WHERE ID_Jogo = ?";
$stmtPontos = $connJogos->prepare($queryPontos);
$stmtPontos->bind_param("i", $idJogo);
$stmtPontos->execute();
$stmtPontos->bind_result($pontosTotais);
$stmtPontos->fetch();
$stmtPontos->close();

$connJogos->close();
$connUtilizadores->close();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['Nome'], ENT_QUOTES, 'UTF-8') ?> - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/jogar_quizz.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
</head>
<body>
<div class="container">
    <div class="sidebar">
        <h1>
            <a href="dashboard.php" style="text-decoration: none; color: inherit;">
                SeguraMente<span class="kids-text">KIDS</span>
            </a>
        </h1>
        <a href="dashboard.php" class="sidebar-item">
                <img src="../imagens/jogos_disponiveis_icon.png" alt="Jogos Disponíveis" style="width: 20px; height: 20px;">
                Jogos Disponíveis
            </a>
            <a href="missoes_semanais.php" class="sidebar-item">
                <img src="../imagens/missoes_icon.png" alt="Missões Semanais" style="width: 20px; height: 20px;">
                Missões Semanais
            </a>
            <a href="historico.php" class="sidebar-item">
                <img src="../imagens/historico_icon.png" alt="Histórico" style="width: 20px; height: 20px;">
                Histórico
            </a>
            <a href="logout.php" class="sidebar-item" id="logout">
                <img src="../imagens/logout_icon.png" alt="Sair" style="width: 20px; height: 20px;">
                Sair
            </a>
        </div>
        

    <div class="content">
        <div class="header">
            <?php include '../components/autocomplete.php'; ?>
            <a href="criar_quizz.php" class="create-quiz">Criar Quizz</a>
            <a href="perfil.php" class="profile">
                <img src="<?php echo !empty($fotoGoogle) ? htmlspecialchars($fotoGoogle, ENT_QUOTES, 'UTF-8') : '../imagens/avatar.png'; ?>" alt="Avatar">
                <span><?php echo htmlspecialchars($_SESSION['nomeUsuario'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        </div>

        <div class="quiz-container">
            <div class="quiz-image">
                <img src="../imagens/quiz-image.webp" alt="<?= htmlspecialchars($quiz['Nome'], ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <div class="quiz-info">
                        <p>Data: <?= htmlspecialchars($quiz['Data_Criacao'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p>Pontos: <?= intval($pontosTotais) ?> pontos</p>
                        <p>Criado por: <?= htmlspecialchars($quiz['Criador'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="quiz-button">
                        <button id="start-quiz-btn">Começar o Quiz</button>
                    </div>
                </div>
            </div>

            <div class="quiz-details">
                <h2><?= htmlspecialchars($quiz['Nome'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars($quiz['Descricao'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Escolha -->
<div class="modal-overlay" id="quiz-mode-modal">
    <div class="modal-content">
        <h2>Como deseja jogar?</h2>
        <div class="modal-buttons">
            <button class="btn-solo" id="play-solo">Jogar Sozinho</button>
            <button class="btn-multi" id="play-multi">Jogar com Outros</button>
            <button class="btn-cancel" id="cancel-quiz">Cancelar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const startQuizBtn = document.getElementById("start-quiz-btn");
    const quizModeModal = document.getElementById("quiz-mode-modal");

    startQuizBtn.addEventListener("click", () => quizModeModal.classList.add("show"));
    document.getElementById("cancel-quiz").addEventListener("click", () => quizModeModal.classList.remove("show"));

    document.getElementById("play-solo").addEventListener("click", () => {
        window.location.href = "quizz.php?id=<?= $idJogo ?>&modo=solo";
    });

    document.getElementById("play-multi").addEventListener("click", function () {
        fetch("criar_lobby.php?id=<?php echo $idJogo; ?>")
            .then(res => res.json())
            .then(data => {
                if (data.sucesso && data.pin) {
                    window.location.href = "lobby.php?id=<?php echo $idJogo; ?>&modo=multi&pin=" + encodeURIComponent(data.pin);
                } else {
                    alert("Erro ao criar o lobby: " + (data.erro || "desconhecido."));
                }
            });
    });
});
</script>
</body>
</html>
