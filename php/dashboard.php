<?php
session_start();
require_once '/home/seguram1/config.php';
include '../components/user_info.php'; 

// Conectar ao banco de dados de utilizadores e jogos
$connUtilizadores = getDBUtilizadores();
$connJogos = getDBJogos();

// Definir charset UTF-8 para evitar problemas de caracteres
$connUtilizadores->set_charset("utf8mb4");
$connJogos->set_charset("utf8mb4");

// Verificar se o usuário está logado
$nomeUsuario = isset($_SESSION['nomeUsuario']) ? htmlspecialchars($_SESSION['nomeUsuario'], ENT_QUOTES, 'UTF-8') : "Visitante";
$idUsuario = isset($_SESSION['idUsuario']) ? intval($_SESSION['idUsuario']) : 0;

// Contar quizzes completados pelo usuário
$quizzesCompletados = 0;
if ($idUsuario > 0) {
    $query = "SELECT COUNT(*) AS total FROM Historico WHERE ID_Utilizador = ? AND Estado = 'Concluído'";
    if ($stmt = $connJogos->prepare($query)) {
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $stmt->bind_result($quizzesCompletados);
        $stmt->fetch();
        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
    
</head>
<body>
    <div class="container">
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
            <!-- Header -->
            <div class="header">
                <?php include '../components/autocomplete.php'; ?>



                <a href="criar_quizz.php" class="create-quiz">Criar Quizz</a>
                <a href="perfil.php" class="profile">
                    <img src="<?php echo !empty($fotoGoogle) ? htmlspecialchars($fotoGoogle, ENT_QUOTES, 'UTF-8') : '../imagens/avatar.png'; ?>" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['nomeUsuario'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            </div>
            
            <!-- Quiz Section -->
            <div class="quiz-section">
                <div class="quiz-header">
                    <div class="user-info">
                        <img src="<?php echo !empty($fotoGoogle) ? htmlspecialchars($fotoGoogle, ENT_QUOTES, 'UTF-8') : '../imagens/avatar.png'; ?>" alt="Avatar do usuário" class="user-avatar">
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nomeUsuario'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <div class="user-score-container">
                                <img src="../imagens/flag_icon.png" alt="Flag Icon" class="flag-icon">
                                <div class="user-score">
                                    <span class="score-value"><?php echo $quizzesCompletados; ?></span>
                                    <span class="score-text">Quiz Concluídos</span>
                                </div>
                            </div>
                        </div>                                             
                    </div>
                </div>

                <a href="jogar_agora_registado.php" class="play-now-button">Jogar Agora</a>
                
                <?php
                // Apenas jogos "Ativo" e "Aprovado"
                $query = "SELECT ID_Jogo, Nome, Descricao FROM Jogo WHERE Estado IN ('Ativo', 'Aprovado')";
                if ($stmt = $connJogos->prepare($query)) {
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<div class="quiz-card">';
                            echo '<img src="../imagens/quiz-image.webp" alt="Quiz">';
                            echo '<div class="quiz-content">';
                            echo '<div class="quiz-title">' . htmlspecialchars($row['Nome'], ENT_QUOTES, 'UTF-8') . '</div>';
                            echo '<div class="quiz-description">' . htmlspecialchars($row['Descricao'], ENT_QUOTES, 'UTF-8') . '</div>';
                            echo '<a href="jogar_quizz.php?id=' . intval($row['ID_Jogo']) . '" class="quiz-button">Jogar</a>';
                            echo '</div></div>';
                        }
                    } else {
                        echo "<p>Nenhum quiz disponível no momento.</p>";
                    }
                    $stmt->close();
                }
                
                $connUtilizadores->close();
                $connJogos->close();
                ?>


            </div>
        </div>
    </div>
    
        <!-- Modal de Logout -->
    <div class="modal-overlay" id="modal">
        <div class="modal-content">
            <h2>Tem certeza que deseja sair?</h2>
            <div class="modal-buttons">
                <button class="btn-yes" id="confirmYes">Sim</button>
                <button class="btn-no" id="confirmNo">Não</button>
            </div>
        </div>
    </div>

    <script>
        const logoutLink = document.getElementById('logout');
        const modal = document.getElementById('modal');
        const confirmYes = document.getElementById('confirmYes');
        const confirmNo = document.getElementById('confirmNo');

        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.add('show');
        });

        confirmYes.addEventListener('click', () => {
            window.location.href = 'logout.php';
        });

        confirmNo.addEventListener('click', () => {
            modal.classList.remove('show');
        });
    </script>
</body>
</html>
