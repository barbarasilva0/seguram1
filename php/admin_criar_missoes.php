<?php
session_start();
require_once '/home/seguram1/config.php';

// Verificar se o usuário é administrador
if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Conectar ao banco de dados
$connUtilizadores = getDBUtilizadores();
$connJogos = getDBJogos();

$connUtilizadores->set_charset("utf8mb4");
$connJogos->set_charset("utf8mb4");

$success = false;

// Processar criação de missão
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nomeMissao = trim($_POST['nomeMissao']);
    $descricaoMissao = trim($_POST['descricaoMissao']);
    $objetivoMissao = intval($_POST['objetivoMissao']);

    if (!empty($nomeMissao) && !empty($descricaoMissao) && $objetivoMissao > 0) {
        // Buscar todos os usuários e criar a missão para cada um deles
        $stmt = $connUtilizadores->prepare("SELECT ID_Utilizador FROM Utilizador WHERE Tipo_de_Utilizador = 'Jogador'");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $idUsuario = $row['ID_Utilizador'];

            // Criar a missão individual para cada utilizador
            $stmtInsert = $connJogos->prepare("INSERT INTO Missao_Semanal (Nome, Descricao, Objetivo, Progresso, ID_Utilizador) VALUES (?, ?, ?, 0, ?)");
            $stmtInsert->bind_param("sssi", $nomeMissao, $descricaoMissao, $objetivoMissao, $idUsuario);
            $stmtInsert->execute();
            $stmtInsert->close();
        }
        $stmt->close();
        $success = true;
    }
}

$connUtilizadores->close();
$connJogos->close();
?>


<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Missão - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/admin_criar_missoes.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h1>SeguraMente<span class="kids-text">KIDS</span></h1>
            <a href="admin_aprovar_quizz.php" class="sidebar-item">Jogos/Quizzes</a>
            <a href="admin_missoes.php" class="sidebar-item active">Missões</a>
            <a href="#" class="sidebar-item" id="logout">Sair</a>
        </div>

        <div class="content">
            <div class="header">

                <a href="admin_criar_quizz.php" class="create-quiz">Criar Quiz</a>
                <a href="admin_perfil.php" class="profile">
                    <img src="../imagens/avatar.png" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['nomeUsuario']); ?></span>
                </a>
            </div>

            <h1>Criar Nova Missão</h1>
            
            <div class="alerta-admin">
                <h3>Atenção:</h3>
                <p>Para que as missões sejam interpretadas corretamente e o progresso dos jogadores seja atualizado automaticamente, utilize os seguintes formatos no <strong>nome da missão</strong>:</p>
                <ul>
                    <li><strong>Jogue</strong> — para contar jogos concluídos (ex: <em>Jogue 3 quizzes esta semana</em>)</li>
                    <li><strong>Multiplayer</strong> — para reconhecer participação em jogos multiplayer(ex: <em>Participe de um jogo multiplayer</em>)</li>
                    <li><strong>Criar</strong> — para reconhecer a criação de quizzes (ex: <em>Criar um quiz</em>)</li>
                </ul>
                <p>Qualquer outro nome será tratado como missão personalizada e <strong>não será atualizada automaticamente</strong>.</p>
            </div>

            <?php if ($success) : ?>
                <div class="success-message">
                    Missão criada com sucesso!
                </div>
                <script>
                    setTimeout(() => {
                        window.location.href = 'admin_missoes.php';
                    }, 1500);
                </script>
            <?php endif; ?>

            <form method="POST">
                <div class="form-card">
                    <div class="form-wrapper">
                        <div class="form-group">
                            <label for="nomeMissao">Nome da Missão:</label>
                            <input type="text" id="nomeMissao" name="nomeMissao" maxlength="50" required>
                        </div>
            
                        <div class="form-group">
                            <label for="descricaoMissao">Descrição:</label>
                            <textarea id="descricaoMissao" name="descricaoMissao" rows="3" maxlength="150" required></textarea>
                        </div>
            
                        <div class="form-group">
                            <label for="objetivoMissao">Objetivo:</label>
                            <input type="number" id="objetivoMissao" name="objetivoMissao" min="1" step="1" required>
                        </div>
                    </div>
                </div>
            
                <div class="action-buttons">
                    <button type="submit" class="btn-add">Adicionar</button>
                    <button type="button" class="btn-remove" id="cancelButton">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Cancelamento -->
    <div class="modal-overlay" id="cancel-modal">
        <div class="modal-content">
            <h2>Tem certeza que deseja cancelar?</h2>
            <div class="modal-buttons">
                <button class="btn-yes" id="confirmCancel">Sim</button>
                <button class="btn-no" id="cancelNo">Não</button>
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
