<?php
session_start();
require_once '/home/seguram1/config.php';

if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

$idUsuario = intval($_SESSION['idUsuario']);
$username = htmlspecialchars($_SESSION['username'] ?? 'Jogador', ENT_QUOTES, 'UTF-8');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pin'])) {
    $pin = intval(trim($_POST['pin']));
    if ($pin > 0) {
        $conn = getDBJogos();
        $conn->set_charset("utf8mb4");

        // Verifica se o PIN existe na tabela Lobby
        $stmt = $conn->prepare("SELECT ID_Jogo FROM Lobby WHERE PIN = ?");
        $stmt->bind_param("i", $pin);
        $stmt->execute();
        $stmt->bind_result($idJogo);
        if ($stmt->fetch()) {
            $stmt->close();

            // Verifica se o jogador já está no lobby
            $stmt = $conn->prepare("SELECT 1 FROM JogadoresLobby WHERE PIN = ? AND ID_Utilizador = ?");
            $stmt->bind_param("ii", $pin, $idUsuario);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                $stmt->close();
                // Inserir jogador no lobby
                $stmt = $conn->prepare("INSERT INTO JogadoresLobby (PIN, ID_Utilizador, Nickname, Tipo) VALUES (?, ?, ?, 'registado')");
                $stmt->bind_param("iis", $pin, $idUsuario, $username);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt->close();
            }

            $conn->close();
            header("Location: lobby.php?pin=" . urlencode($pin));
            exit();
        } else {
            $stmt->close();
            $conn->close();
            $erro = "PIN não encontrado.";
        }
    } else {
        $erro = "PIN inválido.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogar Agora</title>
    <link rel="stylesheet" href="../css/jogar_agora_registado.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
</head>
<body>
<header>
        <h1>
          <a href="dashboard.php" style="text-decoration: none; color: inherit;">
            SeguraMente<span class="kids-text">KIDS</span>
          </a>
        </h1>
</header>

<div class="content">
    <label for="pin-input">Insera o PIN do quiz:</label>
    <div class="input-container">
        <input type="text" id="pin-input" placeholder="Insira aqui o PIN ou link">
        <button class="play-button" onclick="redirectToGame()">Jogar</button>
    </div>
    <p id="error-message" class="error-message" style="display:none;">Por favor, insira um PIN ou link válido antes de continuar.</p>

    <!-- QR Code Scanner -->
    <div class="qr-code">
        <button class="camera-button" id="camera-button" onclick="startCamera()">Ativar Câmera</button>
        <video id="camera-stream" autoplay></video>
        <canvas id="qr-canvas" hidden></canvas>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsQR/1.3.2/jsQR.min.js"></script>
<script>
    let scanning = false;
    let scanInterval = null;

    function detectDevice() {
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        if (!isMobile) {
            const camBtn = document.getElementById("camera-button");
            camBtn.disabled = true;
            camBtn.innerText = "Câmera não disponível no PC";
        }
    }

    async function startCamera() {
        const video = document.getElementById('camera-stream');

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
            video.srcObject = stream;
            video.style.display = 'block';
            scanning = true;
            scanQRCode();
        } catch (error) {
            alert('Erro ao acessar a câmera. Verifique as permissões.');
            console.error('Erro da câmera:', error);
        }
    }

    function stopCamera() {
        const video = document.getElementById('camera-stream');
        if (video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
            video.srcObject = null;
        }
        clearInterval(scanInterval);
    }

    function scanQRCode() {
        const video = document.getElementById('camera-stream');
        const canvas = document.getElementById('qr-canvas');
        const context = canvas.getContext('2d');

        scanInterval = setInterval(() => {
            if (video.readyState === video.HAVE_ENOUGH_DATA && scanning) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                const qrCode = jsQR(imageData.data, canvas.width, canvas.height);

                if (qrCode) {
                    scanning = false;
                    stopCamera();
                    document.getElementById("pin-input").value = qrCode.data;
                    alert("QR Code reconhecido! Clique em 'Jogar'");
                }
            }
        }, 700);
    }

    function redirectToGame() {
        const input = document.getElementById("pin-input").value.trim();
        const error = document.getElementById("error-message");

        if (!input) {
            error.textContent = "Por favor, insira um PIN ou link.";
            error.style.display = 'block';
            setTimeout(() => error.style.display = 'none', 4000);
            return;
        }

        let pin = "";

        try {
            const url = new URL(input);
            pin = new URLSearchParams(url.search).get("pin");
        } catch {
            if (/^\d{6}$/.test(input)) {
                pin = input;
            }
        }

        if (pin) {
            window.location.href = `lobby.php?pin=${encodeURIComponent(pin)}`;
        } else {
            error.textContent = "Formato inválido. Insira um PIN numérico de 6 dígitos ou um link válido.";
            error.style.display = 'block';
            setTimeout(() => error.style.display = 'none', 4000);
        }
    }

    // Permitir pressionar Enter
    document.getElementById("pin-input").addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            redirectToGame();
        }
    });

    window.addEventListener("beforeunload", stopCamera);

    detectDevice();
</script>
</body>
</html>
