<?php
session_start();

// Verificar se o usuário é administrador
if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
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

$conn->set_charset("utf8mb4");

// Buscar todas as missões
$query = "SELECT ID_Missao, Nome, Objetivo, Progresso FROM Missao_Semanal GROUP BY Nome";
$result = $conn->query($query);

$missoes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $missoes[] = $row;
    }
}

// Processar remoção de missão
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['idMissao'])) {
    $idMissao = intval($_POST['idMissao']);

    // Remover a missão da tabela
    $stmt = $conn->prepare("DELETE FROM Missao_Semanal WHERE ID_Missao = ?");
    $stmt->bind_param("i", $idMissao);
    $stmt->execute();
    $stmt->close();

    // Recarregar a página para refletir a remoção
    header("Location: admin_missoes.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missões Semanais - SeguraMente</title>
    <link rel="stylesheet" href="../css/admin_missoes.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h1>SeguraMente</h1>
            <a href="admin_aprovar_quizz.php" class="sidebar-item">Jogos/Quizzes</a>
            <a href="admin_missoes.php" class="sidebar-item active">Missões</a>
            <a href="#" class="sidebar-item" id="logout">Sair</a>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="header">
                <div class="search-container">
                    <input type="text" placeholder="Pesquisar Missão">
                </div>
                <a href="admin_criar_missoes.php" class="create-quiz">Adicionar Missão</a>
                <a href="perfil.php" class="profile">
                    <img src="../imagens/avatar.png" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['nomeUsuario']); ?></span>
                </a>
            </div>

            <h1>Missões Semanais</h1>

            <div class="missions-list">
                <?php if (!empty($missoes)) : ?>
                    <?php foreach ($missoes as $missao) : ?>
                        <div class="mission-item">
                            <span><?php echo htmlspecialchars($missao['Nome']); ?></span>
                            <span><?php echo $missao['Progresso']; ?> / <?php echo htmlspecialchars($missao['Objetivo']); ?></span>
                            <button class="btn-remove" data-id="<?php echo $missao['ID_Missao']; ?>">Remover</button>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>Nenhuma missão disponível.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Remoção -->
    <div class="modal-overlay" id="remove-modal">
        <div class="modal-content">
            <p>Tem certeza que deseja remover esta missão?</p>
            <form method="POST" id="remove-form">
                <input type="hidden" name="idMissao" id="idMissao">
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel">Cancelar</button>
                    <button type="submit" class="btn-confirm">Remover</button>
                </div>
            </form>
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
        document.addEventListener("DOMContentLoaded", function () {
            const logoutLink = document.getElementById('logout');
            const modal = document.getElementById('modal');
            const confirmYes = document.getElementById('confirmYes');
            const confirmNo = document.getElementById('confirmNo');
            const removeButtons = document.querySelectorAll(".btn-remove");
            const removeModal = document.getElementById("remove-modal");
            const removeForm = document.getElementById("remove-form");
            const idMissaoInput = document.getElementById("idMissao");
            const cancelRemove = document.querySelector(".btn-cancel");

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

            // Evento para exibir o modal de remoção
            removeButtons.forEach(button => {
                button.addEventListener("click", function () {
                    const idMissao = this.getAttribute("data-id");
                    idMissaoInput.value = idMissao;
                    removeModal.style.display = "flex";
                });
            });

            // Evento para cancelar a remoção
            cancelRemove.addEventListener("click", function () {
                removeModal.style.display = "none";
            });
        });
    </script>
</body>
</html>
