<?php
session_start();
require_once '/home/seguram1/config.php'; 
include '../components/user_info.php'; 

// Verificação de sessão do utilizador
if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

// Conectar ao banco de dados usando config.php
$connUtilizadores = getDBUtilizadores();
$connJogos = getDBJogos();

// Obtém o ID do utilizador autenticado
$idUsuario = (int) $_SESSION['idUsuario'];

// Buscar nome do utilizador garantindo correta codificação UTF-8
$nomeUsuario = "Utilizador";
$stmt = $connUtilizadores->prepare("SELECT Nome FROM Utilizador WHERE ID_Utilizador = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($nomeUsuario);
$stmt->fetch();
$stmt->close();

// Certificar que o nome está corretamente formatado para evitar caracteres estranhos
$nomeUsuario = htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8');

// Contar quizzes concluídos pelo utilizador
$quizzesCompletados = 0;
$stmt = $connJogos->prepare("SELECT COUNT(*) FROM Historico WHERE ID_Utilizador = ? AND Estado = 'Concluído'");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($quizzesCompletados);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missões Semanais - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/missoes_semanais.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
    
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
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
            <a href="#" class="sidebar-item" id="logout">
                <img src="../imagens/logout_icon.png" alt="Sair" style="width: 20px; height: 20px;">
                Sair
            </a>
        </div>
        
        <!-- Conteúdo Principal -->
        <div class="content">
            <!-- Cabeçalho -->
            <div class="header">
                <?php include '../components/autocomplete.php'; ?>

                <a href="criar_quizz.php" class="create-quiz">Criar Quizz</a>
                <a href="perfil.php" class="profile">
                    <img src="<?php echo !empty($fotoGoogle) ? htmlspecialchars($fotoGoogle, ENT_QUOTES, 'UTF-8') : '../imagens/avatar.png'; ?>" alt="Avatar">
                    <span><?php echo $nomeUsuario; ?></span>
                </a>
            </div>
            
            <!-- Secção de Missões -->
            <div class="quiz-section">
                <div class="quiz-header">
                    <div class="user-info">
                        <img src="<?php echo !empty($fotoGoogle) ? htmlspecialchars($fotoGoogle, ENT_QUOTES, 'UTF-8') : '../imagens/avatar.png'; ?>" alt="Avatar do usuário" class="user-avatar">
                        <div class="user-details">
                            <span class="user-name"><?php echo $nomeUsuario; ?></span>
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
                
                <!-- Lista de Missões -->
                <div class="missions-section">
                    <div class="missions-header">Missões Semanais</div>
                    <?php
                    // Buscar missões semanais do utilizador
                    $stmt = $connJogos->prepare("SELECT Nome, Progresso, Objetivo FROM Missao_Semanal WHERE ID_Utilizador = ?");
                    $stmt->bind_param("i", $idUsuario);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="mission-card">';
                        echo '<span>' . htmlspecialchars($row['Nome'], ENT_QUOTES, 'UTF-8') . '</span>';
                        echo '<span class="progress">' . htmlspecialchars($row['Progresso'], ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($row['Objetivo'], ENT_QUOTES, 'UTF-8') . ')</span>';
                        echo '</div>';
                    }

                    $stmt->close();
                    $connUtilizadores->close();
                    $connJogos->close();
                    ?>
                </div>
            </div>
        </div>
    
    <!-- Modal de Logout -->
    <div class="modal-overlay" id="modal">
        <div class="modal-content">
            <h2>Tem a certeza que deseja sair?</h2>
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
