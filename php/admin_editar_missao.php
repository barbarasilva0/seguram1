<?php
session_start();
require_once '/home/seguram1/config.php';

// Verificar se é admin
if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

$idMissao = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obter o nome original da missão (antes de editar)
$stmt = $conn->prepare("SELECT Nome FROM Missao_Semanal WHERE ID_Missao = ? LIMIT 1");
$stmt->bind_param("i", $idMissao);
$stmt->execute();
$stmt->bind_result($nomeAntigo);
$stmt->fetch();
$stmt->close();

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novoNome = htmlspecialchars($_POST['nome']);
    $novoObjetivo = intval($_POST['objetivo']);
    $descricao = htmlspecialchars($_POST['descricao']);

    // Atualizar todas as missões com o mesmo nome
    $stmt = $conn->prepare("UPDATE Missao_Semanal SET Nome = ?, Objetivo = ?, Descricao = ? WHERE Nome = ?");
    $stmt->bind_param("siss", $novoNome, $novoObjetivo, $descricao, $nomeAntigo);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_missoes.php?sucesso=editada");
    exit();
}

// Buscar dados da missão com base no ID
$stmt = $conn->prepare("SELECT Nome, Objetivo, Descricao FROM Missao_Semanal WHERE ID_Missao = ? LIMIT 1");
$stmt->bind_param("i", $idMissao);
$stmt->execute();
$stmt->bind_result($nomeMissao, $objetivo, $descricao);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Editar Missão | Admin</title>
    <link rel="stylesheet" href="../css/admin_editar_missao.css">
</head>
<body>
    <div class="container">
        <h1>Editar Missão Semanal</h1>
        <form method="POST">
            <label for="nome">Nome da Missão:</label>
            <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($nomeMissao) ?>" required>
            
            <label for="descricao">Descrição da Missão:</label>
            <textarea name="descricao" id="descricao" required><?= htmlspecialchars($descricao ?? '') ?></textarea>

            <label for="objetivo">Objetivo (número de vezes):</label>
            <input type="number" name="objetivo" id="objetivo" min="1" value="<?= intval($objetivo) ?>" required>

            <div class="buttons">
                <button type="submit">Guardar</button>
                <a href="admin_missoes.php" class="cancel">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
