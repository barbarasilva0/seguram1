$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO Utilizador (Nome, Email, Password, Tipo_de_Utilizador) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nome, $email, $password_hash, $tipoUsuario);
$stmt->execute();
