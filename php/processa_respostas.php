<?php
session_start();
require_once '/home/seguram1/config.php';

$connJogos = getDBJogos();
$connJogos->set_charset("utf8mb4");

$idUsuario = isset($_SESSION['idUsuario']) ? intval($_SESSION['idUsuario']) : 0;
$nickname = $_SESSION['username'] ?? $_SESSION['nomeUsuario'] ?? 'Convidado';
$idJogo = isset($_POST['idJogo']) ? intval($_POST['idJogo']) : 0;
$modoJogo = $_POST['modo'] ?? 'solo';
$pin = isset($_POST['pin']) ? intval($_POST['pin']) : null;

$respostasUsuario = $_POST['resposta'] ?? [];
$temposResposta = $_POST['tempo'] ?? [];

if ($idJogo === 0 || empty($respostasUsuario)) {
    die("Informações incompletas.");
}

$pontuacaoTotal = 0;

foreach ($respostasUsuario as $idPergunta => $respostaDada) {
    $query = "SELECT Resposta_Correta, Pontos FROM Pergunta WHERE ID_Pergunta = ?";
    $stmt = $connJogos->prepare($query);
    $stmt->bind_param("i", $idPergunta);
    $stmt->execute();
    $stmt->bind_result($respostaCorreta, $pontos);

    $correta = 0;
    $ganhos = 0;

    if ($stmt->fetch()) {
        if ($respostaDada === $respostaCorreta) {
            $tempoResposta = isset($temposResposta[$idPergunta]) ? floatval($temposResposta[$idPergunta]) : 60;
            $bonusTempo = max(0.5, (60 - $tempoResposta) / 60); // Mínimo 50%
            $ganhos = round($pontos * $bonusTempo);
            $pontuacaoTotal += $ganhos;
            $correta = 1;
        }
    }
    $stmt->close();

    if ($modoJogo === 'multi' && $pin) {
        $tempoResposta = isset($temposResposta[$idPergunta]) ? floatval($temposResposta[$idPergunta]) : null;

        $insert = $connJogos->prepare("INSERT INTO RespostasJogador (PIN, ID_Utilizador, Nickname, ID_Pergunta, Resposta_Dada, Correta, Tempo_Resposta, Pontos_Obtidos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param("iisisdid", $pin, $idUsuario, $nickname, $idPergunta, $respostaDada, $correta, $tempoResposta, $ganhos);
        $insert->execute();
        $insert->close();
    }
}

if ($idUsuario > 0) {
    // Gravar no histórico (modo solo ou multi)
    $stmt = $connJogos->prepare("INSERT INTO Historico (Data, Pontuacao_Obtida, Estado, ID_Utilizador, ID_Jogo) VALUES (CURDATE(), ?, 'Concluído', ?, ?)");
    $stmt->bind_param("iii", $pontuacaoTotal, $idUsuario, $idJogo);
    $stmt->execute();
    $stmt->close();

    // Só no modo multiplayer é que atualiza a pontuação total
    if ($modoJogo === 'multi') {
        $connUtilizadores = getDBUtilizadores();
        $connUtilizadores->set_charset("utf8mb4");

        $updatePerfil = $connUtilizadores->prepare("UPDATE Perfil SET Pontuacao_Total = Pontuacao_Total + ? WHERE ID_Utilizador = ?");
        $updatePerfil->bind_param("ii", $pontuacaoTotal, $idUsuario);
        $updatePerfil->execute();
        $updatePerfil->close();

        $connUtilizadores->close();
    }
}

$connJogos->close();

if ($modoJogo === 'multi' && $pin) {
    header("Location: ranking_final.php?pin=$pin");
    exit();
} else {
    $_SESSION['quizFeedback'] = "Concluíste o quiz com $pontuacaoTotal pontos!";
    header("Location: historico.php");
    exit();
}
?>
