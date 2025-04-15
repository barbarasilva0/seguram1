<?php
session_start();
require_once '/home/seguram1/config.php';
require_once __DIR__ . '/../utils/missoes.php';

$conn = getDBUtilizadores();
$connJogos = getDBJogos();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST["nome"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $tipoUsuario = "jogador";

    if (empty($nome) || empty($username) || empty($email) || empty($password)) {
        die("Erro: Todos os campos são obrigatórios.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Erro: Formato de email inválido.");
    }

    $stmt = $conn->prepare("SELECT ID_Utilizador FROM Utilizador WHERE Email = ? OR Username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("Erro: Este email ou username já estão registados.");
    }
    $stmt->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO Utilizador (Nome, Username, Email, Password, Tipo_de_Utilizador) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nome, $username, $email, $passwordHash, $tipoUsuario);

    if ($stmt->execute()) {
        $idUsuario = $stmt->insert_id;

        // Criar perfil associado ao novo utilizador
        $perfilStmt = $conn->prepare("INSERT INTO Perfil (Pontuacao_Total, Conquistas, ID_Utilizador) VALUES (0, '', ?)");
        $perfilStmt->bind_param("i", $idUsuario);
        $perfilStmt->execute();
        $perfilStmt->close();

        $_SESSION['idUsuario'] = $idUsuario;
        $_SESSION['nomeUsuario'] = $nome;
        $_SESSION['username'] = $username;
        $_SESSION['tipoUsuario'] = $tipoUsuario;

        gerarMissoesIniciais($idUsuario, $connJogos);

        // Associar dados anteriores se vier de um quiz
        if (isset($_GET['pin']) && is_numeric($_GET['pin']) && isset($_SESSION['nicknameTemporario'])) {
            $pin = intval($_GET['pin']);
            $nicknameTemp = $_SESSION['nicknameTemporario'];

            // Atualizar RespostasJogador
            $j = $connJogos->prepare("UPDATE RespostasJogador SET ID_Utilizador = ? WHERE PIN = ? AND ID_Utilizador IS NULL AND Nickname = ?");
            $j->bind_param("iis", $idUsuario, $pin, $nicknameTemp);
            $j->execute();
            $j->close();

            // Atualizar JogadoresLobby
            $jl = $connJogos->prepare("UPDATE JogadoresLobby SET ID_Utilizador = ?, Tipo = 'registado' WHERE PIN = ? AND Nickname = ?");
            $jl->bind_param("iis", $idUsuario, $pin, $nicknameTemp);
            $jl->execute();
            $jl->close();

            // Limpar da sessão
            unset($_SESSION['nicknameTemporario']);
        }

        $stmt->close();
        $conn->close();
        $connJogos->close();

        header("Location: dashboard.php");
        exit();
    } else {
        die("Erro ao registar utilizador: " . $conn->error);
    }
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/register.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
    
</head>
<body>
    <div class="container">
        <!-- Lado Esquerdo com Imagem -->
        <div class="left"></div>

        <!-- Lado Direito com Formulário -->
        <div class="right">
            <div class="site-title">
                <a href="../index.html" class="site-title-link">
                  SeguraMente<span class="kids-text">KIDS</span>
                </a>
            </div>            
            <h1>Cria a tua conta</h1>
            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" placeholder="Insere o teu nome" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Cria o teu username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" id="email" name="email" placeholder="Insere o teu email" required>
                </div>
                <div class="form-group">
                    <label for="password">Palavra-passe*</label>
                    <input type="password" id="password" name="password" placeholder="Palavra-passe" required onkeyup="checkPasswordStrength()">
                    <div id="password-strength" class="password-strength"></div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="remember">
                    <label for="remember">Lembrar a minha palavra-passe</label>
                </div>
                <button type="submit" class="btn-primary">Registar</button>
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
