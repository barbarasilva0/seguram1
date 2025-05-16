<?php
session_start();

// Verificar se o valor da pontuação foi enviado
if (!isset($_POST['pontuacao']) || !is_numeric($_POST['pontuacao'])) {
    http_response_code(400); // Bad Request
    echo "Pontuação inválida.";
    exit;
}

// Guardar pontuação na sessão
$_SESSION['pontuacaoSolo'] = intval($_POST['pontuacao']);

// Resposta de sucesso
http_response_code(200);
echo "Pontuação registada com sucesso.";
?>
