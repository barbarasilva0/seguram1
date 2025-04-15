<?php
session_start();
require_once '/home/seguram1/config.php';

$connJogos = getDBJogos();
$connJogos->set_charset("utf8mb4");

$termo = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT ID_Jogo, Nome, Descricao FROM Jogo WHERE Estado IN ('Ativo', 'Aprovado')";
$params = [];
if (!empty($termo)) {
    $query .= " AND Nome LIKE ?";
    $params[] = "%$termo%";
}

$stmt = $connJogos->prepare($query);
if (!empty($params)) {
    $stmt->bind_param("s", $params[0]);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisar Jogos - SeguraMente</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .search-results-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .search-results-container h2 {
            margin-bottom: 20px;
            color: #1935CA;
        }
        .quiz-card {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .quiz-card img {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 20px;
        }
        .quiz-card .quiz-content {
            flex: 1;
        }
        .quiz-card .quiz-title {
            font-weight: bold;
            font-size: 18px;
        }
        .quiz-card .quiz-description {
            color: #666;
            margin: 5px 0;
        }
        .quiz-card .quiz-button {
            display: inline-block;
            background: #1935CA;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="search-results-container">
        <h2>Resultados para "<?php echo htmlspecialchars($termo); ?>"</h2>

        <form method="GET" action="">
            <input type="text" name="search" value="<?php echo htmlspecialchars($termo); ?>" placeholder="Pesquisar jogos...">
            <button type="submit">Pesquisar</button>
        </form>

        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="quiz-card">';
                echo '<img src="../imagens/quiz-image.webp" alt="Quiz">';
                echo '<div class="quiz-content">';
                echo '<div class="quiz-title">' . htmlspecialchars($row['Nome'], ENT_QUOTES, 'UTF-8') . '</div>';
                echo '<div class="quiz-description">' . htmlspecialchars($row['Descricao'], ENT_QUOTES, 'UTF-8') . '</div>';
                echo '<a href="jogar_quizz.php?id=' . intval($row['ID_Jogo']) . '" class="quiz-button">Jogar</a>';
                echo '</div></div>';
            }
        } else {
            echo "<p>Nenhum resultado encontrado.</p>";
        }
        $stmt->close();
        $connJogos->close();
        ?>
    </div>
</body>
</html>
