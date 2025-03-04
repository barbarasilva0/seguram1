<?php
// Conectar ao banco de dados
$servername = "localhost";
$username = "seguram1"; // Usuário do MySQL
$password_db = "d@V+38y3nWb4FB"; // Senha do MySQL
$dbname = "seguram1_segura_utilizadores";

$conn = new mysqli($servername, $username, $password_db, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Lista de emails e suas senhas antigas
$usuarios = [
    'joao@email.com' => 'senha123',
    'maria@email.com' => 'senha456',
    'carlos@email.com' => 'senha789',
    'ana@email.com' => 'segura123',
    'tiago@email.com' => 'protegido456',
];

// Atualizar cada senha para `password_hash()`
foreach ($usuarios as $email => $senha_plana) {
    $senhaHash = password_hash($senha_plana, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE Utilizador SET Password = ? WHERE Email = ?");
    $stmt->bind_param("ss", $senhaHash, $email);
    $stmt->execute();
}

echo "Senhas atualizadas com sucesso!";
$conn->close();
?>
