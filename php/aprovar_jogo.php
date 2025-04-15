<?php
session_start();
require_once '/home/seguram1/config.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

$nomeUsuario = isset($_SESSION['nomeUsuario']) ? htmlspecialchars($_SESSION['nomeUsuario'], ENT_QUOTES, 'UTF-8') : "Visitante";
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovar Jogo/Quizz - SeguraMente</title>
    <link rel="stylesheet" href="../css/aprovar_jogo.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h1>SeguraMente<span class="kids-text">KIDS</span></h1>
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

        <!-- Content -->
        <div class="content">
            <h1 class="title">Criar o Jogo/Quizz</h1>
            <p class="message">
                O jogo/quiz foi cuidadosamente enviado para um processo de avaliação, com o propósito de garantir que está em conformidade com todas as normas aplicáveis e que oferece conteúdos relevantes, rigorosos e baseados em informações verificadas sobre segurança digital. Este passo é essencial para assegurar a qualidade da experiência e a credibilidade do conhecimento partilhado, promovendo uma abordagem responsável e educativa sobre este tema tão importante nos dias de hoje.
            </p>
            <a href="dashboard.php" class="btn">Voltar à página inicial</a>
        </div>
    </div>
</body>
</html>
