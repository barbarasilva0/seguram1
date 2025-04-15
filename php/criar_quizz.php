<?php
session_start();
require_once '/home/seguram1/config.php';
include '../components/user_info.php'; 

if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nomeJogo = htmlspecialchars($_POST['nomeJogo']);
    $descricaoJogo = htmlspecialchars($_POST['descricaoJogo']);
    $pontosTotais = intval($_POST['pontosTotais']);
    $idCriador = $_SESSION['idUsuario'];

    $stmt = $conn->prepare("INSERT INTO Jogo (Nome, Estado, Descricao, Criado_por) VALUES (?, 'Pendente', ?, ?)");
    $stmt->bind_param("ssi", $nomeJogo, $descricaoJogo, $idCriador);
    $stmt->execute();
    $idJogo = $stmt->insert_id;
    $stmt->close();

    $uploadDir = "../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $totalPerguntas = count($_POST['perguntas']);
    $pontosPorPergunta = $totalPerguntas > 0 ? floor($pontosTotais / $totalPerguntas) : 0;

    foreach ($_POST['perguntas'] as $index => $perguntaTexto) {
        if (!empty($perguntaTexto)) {
            $opcoes = implode(", ", array_map('htmlspecialchars', $_POST['opcoes'][$index]));
            $respostaCorreta = htmlspecialchars($_POST['respostaCorreta'][$index] ?? "");

            $imagemPath = null; 
            if (!empty($_FILES['imagens']['name'][$index])) {
                $fileName = time() . "_" . basename($_FILES['imagens']['name'][$index]);
                $imagemPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['imagens']['tmp_name'][$index], $imagemPath)) {
                    $imagemPath = htmlspecialchars($imagemPath);
                } else {
                    $imagemPath = null;
                }
            }

            $stmt = $conn->prepare("INSERT INTO Pergunta (Texto, Opcoes, Resposta_Correta, Pontos, ID_Jogo, Imagem) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiss", $perguntaTexto, $opcoes, $respostaCorreta, $pontosPorPergunta, $idJogo, $imagemPath);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->close();
    header("Location: aprovar_jogo.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Jogo/Quizz - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/criar_quizz.css">
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
            <a href="#" class="sidebar-item" id="logout">
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
                    <span><?php echo htmlspecialchars($_SESSION['nomeUsuario']); ?></span>
                </a>
            </div>
            <div class="quiz-section">
                <h1 class="quiz-title">Criar o Jogo/Quizz</h1>
                <form method="POST" enctype="multipart/form-data">
                    <div class="quiz-info">
                        <label>Nome do Jogo/Quizz:</label>
                        <input type="text" name="nomeJogo" class="quiz-name-input" required>
                        <label>Descrição do Jogo/Quizz:</label>
                        <textarea name="descricaoJogo" class="quiz-description-input" placeholder="Escreva uma breve descrição do jogo..." required></textarea>
                        <label>Pontuação Total do Jogo:</label>
                        <input type="number" name="pontosTotais" class="quiz-points-input" min="1" required>
                    </div>

                    <div id="question-container">
                        <div class="question-block">
                            <div class="question">
                                <button type="button" class="remove-question-btn" onclick="removeSpecificQuestion(this)">Remover</button>
                                <div class="question-title">Pergunta 1</div>
                                <div class="question-header">
                                    <textarea name="perguntas[]" placeholder="Digite sua pergunta" required></textarea>
                                    <label class="upload-container">
                                        <img class="image-preview" alt="Prévia da imagem">
                                        Clique para enviar
                                        <input type="file" name="imagens[]" accept="image/*" onchange="previewImage(event, this)">
                                    </label>
                                </div>
                            </div>
                            <div class="options">
                                <div class="option">
                                    <input type="text" name="opcoes[0][]" placeholder="Opção A" required>
                                    <input type="radio" name="respostaCorreta[0]" value="A" required>
                                </div>
                                <div class="option">
                                    <input type="text" name="opcoes[0][]" placeholder="Opção B" required>
                                    <input type="radio" name="respostaCorreta[0]" value="B">
                                </div>
                                <div class="option">
                                    <input type="text" name="opcoes[0][]" placeholder="Opção C">
                                    <input type="radio" name="respostaCorreta[0]" value="C">
                                </div>
                                <div class="option">
                                    <input type="text" name="opcoes[0][]" placeholder="Opção D">
                                    <input type="radio" name="respostaCorreta[0]" value="D">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="alert-box" class="alert"></div>
                    <div class="buttons">
                        <button type="button" class="btn" onclick="addQuestion()">Adicionar Pergunta</button>
                    </div>
                    <button type="submit" class="btn">Finalizar Jogo/Quizz</button>
                </form>
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


        function previewImage(event, inputElement) {
            const reader = new FileReader();
            reader.onload = function () {
                const preview = inputElement.previousElementSibling;
                preview.src = reader.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function updateQuestionNumbers() {
            const questionBlocks = document.querySelectorAll('.question-block');
            questionBlocks.forEach((block, index) => {
                block.querySelector('.question-title').textContent = `Pergunta ${index + 1}`;
                block.querySelectorAll('.options input[type="radio"]').forEach(radio => {
                    radio.name = `respostaCorreta[${index}]`;
                });
                block.querySelectorAll('.options input[type="text"]').forEach(input => {
                    input.name = `opcoes[${index}][]`;
                });
            });
        }

        function addQuestion() {
            const container = document.getElementById('question-container');
            const template = document.querySelector('.question-block');
            const clone = template.cloneNode(true);
            clone.querySelector('textarea').value = '';
            clone.querySelector('.image-preview').src = '';
            clone.querySelector('.image-preview').style.display = 'none';
            clone.querySelector('input[type="file"]').value = '';
            clone.querySelectorAll('.options input[type="text"]').forEach(input => input.value = '');
            clone.querySelectorAll('.options input[type="radio"]').forEach(radio => radio.checked = false);
            container.appendChild(clone);
            updateQuestionNumbers();
        }

        function removeSpecificQuestion(button) {
            const questionBlocks = document.querySelectorAll('.question-block');
            if (questionBlocks.length > 1) {
                button.closest('.question-block').remove();
                updateQuestionNumbers();
            } else {
                showAlert("O quiz deve ter pelo menos uma pergunta!");
            }
        }

        function showAlert(message) {
            let alertBox = document.getElementById("alert-box");
            if (!alertBox) {
                alertBox = document.createElement("div");
                alertBox.id = "alert-box";
                alertBox.className = "alert";
                document.body.appendChild(alertBox);
            }
            alertBox.textContent = message;
            alertBox.style.display = "block";
            setTimeout(() => {
                alertBox.style.display = "none";
            }, 3000);
        }
    </script>
</body>
</html>
