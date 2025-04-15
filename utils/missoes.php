<?php

/**
 * Atribui as missões semanais padrão a um novo jogador.
 *
 * @param int $idUsuario
 * @param mysqli $connJogos
 * @return void
 */
function gerarMissoesIniciais($idUsuario, $connJogos) {
    $missoes = [
        ["Jogue 3 quizzes esta semana", 0, 3],
        ["Obtenha 80% de acertos em um quiz", 0, 1],
        ["Participe de um jogo multiplayer", 0, 1],
        ["Criar um quiz", 0, 1]
    ];

    $stmt = $connJogos->prepare(
        "INSERT INTO Missao_Semanal (ID_Utilizador, Nome, Progresso, Objetivo) VALUES (?, ?, ?, ?)"
    );

    foreach ($missoes as $missao) {
        list($nome, $progresso, $objetivo) = $missao;
        $stmt->bind_param("isii", $idUsuario, $nome, $progresso, $objetivo);
        $stmt->execute();
    }

    $stmt->close();
}

/**
 * Verifica se o utilizador já tem missões atribuídas.
 *
 * @param int $idUsuario
 * @param mysqli $connJogos
 * @return bool
 */
function verificarMissoesExistentes($idUsuario, $connJogos) {
    $stmt = $connJogos->prepare("SELECT COUNT(*) FROM Missao_Semanal WHERE ID_Utilizador = ?");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count > 0;
}

/**
 * Atualiza as missões semanais de todos os jogadores que participaram num quiz.
 *
 * @param int $pin
 * @param mysqli $connJogos
 * @return void
 */
function atualizarMissoesTodosJogadores($pin, $connJogos) {
    if (!$pin) return;

    // Verificar se foi multiplayer
    $modo = 'solo';
    $stmt = $connJogos->prepare("SELECT COUNT(*) FROM Lobby WHERE PIN = ?");
    $stmt->bind_param("i", $pin);
    $stmt->execute();
    $stmt->bind_result($temLobby);
    $stmt->fetch();
    $stmt->close();

    if ($temLobby > 0) $modo = 'multi';

    // Obter todos os jogadores com ID_Utilizador
    $stmt = $connJogos->prepare("
        SELECT DISTINCT ID_Utilizador 
        FROM RespostasJogador 
        WHERE PIN = ? AND ID_Utilizador IS NOT NULL
    ");
    $stmt->bind_param("i", $pin);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $idUtilizador = $row['ID_Utilizador'];

        // Total de respostas e acertos do jogador
        $s = $connJogos->prepare("
            SELECT COUNT(*) as total, SUM(Correta) as acertos 
            FROM RespostasJogador 
            WHERE PIN = ? AND ID_Utilizador = ?
        ");
        $s->bind_param("ii", $pin, $idUtilizador);
        $s->execute();
        $s->bind_result($totalPerguntas, $totalAcertos);
        $s->fetch();
        $s->close();

        $percentagem = $totalPerguntas > 0 ? ($totalAcertos / $totalPerguntas) * 100 : 0;

        // Atualizar missões desse jogador
        $m = $connJogos->prepare("
            SELECT ID_Missao, Nome, Objetivo, Progresso 
            FROM Missao_Semanal 
            WHERE ID_Utilizador = ?
        ");
        $m->bind_param("i", $idUtilizador);
        $m->execute();
        $resultMissoes = $m->get_result();

        while ($missao = $resultMissoes->fetch_assoc()) {
            $idMissao = $missao['ID_Missao'];
            $objetivo = $missao['Objetivo'];
            $progresso = $missao['Progresso'];
            $nome = strtolower($missao['Nome']);

            if ($progresso >= $objetivo) continue;

            if (strpos($nome, 'jogue') !== false) {
                $u = $connJogos->prepare("UPDATE Missao_Semanal SET Progresso = Progresso + 1 WHERE ID_Missao = ?");
                $u->bind_param("i", $idMissao);
                $u->execute();
                $u->close();
            }

            if (strpos($nome, '80%') !== false && $percentagem >= 80) {
                $u = $connJogos->prepare("UPDATE Missao_Semanal SET Progresso = 1 WHERE ID_Missao = ?");
                $u->bind_param("i", $idMissao);
                $u->execute();
                $u->close();
            }

            if (strpos($nome, 'multiplayer') !== false && $modo === 'multi') {
                $u = $connJogos->prepare("UPDATE Missao_Semanal SET Progresso = 1 WHERE ID_Missao = ?");
                $u->bind_param("i", $idMissao);
                $u->execute();
                $u->close();
            }
        }

        $m->close();
    }

    $stmt->close();
}
?>