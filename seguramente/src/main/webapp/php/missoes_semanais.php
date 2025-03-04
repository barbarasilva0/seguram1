<?php
session_start();

// Configuração do banco de dados
$servername = "localhost";
$port = "3306"; // Porta do MySQL
$username = "seguram1_seguram1";
$password_db = "d@V+38y3nWb4FB";
$dbUtilizadores = "seguram1_segura_utilizadores";
$dbJogos = "seguram1_segura_jogos";

// Conectar ao banco de dados seguram1_segura_utilizadores
$connUtilizadores = new mysqli($servername, $username, $password_db, $dbUtilizadores, $port);
if ($connUtilizadores->connect_error) {
    die("Erro na conexão com seguram1_segura_utilizadores: " . $connUtilizadores->connect_error);
}

// Conectar ao banco de dados seguram1_segura_jogos
$connJogos = new mysqli($servername, $username, $password_db, $dbJogos, $port);
if ($connJogos->connect_error) {
    die("Erro na conexão com seguram1_segura_jogos: " . $connJogos->connect_error);
}

// Definir UTF-8 como charset
$connUtilizadores->set_charset("utf8mb4");
$connJogos->set_charset("utf8mb4");


// Verificação de sessão do usuário
$idUsuario = isset($_SESSION['idUsuario']) ? (int)$_SESSION['idUsuario'] : null;
if ($idUsuario === null) {
    header("Location: login.php");
    exit();
}

// Buscar nome do usuário
$nomeUsuario = "Pessoa";
$stmt = $connUtilizadores->prepare("SELECT Nome FROM Utilizador WHERE ID_Utilizador = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($nomeUsuario);
$stmt->fetch();
$stmt->close();

// Contar quizzes completados pelo usuário
$quizzesCompletados = 0;
$stmt = $connJogos->prepare("SELECT COUNT(*) AS total FROM Historico WHERE ID_Utilizador = ? AND Estado = 'Concluído'");
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
    <title>Missões Semanais - SeguraMente</title>
    <link rel="stylesheet" href="../css/missoes_semanais.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
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
            <a href="#" class="sidebar-item" id="logout">
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
                    <span><?php echo htmlspecialchars($nomeUsuario); ?></span>
                </a>
            </div>
            
            <!-- Quiz Section -->
            <div class="quiz-section">
                <div class="quiz-header">
                    <div class="user-info">
                        <img src="../imagens/avatar.png" alt="Avatar do usuário" class="user-avatar">
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($nomeUsuario); ?></span>
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
                
                <!-- Missões Section -->
                <div class="missions-section">
                    <div class="missions-header">Missões Semanais</div>
                    <?php
                    $stmt = $connJogos->prepare("SELECT Nome, Progresso, Objetivo FROM Missao_Semanal WHERE ID_Utilizador = ?");
                    $stmt->bind_param("i", $idUsuario);
                    $stmt->execute();
                    $result = $stmt->get_result();
    
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="mission-card">';
                        echo '<span>' . htmlspecialchars($row['Nome']) . '</span>';
                        echo '<span class="progress">' . htmlspecialchars($row['Progresso']) . ' (' . htmlspecialchars($row['Objetivo']) . ')</span>';
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
