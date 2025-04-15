<?php
session_start();
require_once '/home/seguram1/config.php'; 
include '../components/user_info.php'; 
require_once __DIR__ . '/../utils/conquistas.php';

// Verificar se o utilizador está autenticado
if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

// Conectar ao banco de dados usando as funções do config.php
$connUtilizadores = getDBUtilizadores();
$connJogos = getDBJogos();

// Obtém o ID do utilizador autenticado
$idUsuario = (int) $_SESSION['idUsuario'];

// Buscar informações do utilizador garantindo correta codificação UTF-8
$nomeUsuario = "Utilizador";
$stmt = $connUtilizadores->prepare("SELECT Nome FROM Utilizador WHERE ID_Utilizador = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($nomeUsuario);
$stmt->fetch();
$stmt->close();

// Certificar que o nome está corretamente formatado para evitar caracteres estranhos
$nomeUsuario = htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8');

// Buscar pontuação total do utilizador
$pontuacaoTotal = 0;
$stmt = $connUtilizadores->prepare("SELECT Pontuacao_Total FROM Perfil WHERE ID_Utilizador = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($pontuacaoTotal);
$stmt->fetch();
$stmt->close();

// Atualizar conquistas dinamicamente com base na pontuação
atualizarConquista($idUsuario);

// Buscar conquistas atualizadas
$conquistaTexto = '';
$stmt = $connUtilizadores->prepare("SELECT Conquistas FROM Perfil WHERE ID_Utilizador = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($conquistaTexto);
$stmt->fetch();
$stmt->close();

// Buscar pontuação total do utilizador
$pontuacaoTotal = 0;
$stmt = $connUtilizadores->prepare("SELECT Pontuacao_Total FROM Perfil WHERE ID_Utilizador = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($pontuacaoTotal);
$stmt->fetch();
$stmt->close();

// Buscar número de quizzes concluídos
$quizzesConcluidos = 0;
$stmt = $connJogos->prepare("SELECT COUNT(*) FROM Historico WHERE ID_Utilizador = ? AND Estado = 'Concluído'");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($quizzesConcluidos);
$stmt->fetch();
$stmt->close();

// Fechar conexões ao banco de dados
$connUtilizadores->close();
$connJogos->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/perfil.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
    
</head>
<body>
    <div class="container">
        <!-- Barra Lateral -->
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

            <!-- Secção de Perfil -->
            <div class="quiz-section">
                <div class="quiz-header">
                    <div class="user-info">
                        <img src="<?php echo !empty($fotoGoogle) ? htmlspecialchars($fotoGoogle, ENT_QUOTES, 'UTF-8') : '../imagens/avatar.png'; ?>" alt="Avatar do usuário" class="user-avatar">
                        <div class="user-details">
                            <span class="user-name"><?php echo $nomeUsuario; ?></span>
                            <?php if (!empty($conquistaTexto)): ?>
                                <div class="user-achievement"><?php echo htmlspecialchars($conquistaTexto); ?></div>
                            <?php endif; ?>

                            <div class="user-score-container">
                                <img src="../imagens/flag_icon.png" alt="Flag Icon" class="flag-icon">
                                <div class="user-score">
                                    <span class="score-value"><?php echo $quizzesConcluidos; ?></span>
                                    <span class="score-text">Quiz Concluídos</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="editar_perfil.php" class="edit-profile-btn">Editar Perfil</a>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <a href="#" class="tab-item active">Pontuação</a>
                    <a href="ranking_geral.php" class="tab-item">Ranking Geral</a>
                    <a href="quizzes_criados.php" class="tab-item">Jogos/Quizzes Criados</a>
                </div>

                <!-- Pontuação Total -->
                <div class="score-total">
                    <h2>Pontuação</h2>
                    <p><?php echo number_format($pontuacaoTotal, 0, ',', '.'); ?> pts</p>
                </div>
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
