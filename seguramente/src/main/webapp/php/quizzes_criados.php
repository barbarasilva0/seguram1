<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

// Configuração do banco de dados
$servername = "localhost";
$username = "seguram1_seguram1"; 
$password_db = "d@V+38y3nWb4FB"; 
$dbJogos = "seguram1_segura_jogos";

// Conectar ao banco de dados
$conn = new mysqli($servername, $username, $password_db, $dbJogos);
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados.");
}

// Definir charset UTF-8
$conn->set_charset("utf8mb4");

// ID do usuário autenticado
$idUsuario = $_SESSION['idUsuario'];

// Buscar quizzes criados pelo usuário
$query = "SELECT ID_Jogo, Nome, Estado FROM Jogo WHERE Criado_por = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();

$quizzes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $quizzes[] = $row;
    }
}

// Fechar conexão
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes Criados - SeguraMente</title>
    <link rel="stylesheet" href="../css/quizzes_criados.css">

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
                    <span><?php echo htmlspecialchars($_SESSION['nomeUsuario']); ?></span>
                </a>
            </div>

            <!-- Quizzes Criados Section -->
            <div class="quizzes-section">
                <!-- Header com Avatar e Informações -->
                <div class="quiz-header">
                    <div class="user-info">
                        <img src="../imagens/avatar.png" alt="Avatar do usuário" class="user-avatar">
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nomeUsuario']); ?></span>
                            <div class="user-score-container">
                                <img src="../imagens/flag_icon.png" alt="Flag Icon" class="flag-icon">
                                <div class="user-score">
                                    <span class="score-value">Seus Quizzes</span>
                                    <span class="score-text">Criados</span>
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
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">Nenhum quiz criado ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
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
