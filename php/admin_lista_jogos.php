<?php
session_start();
require_once '/home/seguram1/config.php';

// Verificar se é admin
if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$connJogos = getDBJogos();
$connJogos->set_charset("utf8mb4");
$nomeUsuario = $_SESSION['nomeUsuario'] ?? "Admin";
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogos Aprovados - Admin | SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/admin_lista_jogos.css">
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

        <div class="tabs">
            <a href="admin_aprovar_quizz.php" class="tab-item">Aprovar Jogos/Quizzes</a>
            <a href="#" class="tab-item active">Jogos Aprovados</a>
        </div>
        
        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'removido'): ?>
            <div class="alert success">Quiz removido com sucesso.</div>
        <?php elseif (isset($_GET['erro'])): ?>
            <div class="alert error">
                <?php
                switch ($_GET['erro']) {
                    case 'invalid_id':
                        echo "ID do quiz inválido.";
                        break;
                    case 'not_found':
                        echo "Quiz não encontrado.";
                        break;
                    case 'delete_fail':
                        echo "Erro ao remover o quiz. Tenta novamente.";
                        break;
                    default:
                        echo "Ocorreu um erro.";
                }
                ?>
            </div>
        <?php endif; ?>

    
            <div class="quiz-section">
                <h1 class="quiz-title">Quizzes Aprovados</h1>
                <div class="quiz-grid">
                    <?php
                    $query = "SELECT ID_Jogo, Nome, Descricao FROM Jogo WHERE Estado = 'Aprovado'";
                    $stmt = $connJogos->prepare($query);
                    $stmt->execute();
                    $result = $stmt->get_result();
            
                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                        <div class="quiz-card-horizontal">
                          <img src="../imagens/quiz-image.webp" alt="Imagem do Quiz">
                          <div class="quiz-info-horizontal">
                            <h3 class="quiz-title"><?= htmlspecialchars($row['Nome']) ?></h3>
                            <p class="quiz-description"><?= htmlspecialchars($row['Descricao']) ?></p>
                            <div class="quiz-actions">
                                <form method="POST" action="remover_quizz.php" class="form-remover-quizz">
                                    <input type="hidden" name="id_quizz" value="<?= intval($row['ID_Jogo']) ?>">
                                    <button type="button" class="quiz-button red btn-remover" data-id="<?= intval($row['ID_Jogo']) ?>">Remover</button>
                                </form>
                            </div>
                          </div>
                        </div>
                    <?php endwhile;
                    else:
                        echo "<p style='margin-top:20px;'>Não há quizzes aprovados neste momento.</p>";
                    endif;
            
                    $stmt->close();
                    $connJogos->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="custom-modal-overlay" id="modal-remover">
    <div class="custom-modal">
        <h2>Tem a certeza que deseja remover este quiz?</h2>
        <form method="POST" action="remover_quizz.php">
            <input type="hidden" name="id_quizz" id="modal-id-quiz">
            <div class="custom-modal-buttons">
                <button type="submit" class="btn-yes">Sim</button>
                <button type="button" class="btn-no" id="cancelar-modal">Cancelar</button>
            </div>
        </form>
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
    
    
    document.querySelectorAll('.btn-remover').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            document.getElementById('modal-id-quiz').value = id;
            document.getElementById('modal-remover').classList.add('show');
        });
    });
    
    document.getElementById('cancelar-modal').addEventListener('click', () => {
        document.getElementById('modal-remover').classList.remove('show');
    });
</script>
</body>
</html>
