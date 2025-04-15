<?php
require_once '/home/seguram1/config.php';

header('Content-Type: application/json');

if (!isset($_GET['search']) || strlen(trim($_GET['search'])) < 2) {
    echo json_encode([]);
    exit();
}

$searchTerm = trim($_GET['search']);
$conn = getDBJogos();
$conn->set_charset("utf8mb4");

$query = "SELECT ID_Jogo, Nome FROM Jogo WHERE Nome LIKE CONCAT('%', ?, '%') AND Estado IN ('Ativo', 'Aprovado') LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = [
        'id' => $row['ID_Jogo'],
        'nome' => $row['Nome']
    ];
}

echo json_encode($suggestions);

$stmt->close();
$conn->close();
?>
