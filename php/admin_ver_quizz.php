<?php
session_start();
require_once '/home/seguram1/config.php';

// Verificar se o usuário é administrador
if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$connJogos = getDBJogos();
$connJogos->set_charset("utf8mb4");

// Verificar se há um ID de quiz na URL
if (!isset($_GET['id'])) {
    header("Location: admin_aprovar_quizz.php");
    exit();
}

$idJogo = intval($_GET['id']);

// Processar ação de Aprovar ou Recusar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if (in_array($acao, ['Aprovado', 'Recusado'])) {
        // Atualizar o estado do jogo
        $stmt = $connJogos->prepare("UPDATE Jogo SET Estado = ? WHERE ID_Jogo = ?");
        $stmt->bind_param("si", $acao, $idJogo);
        $stmt->execute();
        $stmt->close();
        
        // Se for aprovado, atualizar missão "Criar um quiz"
        if ($acao === 'Aprovado') {
            $connMissoes = getDBJogos();
        
            // Buscar o criador do jogo
            $stmtCriador = $connJogos->prepare("SELECT Criado_por FROM Jogo WHERE ID_Jogo = ?");
            $stmtCriador->bind_param("i", $idJogo);
            $stmtCriador->execute();
            $stmtCriador->bind_result($idCriador);
            $stmtCriador->fetch();
            $stmtCriador->close();
        
            // Atualizar missão: adicionar +1 até ao máximo (Objetivo)
            $stmtMissao = $connMissoes->prepare("
                UPDATE Missao_Semanal 
                SET Progresso = LEAST(Progresso + 1, Objetivo) 
                WHERE ID_Utilizador = ? AND Nome = 'Criar um quiz'
            ");
            $stmtMissao->bind_param("i", $idCriador);
            $stmtMissao->execute();
            $stmtMissao->close();
        }

        header("Location: admin_aprovar_quizz.php");
        exit();
    }
}

// Buscar informações do quiz
$queryQuiz = "SELECT Nome, Descricao FROM Jogo WHERE ID_Jogo = ?";
$stmtQuiz = $connJogos->prepare($queryQuiz);
$stmtQuiz->bind_param("i", $idJogo);
$stmtQuiz->execute();
$resultQuiz = $stmtQuiz->get_result();
$quiz = $resultQuiz->fetch_assoc();

if (!$quiz) {
    header("Location: admin_aprovar_quizz.php");
    exit();
}
$stmtQuiz->close();

// Buscar todas as perguntas do quiz incluindo pontos
$queryPerguntas = "SELECT ID_Pergunta, Texto, Imagem, Opcoes, Resposta_Correta, Pontos FROM Pergunta WHERE ID_Jogo = ?";
$stmtPerguntas = $connJogos->prepare($queryPerguntas);
$stmtPerguntas->bind_param("i", $idJogo);
$stmtPerguntas->execute();
$resultPerguntas = $stmtPerguntas->get_result();

$perguntas = [];
while ($row = $resultPerguntas->fetch_assoc()) {
    $perguntas[] = $row;
}
$stmtPerguntas->close();

$connJogos->close();
?>


<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Quiz - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/admin_ver_quizz.css">
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
            <!-- Header -->
            <div class="header">
                <a href="admin_criar_quizz.php" class="create-quiz">Criar Quiz</a>
                <a href="admin_perfil.php" class="profile">
                    <img src="../imagens/avatar.png" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['nomeUsuario']); ?></span>
                </a>
            </div>
            
            <h1>Aprovar Jogo/Quiz</h1>
            
            <div class="quiz-details">
                <h2><?php echo htmlspecialchars($quiz['Nome']); ?></h2>
                <div class="quiz-description-wrapper">
                    <p><?php echo nl2br(htmlspecialchars($quiz['Descricao'])); ?></p>
                    <a href="admin_editar_quiz.php?id=<?php echo $idJogo; ?>" class="btn-edit-quiz">Editar Quiz</a>
                </div>

                
                <h3>Perguntas:</h3>
                <?php if (!empty($perguntas)): ?>
                    <?php foreach ($perguntas as $index => $pergunta): ?>
                        <div class="quiz-question">
                            <h4>Pergunta <?php echo $index + 1; ?>:</h4>
                            <p><strong>Texto:</strong> <?php echo htmlspecialchars($pergunta['Texto']); ?></p>
                
                            <p><strong>Pontos:</strong> <?php echo intval($pergunta['Pontos']); ?> pts</p>
                
                            <?php if (!empty($pergunta['Imagem'])): ?>
                                <img src="../../uploads/<?php echo basename($pergunta['Imagem']); ?>" width="300px" height="300px" alt="Imagem da Pergunta">
                            <?php endif; ?>
                
                            <div class="quiz-options">
                                <?php
                                $opcoes = explode(", ", $pergunta['Opcoes']);
                                foreach ($opcoes as $opcao):
                                    $correta = ($opcao === $pergunta['Resposta_Correta']) ? 'style="font-weight: bold; color: green;"' : '';
                                ?>
                                    <p <?php echo $correta; ?>><?php echo htmlspecialchars($opcao); ?></p>
                                <?php endforeach; ?>
                            </div>
                
                            <!-- Botão para editar pergunta -->
                            <div class="edit-button">
                                <a href="admin_editar_pergunta.php?idPergunta=<?php echo $pergunta['ID_Pergunta']; ?>&idJogo=<?php echo $idJogo; ?>" class="btn-edit">Editar Pergunta</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Este quiz ainda não possui perguntas.</p>
                <?php endif; ?>

                <div class="action-buttons">
                    <form method="POST">
                        <button type="submit" name="acao" value="Aprovado" class="btn-accept">Aprovar</button>
                        <button type="submit" name="acao" value="Recusado" class="btn-reject">Recusar</button>
                    </form>
                </div>
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
