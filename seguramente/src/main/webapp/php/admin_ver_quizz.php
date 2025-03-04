<?php
session_start();

// Verificar se o usuário é administrador
if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Verificar se há um ID de quiz na URL
if (!isset($_GET['id'])) {
    header("Location: admin_aprovar_quizz.php");
    exit();
}

$idJogo = intval($_GET['id']);

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

// Buscar informações do quiz
$query = "SELECT Nome, Descricao FROM Jogo WHERE ID_Jogo = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idJogo);
$stmt->execute();
$result = $stmt->get_result();

$quiz = $result->fetch_assoc();
if (!$quiz) {
    header("Location: admin_aprovar_quizz.php");
    exit();
}

// Processar aprovação ou recusa
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao'])) {
    $acao = $_POST['acao']; // 'Aprovado' ou 'Recusado'

    $stmt = $conn->prepare("UPDATE Jogo SET Estado = ? WHERE ID_Jogo = ?");
    $stmt->bind_param("si", $acao, $idJogo);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_aprovar_quizz.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Quiz - SeguraMente</title>
    <link rel="stylesheet" href="../css/admin_ver_quizz.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h1>SeguraMente</h1>
            <a href="admin_aprovar_quizz.php" class="sidebar-item">Jogos/Quizzes</a>
            <a href="admin_missoes.php" class="sidebar-item">Missões</a>
            <a href="#" class="sidebar-item" id="logout">Sair</a>
        </div>
        
                <!-- Content -->
        <div class="content">
            <!-- Header -->
            <div class="header">
                <div class="search-container">
                    <input type="text" placeholder="Search Quiz">
                </div>
                <a href="admin_criar_quizz.html" class="create-quiz">Criar Quiz</a>
                <a href="perfil.html" class="profile">
                    <img src="../imagens/avatar.png" alt="Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['nomeUsuario']); ?></span>
                </a>
            </div>
            
            <h1>Aprovar jogos/ Quizzes</h1>
            
            <div class="quiz-details">
                <div class="quiz-question">
                    <h2>Pergunta 1:</h2>
                    <img src="../imagens/quiz-image.png" width="300px" height="300px" alt="Imagem da Pergunta">

        <!-- Content -->
        <div class="content">
            <h1><?php echo htmlspecialchars($quiz['Nome']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($quiz['Descricao'])); ?></p>

            <div class="action-buttons">
                <form method="POST">
                    <button type="submit" name="acao" value="Aprovado" class="btn-accept">Aprovar</button>
                    <button type="submit" name="acao" value="Recusado" class="btn-reject">Recusar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const logoutLink = document.getElementById('logout');
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm("Tem certeza que deseja sair?")) {
                window.location.href = 'logout.php';
            }
        });
    </script>
</body>
</html>
