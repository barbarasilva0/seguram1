<?php
$senhaOriginal = "senha123"; // Senha real do usuário
$novoHash = password_hash($senhaOriginal, PASSWORD_DEFAULT);

echo "Nova senha hash gerada: " . $novoHash;
?>
