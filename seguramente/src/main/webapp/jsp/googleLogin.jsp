<%@ page contentType="application/json; charset=UTF-8" %>
<%@ page import="java.sql.*, java.io.*, org.json.JSONObject" %>
<%@ page import="com.google.api.client.googleapis.auth.oauth2.GoogleIdToken" %>
<%@ page import="com.google.api.client.googleapis.auth.oauth2.GoogleIdTokenVerifier" %>
<%@ page import="com.google.api.client.http.javanet.NetHttpTransport" %>
<%@ page import="com.google.api.client.json.jackson2.JacksonFactory" %>

<%
    String clientId = "SEU_CLIENT_ID"; // Substitua pelo Client ID do Google
    String credential = request.getParameter("credential");
    JSONObject jsonResponse = new JSONObject();

    if (credential != null) {
        try {
            GoogleIdTokenVerifier verifier = new GoogleIdTokenVerifier.Builder(
                new NetHttpTransport(), JacksonFactory.getDefaultInstance())
                .setAudience(java.util.Collections.singletonList(clientId))
                .build();

            GoogleIdToken idToken = verifier.verify(credential);

            if (idToken != null) {
                GoogleIdToken.Payload payload = idToken.getPayload();
                String email = payload.getEmail();
                String name = (String) payload.get("name");

                Connection conn = null;
                PreparedStatement stmt = null;
                ResultSet rs = null;

                try {
                    Class.forName("com.mysql.cj.jdbc.Driver");
                    conn = DriverManager.getConnection("jdbc:mysql://localhost:3306/segura_utilizadores", "root", "!5xne5Qui8900");

                    // Verificar se o email já está registrado
                    String checkQuery = "SELECT * FROM Utilizador WHERE Email = ?";
                    stmt = conn.prepareStatement(checkQuery);
                    stmt.setString(1, email);
                    rs = stmt.executeQuery();

                    if (!rs.next()) {
                        // Inserir novo usuário
                        String insertQuery = "INSERT INTO Utilizador (Nome, Email, Password, Tipo_de_Utilizador) VALUES (?, ?, '', 'Jogador')";
                        stmt = conn.prepareStatement(insertQuery);
                        stmt.setString(1, name);
                        stmt.setString(2, email);
                        stmt.executeUpdate();
                    }

                    jsonResponse.put("success", true);
                } catch (Exception e) {
                    e.printStackTrace();
                    jsonResponse.put("success", false);
                } finally {
                    if (rs != null) rs.close();
                    if (stmt != null) stmt.close();
                    if (conn != null) conn.close();
                }
            } else {
                jsonResponse.put("success", false);
            }
        } catch (Exception e) {
            e.printStackTrace();
            jsonResponse.put("success", false);
        }
    } else {
        jsonResponse.put("success", false);
    }

    out.print(jsonResponse.toString());
%>
