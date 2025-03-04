<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="java.sql.*" %>
<%@ page import="javax.servlet.http.HttpSession" %>

<%
    // Verificar sessão do usuário
    HttpSession sessao = request.getSession();
    String nomeUsuario = (String) sessao.getAttribute("nomeUsuario");
    boolean usuarioLogado = (nomeUsuario != null);

    // Obter o PIN da URL
    String pin = request.getParameter("pin");
    boolean pinValido = false;

    if (pin != null && !pin.isEmpty()) {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;

        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
            conn = DriverManager.getConnection("jdbc:mysql://localhost:3306/segura_jogos", "root", "!5xne5Qui8900");

            String checkQuery = "SELECT * FROM Lobby WHERE PIN = ?";
            stmt = conn.prepareStatement(checkQuery);
            stmt.setString(1, pin);
            rs = stmt.executeQuery();

            if (rs.next()) {
                pinValido = true;
            }

        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            try {
                if (rs != null) rs.close();
                if (stmt != null) stmt.close();
                if (conn != null) conn.close();
            } catch (Exception e) {
                e.printStackTrace();
            }
        }
    }

    // Se o PIN for inválido, redireciona para "jogar_agora.jsp"
    if (!pinValido) {
        response.sendRedirect("jogar_agora.jsp");
        return;
    }
%>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolher Nome Temporário</title>
    <link rel="stylesheet" href="html/css/nickname.css">
</head>
<body>

    <!-- Cabeçalho -->
    <header>
        <a href="index.jsp" class="site-title">SeguraMente</a>
        <% if (usuarioLogado) { %>
            <a href="dashboard.jsp" class="button-blue">Dashboard</a>
        <% } else { %>
            <a href="login.jsp" class="button-blue">Entrar</a>
        <% } %>
    </header>

    <div class="container">
        <h1>Escolher Nome Temporário</h1>
        <div id="pin-display" class="pin-display">PIN: <%= pin %></div>

        <label for="nickname">Insira o seu nome temporário:</label>
        <input type="text" id="nickname" placeholder="">
        <p id="error-message" class="error-message">Por favor, insira um nickname antes de continuar.</p>

        <button class="play-button" onclick="enterLobby()">Entrar no Lobby</button>
    </div>

    <script>
        function enterLobby() {
            const nickname = document.getElementById("nickname").value.trim();
            const errorMessage = document.getElementById("error-message");

            if (nickname) {
                window.location.href = "lobby.jsp?pin=" + encodeURIComponent("<%= pin %>") + "&nickname=" + encodeURIComponent(nickname);
            } else {
                errorMessage.style.display = 'block';
                setTimeout(() => { errorMessage.style.display = 'none'; }, 3000);
            }
        }
    </script>
</body>
</html>
