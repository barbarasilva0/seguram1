<?php
session_start();
require_once '/home/seguram1/config.php';
header('Content-Type: application/json');

// Validar parâmetros obrigatórios
$pin = isset($_POST['pin']) ? intval($_POST['pin']) : 0;
$nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : null;

if ($pin <= 0 || !$nickname) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros ausentes ou inválidos.']);
    exit();
}

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Verifica se o jogador já está no lobby
$stmt = $conn->prepare("SELECT ID_Entrada FROM JogadoresLobby WHERE PIN = ? AND Nickname = ?");
$stmt->bind_param("is", $pin, $nickname);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Atualizar timestamp
    $stmt->close();
    $update = $conn->prepare("
        UPDATE JogadoresLobby 
        SET Last_Active = CURRENT_TIMESTAMP, Status = 'online' 
        WHERE PIN = ? AND Nickname = ?
    ");
    $update->bind_param("is", $pin, $nickname);
    if (!$update->execute()) {
        error_log("Erro ao atualizar heartbeat: " . $update->error);
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao atualizar presença.']);
        $update->close();
        $conn->close();
        exit();
    }
    $update->close();

} else {
    $stmt->close();

    // Verificar se o quiz já começou
    $check = $conn->prepare("SELECT Iniciado FROM Lobby WHERE PIN = ?");
    $check->bind_param("i", $pin);
    $check->execute();
    $check->bind_result($iniciado);
    $found = $check->fetch();
    $check->close();

    if (!$found) {
        http_response_code(404);
        echo json_encode(['erro' => 'Lobby não encontrado.']);
        $conn->close();
        exit();
    }

    if (!$iniciado) {
        // Inserir jogador se quiz ainda não começou
        $tipo = isset($_SESSION['idUsuario']) ? 'registado' : 'anonimo';
        $idUtilizador = $_SESSION['idUsuario'] ?? null;

        $insert = $conn->prepare("
            INSERT INTO JogadoresLobby (PIN, ID_Utilizador, Nickname, Tipo, Last_Active, Status) 
            VALUES (?, ?, ?, ?, NOW(), 'online')
        ");
        $insert->bind_param("iiss", $pin, $idUtilizador, $nickname, $tipo);

        if (!$insert->execute()) {
            error_log("Erro ao inserir no heartbeat: " . $insert->error);
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao reinserir jogador.']);
            $insert->close();
            $conn->close();
            exit();
        }
        $insert->close();
    } else {
        // Quiz já iniciado, não pode reinserir
        http_response_code(403);
        echo json_encode(['erro' => 'Quiz já iniciado. Reinserção bloqueada.']);
        $conn->close();
        exit();
    }
}

$conn->close();
echo json_encode(['sucesso' => true]);
exit();
