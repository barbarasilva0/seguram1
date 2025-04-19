<?php
session_start();
require_once '/home/seguram1/config.php'; 

// Verificar se o usuário é administrador
if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Conectar ao banco de dados de jogos via config.php
$conn = getDBJogos();

// Definir charset UTF-8
$conn->set_charset("utf8mb4");

// Buscar quizzes pendentes para aprovação
$query = "SELECT ID_Jogo, Nome FROM Jogo WHERE Estado = 'Pendente'";
$result = $conn->query($query);

$quizzes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $quizzes[] = $row;
    }
}

// Fechar conexão
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovar Jogos/Quizzes - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/admin_aprovar_quizz.css">
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

        <!-- Content -->
        <div class="content">
            <div class="header">

                <a href="admin_criar_quizz.php" class="create-quiz">Criar Quiz</a>
                <a href="admin_perfil.php" class="profile">
                    <img src="../imagens/avatar.png" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['nomeUsuario']); ?></span>
                </a>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <a href="#" class="tab-item active">Aprovar Jogos/Quizzes</a>
                <a href="admin_lista_jogos.php" class="tab-item">Jogos Disponíveis</a>
            </div>

            <h1>Aprovar Jogos/Quizzes</h1>

            <div class="quiz-list">
                <?php if (!empty($quizzes)): ?>
                    <?php foreach ($quizzes as $quiz): ?>
                        <a href="admin_ver_quizz.php?id=<?php echo $quiz['ID_Jogo']; ?>" class="quiz-item">
                            <span><?php echo htmlspecialchars($quiz['Nome']); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhum quiz pendente para aprovação.</p>
                <?php endif; ?>
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
