<?php
session_start();
require_once '/home/seguram1/config.php';
include '../components/user_info.php';

$connUtilizadores = getDBUtilizadores();
$connJogos = getDBJogos();
$connUtilizadores->set_charset("utf8mb4");
$connJogos->set_charset("utf8mb4");

if (!isset($_GET['id'])) {
    header("Location: quizzes_criados.php");
    exit();
}

$idJogo = intval($_GET['id']);

$queryQuiz = "SELECT Nome, Descricao FROM Jogo WHERE ID_Jogo = ?";
$stmtQuiz = $connJogos->prepare($queryQuiz);
$stmtQuiz->bind_param("i", $idJogo);
$stmtQuiz->execute();
$resultQuiz = $stmtQuiz->get_result();
$quiz = $resultQuiz->fetch_assoc();
$stmtQuiz->close();

if (!$quiz) {
    header("Location: quizzes_criados.php");
    exit();
}

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
    <title>Editar Quiz - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/editar_quizz.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
    
</head>
<body>
    <div class="container">
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
            <a href="logout.php" class="sidebar-item" id="logout">
                <img src="../imagens/logout_icon.png" alt="Sair" style="width: 20px; height: 20px;">
                Sair
            </a>
        </div>
        
        <div class="content">
            <div class="header">
                <?php include '../components/autocomplete.php'; ?>
                
                <a href="criar_quizz.php" class="create-quiz">Criar Quizz</a>
                <a href="perfil.php" class="profile">
                    <img src="<?php echo !empty($fotoGoogle) ? htmlspecialchars($fotoGoogle, ENT_QUOTES, 'UTF-8') : '../imagens/avatar.png'; ?>" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['nomeUsuario'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            </div>

      

            <form method="POST" action="processar_edicao.php?id=<?php echo $idJogo; ?>" enctype="multipart/form-data">
                
                <div class="quiz-details">
                    <h1 class="quiz-title">Editar Jogo/Quiz</h1>
                    <label for="nome_quizz">Título do Quiz:</label>
                    <input type="text" id="nome_quizz" name="nome_quizz" value="<?php echo htmlspecialchars($quiz['Nome']); ?>" required>

                    <label for="descricao_quizz">Descrição:</label>
                    <textarea id="descricao_quizz" name="descricao_quizz" rows="4" required><?php echo htmlspecialchars($quiz['Descricao']); ?></textarea>
                    
                    <label for="total_pontos">Pontos Totais do Quiz:</label>
                    <input type="number" id="total_pontos" name="total_pontos" value="0" min="1" required>
                    <small>Este valor será dividido igualmente por todas as perguntas.</small>

                    <h3>Perguntas:</h3>
                    <div id="question-container">
                        <?php foreach ($perguntas as $index => $pergunta): ?>
                            <?php
                                $opcoesArray = explode(', ', $pergunta['Opcoes']);
                                $respostaCorreta = $pergunta['Resposta_Correta'];
                            ?>
                            <div class="question-block">
                                <div class="question">
                                    <div class="question-title">Pergunta <?php echo $index + 1; ?></div>
                                    <div class="question-header">
                                        <textarea name="perguntas[<?php echo $pergunta['ID_Pergunta']; ?>][texto]" required><?php echo htmlspecialchars($pergunta['Texto']); ?></textarea>
                                        <label class="upload-container">
                                            <img class="image-preview" src="<?php echo !empty($pergunta['Imagem']) ? '../../uploads/' . basename($pergunta['Imagem']) : ''; ?>" alt="Imagem da pergunta">
                                            <input type="file" name="perguntas[<?php echo $pergunta['ID_Pergunta']; ?>][imagem]" accept="image/*" onchange="previewImage(event, this)">
                                        </label>
                                    </div>
                                </div>
                                <div class="options">
                                    <?php for ($i = 0; $i < 4; $i++): ?>
                                        <?php $letra = chr(65 + $i); $opcaoTexto = $opcoesArray[$i] ?? ''; ?>
                                        <div class="option">
                                            <input type="text" name="perguntas[<?php echo $pergunta['ID_Pergunta']; ?>][opcoes][<?php echo $i; ?>]" value="<?php echo htmlspecialchars($opcaoTexto); ?>" placeholder="Opção <?php echo $letra; ?>" required>
                                            <input type="radio" name="perguntas[<?php echo $pergunta['ID_Pergunta']; ?>][correta]" value="<?php echo $letra; ?>" <?php echo ($respostaCorreta === $opcaoTexto) ? 'checked' : ''; ?> required>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <div class="pontos">
                                    <label>Pontos:</label>
                                    <input type="number" name="perguntas[<?php echo $pergunta['ID_Pergunta']; ?>][pontos]" value="<?php echo intval($pergunta['Pontos']); ?>" min="0" required>
                                </div>
                                <button type="button" onclick="removeQuestion(this)" class="btn-remove">Remover Pergunta</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" onclick="addQuestion()" class="btn-add">Adicionar Nova Pergunta</button>
                    <button type="submit" name="editar_quizz" class="btn-accept">Submeter Nova Versão</button>

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


        function previewImage(event, input) {
            const reader = new FileReader();
            reader.onload = () => input.previousElementSibling.src = reader.result;
            reader.readAsDataURL(event.target.files[0]);
        }

        function removeQuestion(button) {
            const block = button.closest('.question-block');
            block.remove();
        }

        function addQuestion() {
            const container = document.getElementById('question-container');
            const index = container.children.length + 1000; // ID fictício para não colidir
            const letra = ['A', 'B', 'C', 'D'];
            const div = document.createElement('div');
            div.className = 'question-block';
            div.innerHTML = `
                <div class="question">
                    <div class="question-title">Nova Pergunta</div>
                    <div class="question-header">
                        <textarea name="novas[${index}][texto]" placeholder="Digite sua pergunta" required></textarea>
                        <label class="upload-container">
                            <img class="image-preview" alt="Prévia da imagem">
                            <input type="file" name="novas[${index}][imagem]" accept="image/*" onchange="previewImage(event, this)">
                        </label>
                    </div>
                </div>
                <div class="options">
                    ${letra.map((l, i) => `
                        <div class="option">
                            <input type="text" name="novas[${index}][opcoes][${i}]" placeholder="Opção ${l}" required>
                            <input type="radio" name="novas[${index}][correta]" value="${l}" required>
                        </div>`).join('')}
                </div>
                <div class="pontos">
                    <label>Pontos:</label>
                    <input type="number" name="novas[${index}][pontos]" value="10" min="0" required>
                </div>
                <button type="button" onclick="removeQuestion(this)" class="btn-remove">Remover Pergunta</button>
            `;
            container.appendChild(div);
        }

        document.getElementById('total_pontos').addEventListener('input', distribuirPontosEquitativos);

        function distribuirPontosEquitativos() {
            const total = parseInt(document.getElementById('total_pontos').value);
            const perguntas = document.querySelectorAll('#question-container .question-block');
            const totalPerguntas = perguntas.length;
        
            if (totalPerguntas > 0 && total > 0) {
                const pontosPorPergunta = Math.floor(total / totalPerguntas);
                perguntas.forEach(pergunta => {
                    const inputPontos = pergunta.querySelector('input[type="number"]');
                    if (inputPontos) {
                        inputPontos.value = pontosPorPergunta;
                    }
                });
            }
        }
        
        // Atualizar o campo total ao carregar com base na soma dos pontos existentes
        window.addEventListener('DOMContentLoaded', () => {
            const pontosInputs = document.querySelectorAll('#question-container input[type="number"]');
            let total = 0;
            pontosInputs.forEach(input => total += parseInt(input.value) || 0);
            document.getElementById('total_pontos').value = total;
        });
    </script>
</body>
</html>
