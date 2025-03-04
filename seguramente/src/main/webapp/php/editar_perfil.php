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
$dbUtilizadores = "seguram1_segura_utilizadores";

// Conectar ao banco de dados
$conn = new mysqli($servername, $username, $password_db, $dbUtilizadores);
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados.");
}

// Definir charset UTF-8
$conn->set_charset("utf8mb4");

// Dados do usuário autenticado
$idUsuario = (int) $_SESSION['idUsuario'];

// Buscar informações do usuário
$stmt = $conn->prepare("SELECT Nome, Email FROM Utilizador WHERE ID_Utilizador = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($nomeUsuario, $emailUsuario);
$stmt->fetch();
$stmt->close();

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $novoNome = htmlspecialchars($_POST['nome']);
    $novoEmail = htmlspecialchars($_POST['email']);

    // Atualizar informações do usuário
    $stmt = $conn->prepare("UPDATE Utilizador SET Nome = ?, Email = ? WHERE ID_Utilizador = ?");
    $stmt->bind_param("ssi", $novoNome, $novoEmail, $idUsuario);

    if ($stmt->execute()) {
        $_SESSION['nomeUsuario'] = $novoNome; // Atualiza a sessão
        $mensagem = "Perfil atualizado com sucesso!";
    } else {
        $mensagem = "Erro ao atualizar o perfil.";
    }
    $stmt->close();
}

// Fechar conexão
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - SeguraMente</title>
    <link rel="stylesheet" href="../css/editar_perfil.css">
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

            <!-- Edit Profile Section -->
            <div class="edit-profile-section">
                <img src="../imagens/avatar.png" alt="Avatar" class="user-avatar">

                <?php if (isset($mensagem)): ?>
                    <div class="alert"><?php echo $mensagem; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="full-name">Nome</label>
                        <input type="text" id="full-name" name="nome" value="<?php echo htmlspecialchars($nomeUsuario); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($emailUsuario); ?>" required>
                    </div>

                    <div class="buttons">
                        <button type="submit" class="btn-primary">Atualizar Perfil</button>
                        <button type="button" class="btn-secondary" onclick="history.back()">Cancelar</button>
                    </div>
                </form>
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
