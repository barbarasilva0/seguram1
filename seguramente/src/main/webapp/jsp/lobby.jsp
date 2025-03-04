<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="java.sql.*" %>
<%@ page import="javax.servlet.http.HttpSession" %>

<%
    HttpSession sessao = request.getSession();
    String nomeUsuario = (String) sessao.getAttribute("nomeUsuario");
    boolean usuarioLogado = (nomeUsuario != null);

    String pin = request.getParameter("pin");
    String idJogo = request.getParameter("id");
    String nickname = request.getParameter("nickname");

    boolean pinValido = false;
    String tituloQuiz = "Quiz Desconhecido";

    Connection conn = null;
    PreparedStatement stmt = null;
    ResultSet rs = null;

    try {
        Class.forName("com.mysql.cj.jdbc.Driver");
        conn = DriverManager.getConnection("jdbc:mysql://localhost:3306/segura_jogos", "root", "!5xne5Qui8900");

        // 🔹 Verifica se o PIN existe na base de dados
        String checkPinQuery = "SELECT J.Nome FROM Lobby L INNER JOIN Jogo J ON L.ID_Jogo = J.ID_Jogo WHERE L.PIN = ?";
        stmt = conn.prepareStatement(checkPinQuery);
        stmt.setString(1, pin);
        rs = stmt.executeQuery();

        if (rs.next()) {
            pinValido = true;
            tituloQuiz = rs.getString("Nome");
        }
        rs.close();
        stmt.close();

        // 🔹 Se for um jogador temporário, adiciona ao lobby
        if (nickname != null && !nickname.isEmpty() && pinValido) {
            String insertPlayerQuery = "INSERT INTO JogadoresLobby (PIN, Nickname) VALUES (?, ?)";
            stmt = conn.prepareStatement(insertPlayerQuery);
            stmt.setString(1, pin);
            stmt.setString(2, nickname);
            stmt.executeUpdate();
            stmt.close();
        }
    } catch (Exception e) {
        e.printStackTrace();
    } finally {
        try {
            if (stmt != null) stmt.close();
            if (conn != null) conn.close();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

%>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby do Quiz - <%= tituloQuiz %></title>
    <link rel="stylesheet" href="html/css/lobby.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <header>
        <a href="dashboard.jsp" class="site-title">SeguraMente</a>
    </header>

    <div class="container">
        <h1><%= tituloQuiz %></h1>

        <div class="pin-qrcode-container">
            <div class="pin-box">
                <span>PIN:</span>
                <div id="pin-display"><%= pin %></div>
            </div>
            <div id="qrcode"></div>
        </div>

        <button class="start-button" onclick="startQuiz()">Iniciar Quiz</button>

        <h3>Jogadores no Lobby</h3>
        <div class="players-container" id="players-container"></div>
    </div>

    <script>
        const pinLobby = "<%= pin %>"; 
        const idJogo = "<%= idJogo %>"; 

        function startQuiz() {
            window.location.href = `quizz.jsp?id=<%= idJogo %>&pin=<%= pin %>`;
        }

        function generateQRCode() {
            new QRCode(document.getElementById("qrcode"), {
                text: window.location.origin + "/jsp/lobby.jsp?id=" + idJogo + "&pin=" + pinLobby,
                width: 150,
                height: 150
            });
        }

        generateQRCode();
    </script>
</body>
</html>
