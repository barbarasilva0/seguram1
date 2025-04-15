<?php
session_start();
require_once '/home/seguram1/config.php'; 
include '../components/user_info.php'; 

// Verificar se o utilizador está autenticado
if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

// Conectar ao banco de dados
$connUtilizadores = getDBUtilizadores();
$connJogos = getDBJogos();

// Obter ID do utilizador
$idUsuario = (int) $_SESSION['idUsuario'];

// Buscar o nome do utilizador
$nomeUsuario = "Utilizador";
$stmt = $connUtilizadores->prepare("SELECT Nome FROM Utilizador WHERE ID_Utilizador = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($nomeUsuario);
$stmt->fetch();
$stmt->close();

// Certificar que o nome está corretamente codificado
$nomeUsuario = htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8');

// Buscar quizzes criados pelo utilizador
$quizzes = [];
$stmt = $connJogos->prepare("SELECT ID_Jogo, Nome, Estado FROM Jogo WHERE Criado_por = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $quizzes[] = $row;
}
$stmt->close();

// Contar quizzes concluídos pelo utilizador
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
    <title>Quizzes Criados - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/quizzes_criados.css">
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

        <!-- Conteúdo -->
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

            <!-- Secção de Quizzes Criados -->
            <div class="quizzes-section">
                <!-- Cabeçalho com Avatar e Informações -->
                <div class="quiz-header">
                    <div class="user-info">
                        <img src="<?php echo !empty($fotoGoogle) ? htmlspecialchars($fotoGoogle, ENT_QUOTES, 'UTF-8') : '../imagens/avatar.png'; ?>" alt="Avatar do usuário" class="user-avatar">
                        <div class="user-details">
                            <span class="user-name"><?php echo $nomeUsuario; ?></span>
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
                    <a href="perfil.php" class="tab-item">Pontuação</a>
                    <a href="ranking_geral.php" class="tab-item">Ranking Geral</a>
                    <a href="#" class="tab-item active">Jogos/Quizzes Criados</a>
                </div>

                <!-- Tabela de Quizzes -->
                <table class="quizzes-table">
                    <thead>
                        <tr>
                            <th>Posição</th>
                            <th>Jogo/Quiz</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($quizzes)): ?>
                            <?php foreach ($quizzes as $index => $quiz): ?>
                                <tr>
                                    <td><?php echo ($index + 1); ?>º</td>
                                    <td><?php echo htmlspecialchars($quiz['Nome']); ?></td>
                                    <td class="<?php 
                                        if ($quiz['Estado'] == 'Aprovado') echo 'status-approved';
                                        elseif ($quiz['Estado'] == 'Recusado') echo 'status-rejected';
                                        else echo 'status-pending';
                                    ?>">
                                        <?php echo ucfirst($quiz['Estado']); ?>
                                    </td>
                                    <td class="center-actions">
                                        <?php if ($quiz['Estado'] === 'Aprovado'): ?>
                                            <a href="editar_quizz.php?id=<?php echo $quiz['ID_Jogo']; ?>" class="btn-edit">Editar</a>
                                        <?php else: ?>
                                            <span style="color: #aaa;">---</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">Nenhum quiz criado ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
