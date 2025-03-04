<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="javax.servlet.http.HttpSession" %>

<%
    HttpSession sessao = request.getSession();
    String nomeUsuario = (String) sessao.getAttribute("nomeUsuario");
    boolean usuarioLogado = (nomeUsuario != null);
%>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeguraMente</title>
    <link rel="stylesheet" href="html/css/index.css">
</head>
<body>
    <header>
        <h1>SeguraMente</h1>
        <nav>
            <% if (usuarioLogado) { %>
                <a href="dashboard.jsp" class="button-blue">Dashboard</a>
                <a href="logout.jsp" class="button-white">Sair</a>
            <% } else { %>
                <a href="login.jsp" class="button-blue">Entrar</a>
                <a href="jogar_agora.jsp" class="button-white">Jogar Agora</a>
            <% } %>
        </nav>
    </header>

    <div class="hero">
        <div class="text">
            <h2>SeguraMente</h2>
            <p>Joga, Aprende e Protege-te Online!</p>
            <p>Descobre a segurança digital de forma divertida e interativa.</p>
        </div>
        <div class="image">
            <img src="html/imagens/imagem1.png" alt="Imagem de um computador com segurança digital">
        </div>
    </div>

    <div class="game-themes">
        <h3>Temas dos jogos</h3>
        <div class="carousel">
            <span class="arrow left" onclick="prevSlide()">&#9664;</span>
            <img src="html/imagens/pishing.png" alt="Tema 1" class="active">
            <img src="html/imagens/tema2.png" alt="Tema 2">
            <img src="html/imagens/tema3.png" alt="Tema 3">
            <img src="html/imagens/tema4.png" alt="Tema 4">
            <span class="arrow right" onclick="nextSlide()">&#9654;</span>
        </div>
    </div>

    <div class="section">
        <div class="image">
            <img src="html/imagens/roubos.png" alt="Fraudes Online">
        </div>
        <div class="text">
            <h3>Reconhecer Fraudes Online</h3>
            <p>Joga desafios interativos para identificar emails suspeitos e mensagens fraudulentas.</p>
        </div>
    </div>

    <div class="section">
        <div class="text">
            <h3>Proteção de Palavras-Passe</h3>
            <p>Aprende como criar palavras-passe fortes e seguras para proteger as tuas contas online.</p>
        </div>
        <div class="image">
            <img src="html/imagens/password.png" alt="Proteção de Palavras-Passe">
        </div>
    </div>

    <footer>
        <p>&copy; 2025 SeguraMente. Todos os direitos reservados.</p>
    </footer>

    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel img');
        const totalSlides = slides.length;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === index) {
                    slide.classList.add('active');
                }
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(currentSlide);
        }

        // Auto-slide every 5 seconds
        setInterval(nextSlide, 5000);
    </script>
</body>
</html>
