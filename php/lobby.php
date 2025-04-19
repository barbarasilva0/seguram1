<?php
session_start();
require_once '/home/seguram1/config.php';

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

$pin = isset($_GET['pin']) ? intval($_GET['pin']) : 0;
$nickname = $_SESSION['nomeUsuario'] ?? $_SESSION['username'] ?? ($_SESSION['nicknameTemporario'] ?? null);
$idUsuario = $_SESSION['idUsuario'] ?? null;

if (!$pin || !$nickname) {
    die("Erro: PIN ou nickname não fornecido.");
}

// Buscar dados do lobby
$stmt = $conn->prepare("SELECT ID_Jogo, Criado_por FROM Lobby WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Erro: Lobby não encontrado.");
$lobby = $result->fetch_assoc();
$idJogo = $lobby['ID_Jogo'];
$criadorLobby = $lobby['Criado_por'];
$stmt->close();

$_SESSION['idJogo'] = $idJogo;

// Inserir jogador no lobby, caso não esteja
$stmt = $conn->prepare("SELECT 1 FROM JogadoresLobby WHERE PIN = ? AND Nickname = ?");
$stmt->bind_param("is", $pin, $nickname);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    $tipo = isset($_SESSION['idUsuario']) ? 'registado' : 'anonimo';
    $stmt = $conn->prepare("INSERT INTO JogadoresLobby (PIN, ID_Utilizador, Nickname, Tipo, Last_Active, Status) VALUES (?, ?, ?, ?, NOW(), 'online')");
    $stmt->bind_param("iiss", $pin, $idUsuario, $nickname, $tipo);
    if (!$stmt->execute()) {
        error_log("Erro ao inserir no lobby: " . $stmt->error);
    }
    $stmt->close();
} else {
    $stmt->close();
    // Se já está, atualiza presença
    $stmt = $conn->prepare("UPDATE JogadoresLobby SET Last_Active = NOW(), Status = 'online' WHERE PIN = ? AND Nickname = ?");
    $stmt->bind_param("is", $pin, $nickname);
    $stmt->execute();
    $stmt->close();
}

// Buscar jogadores
$stmt = $conn->prepare("SELECT Nickname FROM JogadoresLobby WHERE PIN = ?");
$stmt->bind_param("i", $pin);
$stmt->execute();
$result = $stmt->get_result();
$players = [];

while ($row = $result->fetch_assoc()) {
    $players[] = $row['Nickname'];
}
$stmt->close();
$conn->close();

$qrCodeUrl = "https://seguramentekids.pt/php/nickname.php?pin=" . urlencode($pin);
$ehCriador = isset($_SESSION['idUsuario']) && $_SESSION['idUsuario'] == $criadorLobby;
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Lobby do Quiz</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/lobby.css">
    <link rel="icon" href="../imagens/favicon.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
</head>
<body>
<header>
    <a href="../index.html" class="site-title">SeguraMenteKIDS</a>
</header>

<div class="container">
    <h1>Lobby do Quiz</h1>
    <div class="pin-container">
        <div class="pin-box">PIN: <?= htmlspecialchars($pin) ?></div>
    </div>

    <div class="qr-container">
        <canvas id="qr-code"></canvas>
    </div>

    <?php if ($ehCriador): ?>
        <button class="start-button" onclick="startQuiz()">Iniciar Quiz</button>
    <?php else: ?>
        <p class="info-message">Aguarde, apenas o criador do lobby pode iniciar o quiz.</p>
    <?php endif; ?>

    <div class="players-container" id="players-container">
        <?php foreach ($players as $player): ?>
            <div class="player-box"><?= htmlspecialchars($player) ?></div>
        <?php endforeach; ?>
    </div>
</div>

    <div id="alerta-jogadores" class="alerta-erro" style="display:none;">
        Precisas de pelo menos 2 jogadores para iniciar o quiz!
    </div>
    
    <div id="modal-expulsar" class="modal-expulsar-overlay" style="display:none;">
        <div class="modal-expulsar-content">
            <h2 id="expulsar-texto">Deseja expulsar este jogador?</h2>
            <div class="modal-expulsar-buttons">
                <button class="btn-expulsar-confirm" id="confirm-expulsar">Sim</button>
                <button class="btn-expulsar-cancel" id="cancel-expulsar">Não</button>
            </div>
        </div>
    </div>


<script>
const pin = "<?= $pin ?>";
const qrCodeUrl = "<?= $qrCodeUrl ?>";
const ehCriador = <?= json_encode($ehCriador) ?>;
const nickname = "<?= addslashes($nickname) ?>";

new QRious({
    element: document.getElementById("qr-code"),
    value: qrCodeUrl,
    size: 200
});

function atualizarLobby() {
    fetch(`atualizar_lobby.php?pin=${pin}`)
        .then(res => res.json())
        .then(data => {
            if (data.iniciado) {
                window.location.href = `quizz.php?pin=${pin}`;
                return;
            }

            if (data.players) {
                const container = document.getElementById("players-container");
                container.innerHTML = "";
                data.players.forEach(player => {
                    const div = document.createElement("div");
                    div.className = "player-box";
                    div.innerHTML = `<span>${player}</span>`;

                    if (ehCriador && player !== nickname) {
                        const kick = document.createElement("button");
                        kick.textContent = "✖";
                        kick.className = "kick-btn";
                       kick.onclick = () => {
                            document.getElementById("modal-expulsar").style.display = "flex";
                            document.getElementById("expulsar-texto").textContent = `Deseja expulsar ${player}?`;
                        
                            document.getElementById("confirm-expulsar").onclick = () => {
                                fetch("expulsar_jogador.php", {
                                    method: "POST",
                                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                    body: `pin=${pin}&nickname=${encodeURIComponent(player)}`
                                }).then(() => {
                                    document.getElementById("modal-expulsar").style.display = "none";
                                    atualizarLobby();
                                });
                            };
                        
                            document.getElementById("cancel-expulsar").onclick = () => {
                                document.getElementById("modal-expulsar").style.display = "none";
                            };
                        };
                        div.appendChild(kick);
                    }

                    container.appendChild(div);
                });
            }
        });
}

function startHeartbeat() {
    return setInterval(() => {
        fetch("heartbeat.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `pin=${pin}&nickname=${encodeURIComponent(nickname)}`
        });
    }, 10000);
}

let heartbeat = startHeartbeat();

window.addEventListener("beforeunload", () => {
    fetch("remover_jogador.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `pin=${pin}&nickname=${encodeURIComponent(nickname)}`
    });
});

document.addEventListener("visibilitychange", () => {
    document.hidden ? clearInterval(heartbeat) : heartbeat = startHeartbeat();
});

function startQuiz() {
    if (!ehCriador) return;

    const container = document.getElementById("players-container");
    const jogadores = container.querySelectorAll(".player-box");
    const totalJogadores = jogadores.length;

    if (totalJogadores <= 1) {
        const alerta = document.getElementById("alerta-jogadores");
        alerta.style.display = "block";

        setTimeout(() => {
            alerta.style.display = "none";
        }, 4000);

        return;
    }

    fetch("iniciar_quiz.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `pin=${pin}`
    }).then(() => {
        window.location.href = `quizz.php?pin=${pin}`;
    });
}

atualizarLobby();
setInterval(atualizarLobby, 1000);
</script>
</body>
</html>
