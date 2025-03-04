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
$dbJogos = "seguram1_segura_jogos";

$conn = new mysqli($servername, $username, $password_db, $dbJogos);
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nomeJogo = htmlspecialchars($_POST['nomeJogo']);
    $descricaoJogo = htmlspecialchars($_POST['descricaoJogo']);
    $idCriador = $_SESSION['idUsuario'];

    // Inserir novo jogo no banco
    $stmt = $conn->prepare("INSERT INTO Jogo (Nome, Estado, Descricao, Criado_por) VALUES (?, 'Pendente', ?, ?)");
    $stmt->bind_param("ssi", $nomeJogo, $descricaoJogo, $idCriador);
    $stmt->execute();
    $idJogo = $stmt->insert_id;
    $stmt->close();

    // Inserir perguntas
    foreach ($_POST['perguntas'] as $index => $perguntaTexto) {
        if (!empty($perguntaTexto)) {
            $opcoes = implode(", ", array_map('htmlspecialchars', $_POST['opcoes'][$index]));
            $respostaCorreta = htmlspecialchars($_POST['respostaCorreta'][$index] ?? "");
            $pontos = 10;

            // Upload da imagem
            $imagemPath = "";
            if (!empty($_FILES['imagens']['name'][$index])) {
                $targetDir = "uploads/";
                $fileName = time() . "_" . basename($_FILES['imagens']['name'][$index]);
                $imagemPath = $targetDir . $fileName;
                if (move_uploaded_file($_FILES['imagens']['tmp_name'][$index], $imagemPath)) {
                    $imagemPath = htmlspecialchars($imagemPath);
                }
            }

            // Inserir pergunta no banco
            $stmt = $conn->prepare("INSERT INTO Pergunta (Texto, Opcoes, Resposta_Correta, Pontos, ID_Jogo) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $perguntaTexto, $opcoes, $respostaCorreta, $pontos, $idJogo);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->close();
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Jogo/Quizz - SeguraMente</title>
    <link rel="stylesheet" href="../css/criar_quizz.css">
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
    

    <script>

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
            const questions = document.querySelectorAll('.question');
            questions.forEach((question, index) => {
                question.querySelector('.question-title').textContent = `Pergunta ${index + 1}`;
                question.querySelectorAll('input[type="radio"]').forEach(radio => {
                    radio.name = `respostaCorreta[${index}]`;
                });
            });
        }

        function addQuestion() {
            const container = document.getElementById('question-container');
            const newQuestion = container.firstElementChild.cloneNode(true);
            newQuestion.querySelector('textarea').value = '';
            newQuestion.querySelector('.image-preview').src = '';
            newQuestion.querySelector('.image-preview').style.display = 'none';
            newQuestion.querySelector('input[type="file"]').value = '';
            container.appendChild(newQuestion);
            updateQuestionNumbers();
        }

        function removeSpecificQuestion(button) {
            const questions = document.querySelectorAll('.question');
            if (questions.length > 1) {
                const question = button.closest('.question');
                question.remove();
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
