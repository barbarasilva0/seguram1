<?php
require_once '/home/seguram1/config.php';

$logPath = "/home/seguram1/cron_logs/reset_debug.log";
file_put_contents($logPath, "Início do reset de missões semanais\n", FILE_APPEND);

// Conexões separadas para cada base de dados
$connUtilizadores = getDBUtilizadores();
$connJogos = getDBJogos();

if (!$connUtilizadores || !$connJogos) {
    file_put_contents($logPath, "Erro: não foi possível conectar às bases de dados.\n", FILE_APPEND);
    exit;
}

// Buscar todos os utilizadores registados
$res = $connUtilizadores->query("SELECT ID_Utilizador FROM Utilizador");
if (!$res) {
    file_put_contents($logPath, "Erro ao obter utilizadores: " . $connUtilizadores->error . "\n", FILE_APPEND);
    exit;
}

while ($row = $res->fetch_assoc()) {
    $id = (int)$row['ID_Utilizador'];
    file_put_contents($logPath, "Resetando missões do utilizador ID=$id\n", FILE_APPEND);

    // Apagar missões antigas
    $delete = $connJogos->prepare("DELETE FROM Missao_Semanal WHERE ID_Utilizador = ?");
    if ($delete) {
        $delete->bind_param("i", $id);
        if (!$delete->execute()) {
            file_put_contents($logPath, "Erro ao apagar missões do utilizador $id: " . $delete->error . "\n", FILE_APPEND);
        }
        $delete->close();
    } else {
        file_put_contents($logPath, "Erro ao preparar DELETE para $id: " . $connJogos->error . "\n", FILE_APPEND);
        continue;
    }

    // Inserir missões padrão
    $missoes = [
        ['Jogue 3 quizzes esta semana', null, 3],
        ['Obtenha 80% de acertos em um quiz', null, 1],
        ['Participe de um jogo multiplayer', null, 1],
        ['Criar um quiz', null, 1]
    ];

    $insert = $connJogos->prepare("INSERT INTO Missao_Semanal (Nome, Descricao, Objetivo, Progresso, ID_Utilizador) VALUES (?, ?, ?, 0, ?)");
    if (!$insert) {
        file_put_contents($logPath, "Erro ao preparar INSERT: " . $connJogos->error . "\n", FILE_APPEND);
        continue;
    }

    foreach ($missoes as $m) {
        $insert->bind_param("ssii", $m[0], $m[1], $m[2], $id);
        if (!$insert->execute()) {
            file_put_contents($logPath, "Erro ao inserir missão para utilizador $id: " . $insert->error . "\n", FILE_APPEND);
        }
    }
    $insert->close();
}

$connUtilizadores->close();
$connJogos->close();

file_put_contents($logPath, "Fim do reset de missões semanais\n", FILE_APPEND);
