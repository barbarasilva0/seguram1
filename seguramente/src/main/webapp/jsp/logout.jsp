<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>
<%@ page import="javax.servlet.http.HttpSession" %>

<%
    // Invalida a sessão do usuário
    HttpSession sessao = request.getSession(false); // Evita criar uma nova sessão se não existir

    if (sessao != null) {
        sessao.invalidate(); // Encerra a sessão
    }

    // Redireciona para a página inicial
    response.sendRedirect("index.jsp");
%>
