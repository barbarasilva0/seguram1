<?php
session_start();
require_once '/home/seguram1/config.php';
require_once __DIR__ . '/../utils/missoes.php';

$conn = getDBUtilizadores();
$connJogos = getDBJogos();
$mensagemErro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT ID_Utilizador, Nome, Username, Tipo_de_Utilizador, Password FROM Utilizador WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($idUsuario, $nomeUsuario, $username, $tipoUsuario, $hashedPassword);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['idUsuario'] = $idUsuario;
                $_SESSION['nomeUsuario'] = $nomeUsuario;
                $_SESSION['username'] = $username;
                $_SESSION['tipoUsuario'] = strtolower(trim($tipoUsuario));

                // Criar perfil se não existir
                $checkPerfil = $conn->prepare("SELECT 1 FROM Perfil WHERE ID_Utilizador = ?");
                $checkPerfil->bind_param("i", $idUsuario);
                $checkPerfil->execute();
                $checkPerfil->store_result();
                if ($checkPerfil->num_rows === 0) {
                    $createPerfil = $conn->prepare("INSERT INTO Perfil (Pontuacao_Total, Conquistas, ID_Utilizador) VALUES (0, '', ?)");
                    $createPerfil->bind_param("i", $idUsuario);
                    $createPerfil->execute();
                    $createPerfil->close();
                }
                $checkPerfil->close();

                // Atribuir missões iniciais se não existirem
                if ($_SESSION['tipoUsuario'] === "jogador" && !verificarMissoesExistentes($idUsuario, $connJogos)) {
                    gerarMissoesIniciais($idUsuario, $connJogos);
                }

                session_write_close(); // segurança

                header("Location: " . ($_SESSION['tipoUsuario'] === "admin" ? "admin_aprovar_quizz.php" : "dashboard.php"));
                exit();
            } else {
                $mensagemErro = "Palavra-passe incorreta!";
            }
        } else {
            $mensagemErro = "Utilizador não encontrado!";
        }

        $stmt->close();
    } else {
        $mensagemErro = "Preencha todos os campos!";
    }
}

$conn->close();
$connJogos->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/login.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
    
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <div class="container">
        <div class="left"></div>
        <div class="right">
            <div class="site-title">
                <a href="../index.html" class="site-title-link">
                  SeguraMente<span class="kids-text">KIDS</span>
                </a>
            </div>
            <h1>Entra na tua conta</h1>

            <?php if (!empty($mensagemErro)) { ?>
                <p style="color: red;"><?php echo htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>

            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" id="email" name="email" placeholder="Insere o teu email" required>
                </div>
                <div class="form-group">
                    <label for="password">Palavra-passe*</label>
                    <input type="password" id="password" name="password" placeholder="Palavra-passe" required>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="remember">
                    <label for="remember">Lembrar a minha palavra-passe</label>
                </div>
                <button type="submit" class="btn-primary">Entrar</button>

                <div class="or-text">Ou</div>

                <!-- Botão de Login com Google -->
                <div id="g_id_onload"
                     data-client_id="624604963739-qe132vhh6tp66rc3ep90m6ffa4vi8pqr.apps.googleusercontent.com"
                     data-context="signin"
                     data-ux_mode="popup"
                     data-callback="handleCredentialResponse"
                     data-auto_prompt="false">
                </div>
                <div class="g_id_signin" data-type="standard"></div>
                
                <div class="or-text">Ou</div>

                <button type="button" class="btn-secondary" onclick="window.location.href='register.php'">Registar</button>
            </form>
            <a href="forgot_password.php" class="forgot-password">Esqueceste-te da palavra-passe?</a>
        </div>
    </div>

    <script>
        function handleCredentialResponse(response) {
            const id_token = response.credential;
        
            fetch('google_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_token })
            })
            .then(response => response.json())
            .then(data => {
                console.log(data); // DEBUG: Ver a resposta do PHP
            
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    alert("Erro ao autenticar com Google: " + data.message);
                }
            })

            console.log("ID Token recebido:", response.credential);

            const payload = JSON.parse(atob(response.credential.split('.')[1]));
            console.log("Payload do ID Token:", payload);

        }
    </script>
</body>
</html>
