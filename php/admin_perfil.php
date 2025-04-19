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
        <!-- Sidebar -->
        <div class="sidebar">
            <h1>SeguraMente<span class="kids-text">KIDS</span></h1>
            <a href="admin_aprovar_quizz.php" class="sidebar-item">Jogos/Quizzes</a>
            <a href="admin_missoes.php" class="sidebar-item">Missões</a>
            <a href="#" class="sidebar-item" id="logout">Sair</a>
        </div>

        <!-- Conteúdo -->
        <div class="content">
            <!-- Cabeçalho -->
            <div class="header">

                <a href="admin_criar_quizz.php" class="create-quiz">Criar Quiz</a>
                <a href="admin_perfil.php" class="profile">
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
                        </div>
                    </div>
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
                                            <a href="admin_editar_quizz.php?id=<?php echo $quiz['ID_Jogo']; ?>" class="btn-edit">Editar</a>
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
