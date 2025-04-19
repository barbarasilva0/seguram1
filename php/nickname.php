<?php
session_start();
require_once '/home/seguram1/config.php';

$pin = isset($_GET['pin']) ? intval($_GET['pin']) : 0;
if ($pin <= 0 || strlen((string)$pin) !== 6) {
    die("Erro: PIN inválido.");
}

$erro = "";
$nicknameSeguro = "";

// Processar o formulário
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nickname'])) {
    $nickname = trim($_POST['nickname']);

    if ($nickname === "") {
        $erro = "Erro: Por favor, insira um nickname.";
    } elseif (mb_strlen($nickname) > 30) {
        $erro = "Erro: O nickname é demasiado longo (máx. 30 caracteres).";
    } else {
        // Sanitizar e guardar
        $nicknameSeguro = htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8');
        $_SESSION['nicknameTemporario'] = $nicknameSeguro;

        // Inserir na base de dados (evita duplicação)
        $conn = getDBJogos();
        $conn->set_charset("utf8mb4");

        $stmt = $conn->prepare("SELECT 1 FROM JogadoresLobby WHERE PIN = ? AND Nickname = ?");
        $stmt->bind_param("is", $pin, $nicknameSeguro);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO JogadoresLobby (PIN, ID_Utilizador, Nickname, Last_Active, Status, Tipo) VALUES (?, NULL, ?, NOW(), 'online', 'anonimo')");
            $stmt->bind_param("is", $pin, $nicknameSeguro);
            $stmt->execute();
        } else {
            $stmt->close();
        }

        $conn->close();

        // Redirecionar
        header("Location: lobby.php?pin=" . urlencode($pin) . "&nickname=" . urlencode($nicknameSeguro));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolher Nome Temporário</title>
    <link rel="stylesheet" href="../css/nickname.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
</head>
<body>
<header>
    <a href="../index.html" class="site-title">SeguraMenteKIDS</a>
    <a href="login.php" class="button-blue">Entrar</a>
</header>

<div class="container">
    <h1>Escolher Nome Temporário</h1>
    <div class="pin-display">PIN: <?= htmlspecialchars((string)$pin) ?></div>

    <form method="post" autocomplete="off">
        <label for="nickname">Insira o seu nome temporário:</label>
        <input type="text"
               name="nickname"
               id="nickname"
               maxlength="30"
               placeholder="Digite seu nome"
               required
               value="<?= isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname'], ENT_QUOTES, 'UTF-8') : '' ?>">

        <?php if ($erro): ?>
            <p class="error-message"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <button type="submit" class="play-button">Entrar no Lobby</button>
    </form>
</div>
</body>
</html>
