<?php
$senhaDigitada = "senha123"; // Teste com a senha de um usuário real
$hashArmazenado = "$2y$10$pfDpFd/I/vZfuisoEwc0EeaMwVtzMdZ.hcJcwrGnwzWLHP4xjV/Em"; // Copie da tabela Utilizador

if (password_verify($senhaDigitada, $hashArmazenado)) {
    echo "✅ Senha correta!";
} else {
    echo "❌ Senha incorreta!";
}
?>
