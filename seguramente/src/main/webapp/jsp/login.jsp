<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="java.sql.*" %>
<%@ page import="javax.servlet.http.HttpSession" %>

<%
    String email = request.getParameter("email");
    String password = request.getParameter("password");
    boolean loginSuccess = false;
    String nomeUsuario = "";
    int idUsuario = 0;

    if (email != null && password != null) {
        Connection conn = null;
        PreparedStatement stmt = null;
        ResultSet rs = null;

        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
            conn = DriverManager.getConnection("jdbc:mysql://localhost:3306/segura_utilizadores", "root", "!5xne5Qui8900");

            String query = "SELECT ID_Utilizador, Nome, Tipo_de_Utilizador FROM Utilizador WHERE Email = ? AND Password = ?";
            stmt = conn.prepareStatement(query);
            stmt.setString(1, email);
            stmt.setString(2, password);
            rs = stmt.executeQuery();

            if (rs.next()) {
                idUsuario = rs.getInt("ID_Utilizador");
                nomeUsuario = rs.getString("Nome");
                String tipoUsuario = rs.getString("Tipo_de_Utilizador");
                loginSuccess = true;

                HttpSession userSession = request.getSession();
                userSession.setAttribute("idUsuario", idUsuario);
                userSession.setAttribute("nomeUsuario", nomeUsuario);
                if ("Admin".equals(tipoUsuario)) {
                    response.sendRedirect("admin_aprovar_quizz.jsp");
                } else {
                    response.sendRedirect("dashboard.jsp");
                }
            }
        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            try { if (rs != null) rs.close(); if (stmt != null) stmt.close(); if (conn != null) conn.close(); } catch (Exception e) { e.printStackTrace(); }
        }
    }
%>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SeguraMente</title>
    <link rel="stylesheet" href="html/css/login.css">
</head>
<body>
    <div class="container">
        <div class="left"></div>
        <div class="right">
            <div class="site-title">
                <a href="../index.html" class="site-title-link">SeguraMente</a>
            </div>
            <h1>Entra na tua conta</h1>
            <% if (email != null && !loginSuccess) { %>
                <p style="color: red;">Email ou palavra-passe incorretos.</p>
            <% } %>
            <form method="post" action="login.jsp">
                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" id="email" name="email" placeholder="Insere o teu email" required>
                </div>
                <div class="form-group">
                    <label for="password">Palavra-passe*</label>
                    <input type="password" id="password" name="password" placeholder="Palavra-passe" required>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="remember">
                    <label for="remember">Lembrar a minha palavra-passe</label>
                </div>
                <button type="submit" class="btn-primary">Entrar</button>
                <div class="or-text">Ou</div>
                <button type="button" class="btn-secondary" onclick="window.location.href='register.jsp'">Registar</button>
            </form>
            <a href="forgot_password.jsp" class="forgot-password">Esqueceu da palavra-passe</a>
        </div>
    </div>
</body>
</html>
