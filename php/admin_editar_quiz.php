<?php
session_start();
require_once '/home/seguram1/config.php';

if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$connJogos = getDBJogos();
$connJogos->set_charset("utf8mb4");

if (!isset($_GET['id'])) {
    header("Location: admin_aprovar_quizz.php");
    exit();
}

$idJogo = intval($_GET['id']);

// Buscar dados atuais do quiz
$stmt = $connJogos->prepare("SELECT Nome, Descricao FROM Jogo WHERE ID_Jogo = ?");
$stmt->bind_param("i", $idJogo);
$stmt->execute();
$result = $stmt->get_result();
$quiz = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];

    $stmtUpdate = $connJogos->prepare("UPDATE Jogo SET Nome = ?, Descricao = ? WHERE ID_Jogo = ?");
    $stmtUpdate->bind_param("ssi", $nome, $descricao, $idJogo);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    header("Location: admin_ver_quizz.php?id=$idJogo");
    exit();
}

$connJogos->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Editar Quiz</title>
    <link rel="stylesheet" href="../css/admin_editar_quiz.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
</head>
<body>
    <div class="container">
        <h1>Editar Quiz</h1>
        <form method="POST">
            <label>Nome do Quiz:</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($quiz['Nome']); ?>" required>

            <label>Descrição:</label>
            <textarea name="descricao" rows="5" required><?php echo htmlspecialchars($quiz['Descricao']); ?></textarea>

            <button type="submit">Guardar Alterações</button>
        </form>
        <a href="admin_ver_quizz.php?id=<?php echo $idJogo; ?>" class="back-link">Voltar</a>
    </div>
</body>
</html>

