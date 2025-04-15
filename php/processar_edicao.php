<?php
session_start();
require_once '/home/seguram1/config.php';

if (!isset($_SESSION['idUsuario']) || !isset($_GET['id'])) {
    header("Location: quizzes_criados.php");
    exit();
}

$idUsuario = intval($_SESSION['idUsuario']);
$idJogo = intval($_GET['id']);
$conn = getDBJogos();
$conn->set_charset("utf8mb4");

function validarImagem($tmpPath, $maxSize = 10000000) {
    $mime = mime_content_type($tmpPath);
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return in_array($mime, $tiposPermitidos) && filesize($tmpPath) <= $maxSize;
}

function guardarImagem($fileArray, $destino = '../../uploads/'): ?string {
    $tmp = $fileArray['tmp_name'];
    if (!validarImagem($tmp)) return null;

    $extensao = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
    $nomeUnico = uniqid('img_', true) . '.' . strtolower($extensao);
    $caminhoFinal = $destino . $nomeUnico;

    return move_uploaded_file($tmp, $caminhoFinal) ? $nomeUnico : null;
}

// 1. Atualizar nome e descrição do quiz
if (isset($_POST['nome_quizz'], $_POST['descricao_quizz'])) {
    $nome = trim($_POST['nome_quizz']);
    $descricao = trim($_POST['descricao_quizz']);

    $stmt = $conn->prepare("UPDATE Jogo SET Nome = ?, Descricao = ?, Estado = 'Pendente' WHERE ID_Jogo = ? AND Criado_por = ?");
    $stmt->bind_param("ssii", $nome, $descricao, $idJogo, $idUsuario);
    $stmt->execute();
    $stmt->close();
}

// 2. Eliminar perguntas removidas
if (!empty($_POST['perguntas'])) {
    $idsEnviados = array_map('intval', array_keys($_POST['perguntas']));

    $stmt = $conn->prepare("SELECT ID_Pergunta FROM Pergunta WHERE ID_Jogo = ?");
    $stmt->bind_param("i", $idJogo);
    $stmt->execute();
    $result = $stmt->get_result();

    $idsExistentes = [];
    while ($row = $result->fetch_assoc()) {
        $idsExistentes[] = intval($row['ID_Pergunta']);
    }
    $stmt->close();

    $idsParaEliminar = array_diff($idsExistentes, $idsEnviados);
    if (!empty($idsParaEliminar)) {
        $in = implode(',', array_fill(0, count($idsParaEliminar), '?'));
        $types = str_repeat('i', count($idsParaEliminar));
        $stmt = $conn->prepare("DELETE FROM Pergunta WHERE ID_Jogo = ? AND ID_Pergunta IN ($in)");
        $params = array_merge([$idJogo], $idsParaEliminar);
        $stmt->bind_param("i" . $types, ...$params);
        $stmt->execute();
        $stmt->close();
    }
}

// 3. Atualizar perguntas existentes
if (!empty($_POST['perguntas'])) {
    foreach ($_POST['perguntas'] as $idPergunta => $dados) {
        $texto = trim($dados['texto']);
        $pontos = intval($dados['pontos']);
        $opcoes = array_map('trim', $dados['opcoes']);
        $respostaLetra = $dados['correta'];
        $letraIndex = ord(strtoupper($respostaLetra)) - 65;
        $respostaCorreta = $opcoes[$letraIndex] ?? '';
        $imagem = "";

        if (isset($_FILES['perguntas']['error'][$idPergunta]['imagem']) && $_FILES['perguntas']['error'][$idPergunta]['imagem'] === 0) {
            $imagemGuardada = guardarImagem([
                'tmp_name' => $_FILES['perguntas']['tmp_name'][$idPergunta]['imagem'],
                'name' => $_FILES['perguntas']['name'][$idPergunta]['imagem']
            ]);
            if ($imagemGuardada) $imagem = $imagemGuardada;
        } else {
            $stmt = $conn->prepare("SELECT Imagem FROM Pergunta WHERE ID_Pergunta = ? AND ID_Jogo = ?");
            $stmt->bind_param("ii", $idPergunta, $idJogo);
            $stmt->execute();
            $stmt->bind_result($imagem);
            $stmt->fetch();
            $stmt->close();
        }

        $opcoesStr = implode(', ', $opcoes);
        $stmt = $conn->prepare("UPDATE Pergunta SET Texto = ?, Imagem = ?, Opcoes = ?, Resposta_Correta = ?, Pontos = ? WHERE ID_Pergunta = ? AND ID_Jogo = ?");
        $stmt->bind_param("ssssiii", $texto, $imagem, $opcoesStr, $respostaCorreta, $pontos, $idPergunta, $idJogo);
        $stmt->execute();
        $stmt->close();
    }
}

// 4. Inserir novas perguntas
if (!empty($_POST['novas'])) {
    foreach ($_POST['novas'] as $idx => $dados) {
        $texto = trim($dados['texto']);
        $pontos = intval($dados['pontos']);
        $opcoes = array_map('trim', $dados['opcoes']);
        $respostaLetra = $dados['correta'];
        $respostaCorreta = $opcoes[ord(strtoupper($respostaLetra)) - 65] ?? '';
        $imagem = "";

        if (isset($_FILES['novas']['error'][$idx]['imagem']) && $_FILES['novas']['error'][$idx]['imagem'] === 0) {
            $imagemGuardada = guardarImagem([
                'tmp_name' => $_FILES['novas']['tmp_name'][$idx]['imagem'],
                'name' => $_FILES['novas']['name'][$idx]['imagem']
            ]);
            if ($imagemGuardada) $imagem = $imagemGuardada;
        }

        $opcoesStr = implode(', ', $opcoes);
        $stmt = $conn->prepare("INSERT INTO Pergunta (Texto, Imagem, Opcoes, Resposta_Correta, Pontos, ID_Jogo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $texto, $imagem, $opcoesStr, $respostaCorreta, $pontos, $idJogo);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();
header("Location: aprovar_jogo.php");
exit();