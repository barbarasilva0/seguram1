<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="java.sql.*" %>
<%@ page import="javax.servlet.http.HttpSession" %>

<%
    // Verificar sessão do usuário
    HttpSession sessao = request.getSession();
    String nomeUsuario = (String) sessao.getAttribute("nomeUsuario");
    boolean usuarioLogado = (nomeUsuario != null);
%>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogar Agora</title>
    <link rel="stylesheet" href="html/css/jogar_agora.css">
</head>
<body>
    <header>
        <a href="index.jsp" class="site-title">SeguraMente</a>
        <% if (usuarioLogado) { %>
            <a href="dashboard.jsp" class="button-blue">Dashboard</a>
        <% } else { %>
            <a href="login.jsp" class="button-blue">Entrar</a>
        <% } %>
    </header>

    <div class="content">
        <label for="pin-input">Inserir pin/link:</label>
        <div class="input-container">
            <input type="text" id="pin-input" placeholder="Insira aqui o pin ou link">
            <button class="play-button" id="play-button">Jogar</button>
        </div>
        <p id="error-message" class="error-message" style="display: none;">Por favor, insira um PIN válido antes de continuar.</p>

        <div class="qr-code">
            <button class="camera-button" id="camera-button">Ativar Câmera</button>
            <video id="camera-stream" autoplay style="display: none;"></video>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Botão para ativar a câmera
            document.getElementById("camera-button").addEventListener("click", startCamera);

            // Botão para iniciar o jogo
            document.getElementById("play-button").addEventListener("click", redirectToNickname);
        });

        async function startCamera() {
            const video = document.getElementById("camera-stream");
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                video.style.display = "block";
            } catch (error) {
                alert("Erro ao acessar a câmera. Verifique se o navegador tem permissões.");
                console.error("Erro ao acessar a câmera:", error);
            }
        }

        function redirectToNickname() {
            const pinInput = document.getElementById("pin-input").value.trim();
            const errorMessage = document.getElementById("error-message");

            if (pinInput !== "") {
                console.log("PIN inserido:", pinInput); // DEBUG
                const encodedPin = encodeURIComponent(pinInput);
                window.location.href = `nickname.jsp?pin=${encodedPin}`;
            } else {
                errorMessage.style.display = "block";
                setTimeout(() => {
                    errorMessage.style.display = "none";
                }, 3000);
            }
        }
    </script>
</body>
</html>
