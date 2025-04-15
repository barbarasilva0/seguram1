<?php
session_start();
require_once '/home/seguram1/config.php';

if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['tipoUsuario']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$connJogos = getDBJogos();
$connJogos->set_charset("utf8mb4");

if (!isset($_GET['idPergunta']) || !isset($_GET['idJogo'])) {
    header("Location: admin_aprovar_quizz.php");
    exit();
}

$idPergunta = intval($_GET['idPergunta']);
$idJogo = intval($_GET['idJogo']);

// Buscar dados da pergunta
$stmt = $connJogos->prepare("SELECT Texto, Imagem, Opcoes, Resposta_Correta, Pontos FROM Pergunta WHERE ID_Pergunta = ?");
$stmt->bind_param("i", $idPergunta);
$stmt->execute();
$result = $stmt->get_result();
$pergunta = $result->fetch_assoc();
$stmt->close();

// Separar opções em array para exibir nos inputs
$opcoesArray = explode(', ', $pergunta['Opcoes']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texto = $_POST['texto'];
    $opcoes = $_POST['opcoes']; // array com as 4 opções
    $resposta = $_POST['resposta'];
    $pontos = intval($_POST['pontos']);

    // Juntar opções em string separada por vírgula
    $opcoesString = implode(', ', array_map('trim', $opcoes));

    // Upload da imagem se existir
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '/var/www/html/uploads/';
        $filename = basename($_FILES['imagem']['name']);
        $targetFile = $uploadDir . $filename;
        move_uploaded_file($_FILES['imagem']['tmp_name'], $targetFile);
        $imagem = $filename;

        $stmtUpdate = $connJogos->prepare("UPDATE Pergunta SET Texto = ?, Opcoes = ?, Resposta_Correta = ?, Pontos = ?, Imagem = ? WHERE ID_Pergunta = ?");
        $stmtUpdate->bind_param("sssisi", $texto, $opcoesString, $resposta, $pontos, $imagem, $idPergunta);
    } else {
        $stmtUpdate = $connJogos->prepare("UPDATE Pergunta SET Texto = ?, Opcoes = ?, Resposta_Correta = ?, Pontos = ? WHERE ID_Pergunta = ?");
        $stmtUpdate->bind_param("sssii", $texto, $opcoesString, $resposta, $pontos, $idPergunta);
    }

    $stmtUpdate->execute();
    $stmtUpdate->close();

    header("Location: admin_ver_quizz.php?id=$idJogo");
    exit();
}

$connJogos->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Editar Pergunta</title>
    <link rel="stylesheet" href="../css/admin_editar_pergunta.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
</head>
<body>
    <div class="container">
        <h1>Editar Pergunta</h1>
        <form method="POST" enctype="multipart/form-data" class="form" id="formPergunta">
        <label>Texto da Pergunta:</label>
        <textarea name="texto" rows="4" required><?php echo htmlspecialchars($pergunta['Texto']); ?></textarea>
    
        <?php for ($i = 0; $i < 4; $i++): ?>
            <label>Opção <?php echo $i + 1; ?>:</label>
            <input type="text" class="opcao" name="opcoes[]" value="<?php echo htmlspecialchars($opcoesArray[$i] ?? ''); ?>" required>
        <?php endfor; ?>
    
        <label>Resposta Correta:</label>
        <select name="resposta" id="resposta" required>
            <?php foreach ($opcoesArray as $op): ?>
                <option value="<?php echo htmlspecialchars($op); ?>" <?php if ($op === $pergunta['Resposta_Correta']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($op); ?>
                </option>
            <?php endforeach; ?>
        </select>
    
        <label>Pontos:</label>
        <input type="number" name="pontos" value="<?php echo intval($pergunta['Pontos']); ?>" required>
    
        <label>Imagem da Pergunta (opcional):</label>
        <?php if (!empty($pergunta['Imagem'])): ?>
            <p>Imagem Atual:</p>
            <img src="../../uploads/<?php echo htmlspecialchars($pergunta['Imagem']); ?>" width="200px">
        <?php endif; ?>
        <input type="file" name="imagem" accept="image/*">
    
        <button type="submit">Guardar Alterações</button>
    </form>
    <a href="admin_ver_quizz.php?id=<?php echo $idJogo; ?>" class="back-link">Voltar</a>
    
    <script>
    // Script para atualizar o dropdown das respostas
    const inputsOpcoes = document.querySelectorAll('.opcao');
    const selectResposta = document.getElementById('resposta');
    
    // Função que atualiza as opções do select
    function atualizarDropdown() {
        const valores = Array.from(inputsOpcoes).map(input => input.value.trim());
    
        // Limpar dropdown
        selectResposta.innerHTML = '';
    
        // Adicionar novas opções
        valores.forEach(opcao => {
            const opt = document.createElement('option');
            opt.value = opcao;
            opt.textContent = opcao;
            selectResposta.appendChild(opt);
        });
    }
    
    // Atualiza dropdown sempre que as opções mudam
    inputsOpcoes.forEach(input => {
        input.addEventListener('input', atualizarDropdown);
    });
    
    // Atualiza ao carregar a página
    window.addEventListener('DOMContentLoaded', atualizarDropdown);
    </script>

</body>
</html>
