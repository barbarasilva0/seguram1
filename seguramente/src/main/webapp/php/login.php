<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $loginSuccess = false;

    if (!empty($email) && !empty($password)) {
        // Conectar ao banco de dados
        $servername = "localhost";
        $username = "seguram1_seguram1";
        $password_db = "d@V+38y3nWb4FB";
        $dbname = "seguram1_segura_utilizadores";

        $conn = new mysqli($servername, $username, $password_db, $dbname);
        if ($conn->connect_error) {
            die("Erro de conexão: " . $conn->connect_error);
        }

        // Buscar o usuário
        $stmt = $conn->prepare("SELECT ID_Utilizador, Nome, Tipo_de_Utilizador, Password FROM Utilizador WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $idUsuario = $row['ID_Utilizador'];
            $nomeUsuario = $row['Nome'];
            $tipoUsuario = strtolower(trim($row['Tipo_de_Utilizador']));
            $hashedPassword = $row['Password'];

            // Log para verificar o que está sendo recuperado do banco
            error_log("Email digitado: " . $email);
            error_log("Senha digitada: " . $password);
            error_log("Senha armazenada no banco: " . $hashedPassword);
            error_log("Tipo de Usuário: " . $tipoUsuario);

            // Verificar senha
            if (password_verify($password, $hashedPassword)) {
                $loginSuccess = true;

                // Criar sessão
                $_SESSION['idUsuario'] = $idUsuario;
                $_SESSION['nomeUsuario'] = $nomeUsuario;
                $_SESSION['tipoUsuario'] = $tipoUsuario;

                session_write_close();

                // Redirecionamento corrigido
                if ($tipoUsuario === "admin") {
                    header("Location: admin_aprovar_quizz.php");
                    exit();
                } elseif ($tipoUsuario === "jogador") {
                    header("Location: dashboard.php");
                    exit();
                } else {
                    error_log("Tipo de usuário não reconhecido: " . $tipoUsuario);
                }
            } else {
                error_log("Erro: Senha incorreta!");
            }
        } else {
            error_log("Erro: Usuário não encontrado!");
        }

        $stmt->close();
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SeguraMente</title>
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="container">
        <div class="left"></div>
        <div class="right">
            <div class="site-title">
                <a href="../index.html" class="site-title-link">SeguraMente</a>
            </div>
            <h1>Entra na tua conta</h1>
            <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !$loginSuccess) { ?>
                <p style="color: red;">Email ou palavra-passe incorretos.</p>
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
                <button type="button" class="btn-secondary" onclick="window.location.href='register.php'">Registar</button>
            </form>
            <a href="forgot_password.php" class="forgot-password">Esqueceu da palavra-passe</a>
        </div>
    </div>
</body>
</html>
