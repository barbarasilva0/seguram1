<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../utils/missoes.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

function validarTokenGoogle($id_token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($id_token));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ? json_decode($response, true) : false;
}

function gerarUsernameUnico($conn, $email) {
    $base = explode("@", $email)[0];
    $username = $base;
    $contador = 0;

    $stmt = $conn->prepare("SELECT COUNT(*) FROM Utilizador WHERE Username = ?");
    do {
        $proposto = $contador === 0 ? $username : $base . "_" . rand(1000, 9999);
        $stmt->bind_param("s", $proposto);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $contador++;
    } while ($count > 0);
    $stmt->close();

    return $proposto;
}

// Entrada
$data = json_decode(file_get_contents("php://input"), true);
if (empty($data['id_token'])) {
    echo json_encode(["success" => false, "message" => "Token ausente"]);
    exit();
}

$id_token = $data['id_token'];
$google_data = validarTokenGoogle($id_token);

if (!$google_data || !isset($google_data['aud']) || $google_data['aud'] !== '624604963739-qe132vhh6tp66rc3ep90m6ffa4vi8pqr.apps.googleusercontent.com') {
    echo json_encode(["success" => false, "message" => "Token inválido ou client_id incorreto"]);
    exit();
}

// Dados do Google
$nome  = $google_data['name'] ?? '';
$email = $google_data['email'] ?? '';
$foto  = $google_data['picture'] ?? '';
$tipoUsuario = "jogador";

$conn = getDBUtilizadores();
$connJogos = getDBJogos();

if ($conn->connect_error || $connJogos->connect_error) {
    echo json_encode(["success" => false, "message" => "Erro de conexão com a base de dados"]);
    exit();
}

// Verifica se já existe
$stmt = $conn->prepare("SELECT ID_Utilizador, Nome, Username, Tipo_de_Utilizador FROM Utilizador WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Utilizador existente
    $stmt->bind_result($idUsuario, $nomeUsuario, $username, $tipoUsuario);
    $stmt->fetch();

    $_SESSION['idUsuario']    = $idUsuario;
    $_SESSION['nomeUsuario']  = $nomeUsuario;
    $_SESSION['username']     = $username;
    $_SESSION['tipoUsuario']  = strtolower(trim($tipoUsuario));

    // Criar perfil se ainda não existir
    $checkPerfil = $conn->prepare("SELECT 1 FROM Perfil WHERE ID_Utilizador = ?");
    $checkPerfil->bind_param("i", $idUsuario);
    $checkPerfil->execute();
    $checkPerfil->store_result();

    if ($checkPerfil->num_rows === 0) {
        $createPerfil = $conn->prepare("INSERT INTO Perfil (Pontuacao_Total, Conquistas, ID_Utilizador) VALUES (0, '', ?)");
        $createPerfil->bind_param("i", $idUsuario);
        $createPerfil->execute();
        $createPerfil->close();
    }
    $checkPerfil->close();

    // Missões iniciais
    if ($_SESSION['tipoUsuario'] === 'jogador' && !verificarMissoesExistentes($idUsuario, $connJogos)) {
        gerarMissoesIniciais($idUsuario, $connJogos);
    }

} else {
    // Novo utilizador
    $username = gerarUsernameUnico($conn, $email);
    $senhaDummy = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO Utilizador (Nome, Username, Email, Password, Tipo_de_Utilizador, Foto_Google) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nome, $username, $email, $senhaDummy, $tipoUsuario, $foto);

    if ($stmt->execute()) {
        $idUsuario = $stmt->insert_id;

        $_SESSION['idUsuario']    = $idUsuario;
        $_SESSION['nomeUsuario']  = $nome;
        $_SESSION['username']     = $username;
        $_SESSION['tipoUsuario']  = $tipoUsuario;

        // Criar perfil
        $perfil = $conn->prepare("INSERT INTO Perfil (Pontuacao_Total, Conquistas, ID_Utilizador) VALUES (0, '', ?)");
        $perfil->bind_param("i", $idUsuario);
        $perfil->execute();
        $perfil->close();

        gerarMissoesIniciais($idUsuario, $connJogos);
    } else {
        echo json_encode(["success" => false, "message" => "Erro ao criar utilizador"]);
        exit();
    }
}

$stmt->close();
$conn->close();
$connJogos->close();
session_write_close();

// Sucesso
echo json_encode(["success" => true, "redirect" => "dashboard.php"]);
exit();
?>
