<?php
session_start();
require_once '/home/seguram1/config.php'; // Importa a configuração do banco de dados

// Conectar ao banco de dados de utilizadores
$conn = getDBUtilizadores();

// Verificar se o token foi passado na URL
if (!isset($_GET["token"])) {
    die("Erro: Token inválido.");
}

$token = $_GET["token"];

// Verificar se o token é válido e ainda está ativo
$stmt = $conn->prepare("SELECT ID_Utilizador, Token, Expira_Em FROM Recuperacao_Senha WHERE Expira_Em > NOW()");
$stmt->execute();
$result = $stmt->get_result();

$usuarioValido = false;
while ($row = $result->fetch_assoc()) {
    if (password_verify($token, $row["Token"])) {
        $usuarioValido = true;
        $idUsuario = $row["ID_Utilizador"];
        break;
    }
}

$stmt->close();

if (!$usuarioValido) {
    die("Erro: Token inválido ou expirado.");
}

// Se o formulário foi submetido, atualizar a senha
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $novaSenha = $_POST["password"];
    $confirmarSenha = $_POST["confirm_password"];

    if ($novaSenha !== $confirmarSenha) {
        die("Erro: As palavras-passe não coincidem.");
    }

    // Hash da nova senha
    $password_hash = password_hash($novaSenha, PASSWORD_DEFAULT);

    // Atualizar senha na base de dados
    $stmt = $conn->prepare("UPDATE Utilizador SET Password = ? WHERE ID_Utilizador = ?");
    $stmt->bind_param("si", $password_hash, $idUsuario);
    $stmt->execute();
    $stmt->close();

    // Remover o token usado
    $stmt = $conn->prepare("DELETE FROM Recuperacao_Senha WHERE ID_Utilizador = ?");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $stmt->close();

    // ✅ Redirecionar para login.php após redefinir a senha
    header("Location: login.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Palavra-passe - SeguraMente</title>
    <link rel="stylesheet" href="../css/reset_password.css">
</head>
<body>
    <div class="container">
        <div class="right">
            <div class="site-title">
                <a href="../index.html" class="site-title-link">SeguraMente</a>
            </div>   
            <h1>Redefinir Palavra-passe</h1>
            <form method="post">
                <div class="form-group">
                    <label for="password">Nova Palavra-passe*</label>
                    <input type="password" name="password" id="password" placeholder="Digite a nova palavra-passe" required onkeyup="checkPasswordStrength()">
                    <div id="password-strength" class="password-strength"></div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Palavra-passe*</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirme a nova palavra-passe" required>
                </div>
                <button type="submit" class="btn-primary">Redefinir</button>
            </form>
        </div>
    </div>

    <script>
        function checkPasswordStrength() {
            var password = document.getElementById("password").value;
            var strengthBar = document.getElementById("password-strength");

            var strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[@$!%*?&]+/)) strength++;

            if (strength === 0) {
                strengthBar.innerHTML = "Muito Fraca";
                strengthBar.className = "password-strength weak";
            } else if (strength === 1 || strength === 2) {
                strengthBar.innerHTML = "Fraca";
                strengthBar.className = "password-strength weak";
            } else if (strength === 3) {
                strengthBar.innerHTML = "Média";
                strengthBar.className = "password-strength medium";
            } else if (strength === 4) {
                strengthBar.innerHTML = "Forte";
                strengthBar.className = "password-strength strong";
            } else if (strength === 5) {
                strengthBar.innerHTML = "Muito Forte";
                strengthBar.className = "password-strength very-strong";
            }
        }
    </script>

</body>
</html>
