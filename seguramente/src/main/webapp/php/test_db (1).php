<?php
$servername = "localhost";
$username = "seguram1_seguram1";
$password_db = "d@V+38y3nWb4FB";
$dbUtilizadores = "seguram1_segura_utilizadores";
$dbJogos = "seguram1_segura_jogos";

// Conectar ao banco de dados
$connUtilizadores = new mysqli($servername, $username, $password_db, $dbUtilizadores);
$connJogos = new mysqli($servername, $username, $password_db, $dbJogos);

if ($connUtilizadores->connect_error) {
    die("Erro na conexão com Utilizadores: " . $connUtilizadores->connect_error);
}
if ($connJogos->connect_error) {
    die("Erro na conexão com Jogos: " . $connJogos->connect_error);
}

// Definir UTF-8 como charset
$connUtilizadores->set_charset("utf8mb4");
$connJogos->set_charset("utf8mb4");

// Testar dados na tabela Utilizador
$sql = "SELECT ID_Utilizador, Nome FROM Utilizador";
$result = $connUtilizadores->query($sql);
echo "<h3>Usuários na base de dados:</h3>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row["ID_Utilizador"] . " - Nome: " . htmlspecialchars($row["Nome"], ENT_QUOTES, 'UTF-8') . "<br>";
    }
} else {
    echo "Nenhum usuário encontrado!<br>";
}

// Testar dados na tabela Jogo
$sql = "SELECT ID_Jogo, Nome FROM Jogo";
$result = $connJogos->query($sql);
echo "<h3>Jogos na base de dados:</h3>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row["ID_Jogo"] . " - Nome: " . htmlspecialchars($row["Nome"], ENT_QUOTES, 'UTF-8') . "<br>";
    }
} else {
    echo "Nenhum jogo encontrado!<br>";
}

$connUtilizadores->close();
$connJogos->close();
?>
