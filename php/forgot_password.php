<?php
session_start();
require_once '/home/seguram1/config.php';

// Importação correta do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/../vendor/PHPMailer-master/src/SMTP.php';
require __DIR__ . '/../vendor/PHPMailer-master/src/Exception.php';

// Conectar ao banco de dados de utilizadores
$conn = getDBUtilizadores();

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    // Verificar se o e-mail existe na base de dados
    $stmt = $conn->prepare("SELECT ID_Utilizador FROM Utilizador WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $mensagem = "Erro: Este e-mail não está registado.";
    } else {
        $stmt->bind_result($idUsuario);
        $stmt->fetch();
        $stmt->close();

        // Gerar um token seguro
        $token = bin2hex(random_bytes(32));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $expiraEm = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Inserir ou atualizar o token na base de dados
        $stmt = $conn->prepare("INSERT INTO Recuperacao_Senha (ID_Utilizador, Token, Expira_Em) 
                                VALUES (?, ?, ?) 
                                ON DUPLICATE KEY UPDATE Token = VALUES(Token), Expira_Em = VALUES(Expira_Em)");
        $stmt->bind_param("iss", $idUsuario, $tokenHash, $expiraEm);
        $stmt->execute();
        $stmt->close();

        // Criar link de redefinição de senha
        $resetLink = "https://seguramentekids.pt/php/reset_password.php?token=" . urlencode($token);

        // Enviar e-mail via PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(SMTP_USER, 'SeguraMente');
            $mail->addAddress($email);
            $mail->Subject = "Redefinir sua senha - SeguraMente";
            $mail->Body = "Olá,\n\nRecebemos um pedido para redefinir a sua palavra-passe.\n\nClique no link abaixo para redefinir:\n\n$resetLink\n\nEste link é válido por 1 hora.";

            $mail->send();
            $mensagem = "Um link de redefinição de palavra-passe foi enviado para o seu e-mail.";
        } catch (Exception $e) {
            $mensagem = "Erro ao enviar o e-mail: " . $mail->ErrorInfo;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Palavra-passe - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/forgot_password.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
    
</head>
<body>
    <div class="container">
        <!-- Left Side with Image -->
        <div class="left"></div>

        <!-- Right Side with Form -->
        <div class="right">
            <div class="site-title">
                <a href="../index.html" class="site-title-link">
                  SeguraMente<span class="kids-text">KIDS</span>
                </a>
            </div>   
            <h1>Recuperar Palavra-passe</h1>
            <p>Insira o seu email para recuperar a sua palavra-passe. Enviaremos um link para o seu email.</p>
            
            <?php if (!empty($mensagem)) { echo "<p style='color: red;'>$mensagem</p>"; } ?>
            
            <form action="forgot_password.php" method="post">
                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" name="email" id="email" placeholder="Insere o teu email" required>
                </div>
                <button type="submit" class="btn-primary">Enviar</button>
            </form>
            <a href="register.php" class="back-link">Voltar ao registo</a>
        </div>
    </div>
</body>
</html>
