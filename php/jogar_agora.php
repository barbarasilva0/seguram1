<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogar Agora</title>
    <link rel="stylesheet" href="../css/jogar_agora.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
</head>
<body>
    <header>
        <a href="../index.html" class="site-title">SeguraMente<span class="kids-text">KIDS</span></a>
        <a href="login.php" class="button-blue">Entrar</a>
    </header>

    <div class="content">
        <label for="pin-input">Inserir PIN ou Link:</label>
        <div class="input-container">
            <input type="text" id="pin-input" placeholder="Insira aqui o PIN ou link">
            <button class="play-button" onclick="redirectToNickname()">Jogar</button>
        </div>
        <p id="error-message" class="error-message">Por favor, insira um PIN ou link antes de continuar.</p>
        
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

        // Detecta se é um computador e desativa a câmera
        function detectDevice() {
            const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            if (!isMobile) {
                document.getElementById("camera-button").disabled = true;
                document.getElementById("camera-button").innerText = "Câmera não disponível no PC";
            }
        }

        async function startCamera() {
            const video = document.getElementById('camera-stream');

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
                
                video.srcObject = stream;
                video.style.display = 'block'; 

                scanQRCode();
            } catch (error) {
                alert('Erro ao acessar a câmera. Verifique se o navegador tem permissões.');
                console.error('Erro da câmera:', error);
            }
        }

        function scanQRCode() {
            const video = document.getElementById('camera-stream');
            const canvas = document.getElementById('qr-canvas');
            const context = canvas.getContext('2d');

            scanning = true;

            setInterval(() => {
                if (video.readyState === video.HAVE_ENOUGH_DATA && scanning) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);

                    const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                    const qrCode = jsQR(imageData.data, canvas.width, canvas.height);

                    if (qrCode) {
                        document.getElementById("pin-input").value = qrCode.data;
                        stopCamera();
                        scanning = false;
                        alert("QR Code reconhecido! Clique em 'Jogar'");
                    }
                }
            }, 1000);
        }

        function stopCamera() {
            const video = document.getElementById('camera-stream');
            if (video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
                video.srcObject = null;
            }
        }

        function redirectToNickname() {
            const pinInput = document.getElementById("pin-input").value.trim();
            const errorMessage = document.getElementById("error-message");
        
            let pin = pinInput;
        
            // Se o input for um link, extrair o número do PIN
            const match = pinInput.match(/pin=(\d{6})/);
            if (match) {
                pin = match[1];
            }
        
            if (pin && /^\d{6}$/.test(pin)) {
                window.location.href = `nickname.php?pin=${encodeURIComponent(pin)}`;
            } else {
                errorMessage.style.display = 'block';
                setTimeout(() => errorMessage.style.display = 'none', 3000);
            }
        }

        // Verifica o dispositivo ao carregar a página
        detectDevice();
    </script>
</body>
</html>
