<?php
session_start();
require_once '/home/seguram1/config.php'; // Carregar configurações seguras

// Verificar se o usuário é administrador
if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Conectar ao banco de dados de jogos
$connJogos = getDBJogos();

// Definir charset UTF-8 corretamente
$connJogos->set_charset("utf8mb4");

// Buscar todas as missões semanais
$query = "SELECT ID_Missao, Nome, Objetivo, Progresso FROM Missao_Semanal GROUP BY Nome";
$result = $connJogos->query($query);

$missoes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $missoes[] = $row;
    }
}

// 📌 Processar remoção de missão
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['idMissao'])) {
    $idMissao = intval($_POST['idMissao']);

    // Remover a missão da tabela
    $stmt = $connJogos->prepare("DELETE FROM Missao_Semanal WHERE ID_Missao = ?");
    $stmt->bind_param("i", $idMissao);
    $stmt->execute();
    $stmt->close();

    // Recarregar a página para refletir a remoção
    header("Location: admin_missoes.php");
    exit();
}

$connJogos->close();
?>


<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missões Semanais - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/admin_missoes.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h1>SeguraMente<span class="kids-text">KIDS</span></h1>
            <a href="admin_aprovar_quizz.php" class="sidebar-item">Jogos/Quizzes</a>
            <a href="admin_missoes.php" class="sidebar-item active">Missões</a>
            <a href="#" class="sidebar-item" id="logout">Sair</a>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="header">

                <a href="admin_criar_missoes.php" class="create-quiz">Adicionar Missão</a>
                <a href="admin_perfil.php" class="profile">
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
    
            // Evento para abrir modal de remoção
            removeButtons.forEach(button => {
                button.addEventListener("click", function () {
                    const idMissao = this.getAttribute("data-id");
                    idMissaoInput.value = idMissao;
                    removeModal.classList.add('show');
                });
            });
            
            // Cancelar remoção
            cancelRemove.addEventListener("click", function () {
                removeModal.classList.remove('show');
            });

            // Após submissão bem-sucedida, esconder modal (com fallback)
            removeForm.addEventListener("submit", function() {
                removeModal.style.display = "none";
            });
        });
    </script>
</body>
</html>
