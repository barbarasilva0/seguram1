<?php
session_start();
$servername = "localhost";
$username = "seguram1_seguram1"; 
$password_db = "d@V+38y3nWb4FB"; 
$dbJogos = "seguram1_segura_jogos";

$conn = new mysqli($servername, $username, $password_db, $dbJogos);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$idJogo = 1; // ID do jogo atual (substituir pela lógica dinâmica)
$query = "SELECT ID_Pergunta, Texto, Opcoes, Resposta_Correta, Pontos FROM Pergunta WHERE ID_Jogo = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idJogo);
$stmt->execute();
$result = $stmt->get_result();

$perguntas = [];
while ($row = $result->fetch_assoc()) {
    $perguntas[] = $row;
}

$stmt->close();
$conn->close();
?>


<form method="POST" action="processa_respostas.php">
    <?php foreach ($perguntas as $pergunta): ?>
        <h3><?php echo htmlspecialchars($pergunta['Texto']); ?></h3>
        <?php
        $opcoes = explode(", ", $pergunta['Opcoes']); // Separar opções
        foreach ($opcoes as $opcao):
        ?>
            <input type="radio" name="resposta[<?php echo $pergunta['ID_Pergunta']; ?>]" value="<?php echo htmlspecialchars($opcao); ?>">
            <?php echo htmlspecialchars($opcao); ?><br>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <input type="hidden" name="idJogo" value="<?php echo $idJogo; ?>">
    <button type="submit">Enviar Respostas</button>
</form>


<?php
session_start();
$servername = "localhost";
$username = "seguram1_seguram1"; 
$password_db = "d@V+38y3nWb4FB"; 
$dbJogos = "seguram1_segura_jogos";

$conn = new mysqli($servername, $username, $password_db, $dbJogos);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$idUsuario = $_SESSION['idUsuario'];
$idJogo = $_POST['idJogo'];
$respostasUsuario = $_POST['resposta'];

$pontuacaoTotal = 0;

// Verificar respostas corretas
foreach ($respostasUsuario as $idPergunta => $respostaDada) {
    $query = "SELECT Resposta_Correta, Pontos FROM Pergunta WHERE ID_Pergunta = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $idPergunta);
    $stmt->execute();
    $stmt->bind_result($respostaCorreta, $pontos);
    $stmt->fetch();
    $stmt->close();

    if ($respostaDada === $respostaCorreta) {
        $pontuacaoTotal += $pontos;
    }
}

// Inserir no histórico com a pontuação correta
$stmt = $conn->prepare("
    INSERT INTO Historico (Data, Pontuacao_Obtida, Estado, ID_Utilizador, ID_Jogo)
    VALUES (CURDATE(), ?, 'Concluído', ?, ?)
");
$stmt->bind_param("iii", $pontuacaoTotal, $idUsuario, $idJogo);
$stmt->execute();
$stmt->close();

$conn->close();

header("Location: historico.php"); // Redirecionar para a página de histórico
exit();
?>
