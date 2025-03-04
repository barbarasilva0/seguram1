<?php
session_start();

// Conexão com os Bancos de Dados no cPanel via phpMyAdmin
$servername = "localhost";
$username = "seguram1_seguram1";  
$password = "d@V+38y3nWb4FB"; 
$dbUtilizadores = "seguram1_segura_utilizadores";
$dbJogos = "seguram1_segura_jogos";

$connUtilizadores = new mysqli($servername, $username, $password, $dbUtilizadores);
$connJogos = new mysqli($servername, $username, $password, $dbJogos);

// Verificação de conexão
if ($connUtilizadores->connect_error || $connJogos->connect_error) {
    die("Erro na conexão: " . $connUtilizadores->connect_error . " / " . $connJogos->connect_error);
}

$quizzesCompletados = 0;
$nomeUsuario = isset($_SESSION['nomeUsuario']) ? $_SESSION['nomeUsuario'] : "Visitante";
$idUsuario = isset($_SESSION['idUsuario']) ? $_SESSION['idUsuario'] : 0;

// Contar quizzes completados pelo usuário
if ($idUsuario > 0) {
    $query = "SELECT COUNT(*) AS total FROM Historico WHERE ID_Utilizador = ? AND Estado = 'Concluído'";
    $stmt = $connJogos->prepare($query);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $stmt->bind_result($quizzesCompletados);
    $stmt->fetch();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SeguraMente</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h1>SeguraMente</h1>
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
            <!-- Header -->
            <div class="header">
                <div class="search-container">
                    <img src="../imagens/lupa.png" alt="Lupa" class="search-icon">
                    <input type="text" placeholder="Pesquisar...">
                </div>
                <a href="criar_quizz.php" class="create-quiz">Criar Quizz</a>
                <a href="perfil.php" class="profile">
                    <img src="../imagens/avatar.png" alt="Avatar">
                    <span><?php echo htmlspecialchars(mb_convert_encoding($_SESSION['nomeUsuario'], 'UTF-8', 'ISO-8859-1')); ?></span>
                </a>
            </div>
            
            <!-- Quiz Section -->
            <div class="quiz-section">
                <div class="quiz-header">
                    <div class="user-info">
                        <img src="../imagens/avatar.png" alt="Avatar do usuário" class="user-avatar">
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars(mb_convert_encoding($_SESSION['nomeUsuario'], 'UTF-8', 'ISO-8859-1')); ?></span>
                            <div class="user-score-container">
                                <img src="../imagens/flag_icon.png" alt="Flag Icon" class="flag-icon">
                                <div class="user-score">
                                    <span class="score-value"><?php echo $quizzesCompletados; ?></span>
                                    <span class="score-text">Quiz Passed</span>
                                </div>
                            </div>
                        </div>                                             
                    </div>
                </div>

                <a href="jogar_agora_registado.php" class="play-now-button">Jogar Agora</a>

                <?php
                $query = "SELECT ID_Jogo, Nome, Descricao FROM Jogo WHERE ID_Jogo IN (SELECT ID_Jogo FROM Historico WHERE ID_Utilizador = ? OR Estado = 'Disponível')";
                $stmt = $connJogos->prepare($query);
                $stmt->bind_param("i", $idUsuario);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="quiz-card">';
                        echo '<img src="../imagens/quiz-image.png" alt="Quiz">';
                        echo '<div class="quiz-content">';
                        echo '<div class="quiz-title">' . htmlspecialchars($row['Nome']) . '</div>';
                        echo '<div class="quiz-description">' . htmlspecialchars($row['Descricao']) . '</div>';
                        echo '<a href="jogar_quizz.php?id=' . $row['ID_Jogo'] . '" class="quiz-button">Jogar</a>';
                        echo '</div></div>';
                    }
                }
                
                $stmt->close();
                $connUtilizadores->close();
                $connJogos->close();
                ?>
            </div>
        </div>
    </div>
</body>
</html>
