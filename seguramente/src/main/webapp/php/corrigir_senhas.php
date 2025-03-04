<?php
$servername = "localhost";
$username = "seguram1_seguram1";
$password_db = "d@V+38y3nWb4FB";
$dbname = "seguram1_segura_utilizadores";

$conn = new mysqli($servername, $username, $password_db, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Lista de emails e senhas originais (NÃO HASHED)
$users = [
    'joao@email.com' => 'senha123',
    'maria@email.com' => 'senha456',
    'carlos@email.com' => 'senha789',
    'ana@email.com' => 'segura123',
    'tiago@email.com' => 'protegido456'
];

foreach ($users as $email => $plainPassword) {
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT); // Criptografa corretamente
    $stmt = $conn->prepare("UPDATE Utilizador SET Password = ? WHERE Email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    $stmt->execute();
}

echo "✅ Senhas corrigidas com sucesso!";
$conn->close();
?>
