<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

// Configuração do banco de dados
$servername = "localhost";
$username = "seguram1_seguram1"; 
$password_db = "d@V+38y3nWb4FB"; 
$dbJogos = "seguram1_segura_jogos";

// Conectar ao banco de dados de jogos
$connJogos = new mysqli($servername, $username, $password_db, $dbJogos);
if ($connJogos->connect_error) {
    die("Erro na conexão com o banco de dados: " . $connJogos->connect_error);
}

// Definir UTF-8 para evitar problemas com acentos
$connJogos->set_charset("utf8mb4");

// Obter dados do usuário autenticado
$idUsuario = $_SESSION['idUsuario'];
$nomeUsuario = $_SESSION['nomeUsuario'] ?? "Visitante";

// Configuração de paginação
$itensPorPagina = isset($_GET['itens']) ? (int)$_GET['itens'] : 10;
$paginaAtual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Contar número total de registros para paginação
$stmtTotal = $connJogos->prepare("SELECT COUNT(*) FROM Historico WHERE ID_Utilizador = ?");
$stmtTotal->bind_param("i", $idUsuario);
$stmtTotal->execute();
$stmtTotal->bind_result($totalRegistros);
$stmtTotal->fetch();
$stmtTotal->close();
$totalPaginas = max(1, ceil($totalRegistros / $itensPorPagina));

// Contar número de quizzes concluídos
$stmt = $connJogos->prepare("SELECT COUNT(*) FROM Historico WHERE ID_Utilizador = ? AND Estado = 'Concluído'");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($quizzesCompletados);
$stmt->fetch();
$stmt->close();

// Buscar histórico de quizzes jogados com paginação
$historico = [];
$stmt = $connJogos->prepare("
    SELECT h.ID_Historico, j.Nome AS quizName, h.Data, h.Pontuacao_Obtida, 
           (SELECT COUNT(*) FROM Pergunta WHERE ID_Jogo = j.ID_Jogo) AS totalPerguntas 
    FROM Historico h 
    JOIN Jogo j ON h.ID_Jogo = j.ID_Jogo 
    WHERE h.ID_Utilizador = ? 
    ORDER BY h.Data DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $idUsuario, $itensPorPagina, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $historico[] = $row;
}
$stmt->close();

// Fechar conexão
$connJogos->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - SeguraMente</title>
    <link rel="stylesheet" href="../css/historico.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h1>SeguraMente</h1>
            <a href="dashboard.php" class="sidebar-item">
                <img src="../imagens/jogos_disponiveis_icon.png" alt="Jogos Disponíveis" style="width: 20px; height: 20px;">
                Jogos Disponíveis
            </a>
            <a href="missoes_semanais.php" class="sidebar-item">
                <img src="../imagens/missoes_icon.png" alt="Missões Semanais" style="width: 20px; height: 20px;">
                Missões Semanais
            </a>
            <a href="historico.php" class="sidebar-item">
                <img src="../imagens/historico_icon.png" alt="Histórico" style="width: 20px; height: 20px;">
                Histórico
            </a>
            <a href="logout.php" class="sidebar-item" id="logout">
                <img src="../imagens/logout_icon.png" alt="Sair" style="width: 20px; height: 20px;">
                Sair
            </a>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Header -->
            <div class="header">
                <div class="search-container">
                    <img src="../imagens/lupa.png" alt="Lupa" class="search-icon">
                    <input type="text" placeholder="Pesquisar...">
                </div>
                <a href="criar_quizz.php" class="create-quiz">Criar Quizz</a>
                <a href="perfil.php" class="profile">
                    <img src="../imagens/avatar.png" alt="Avatar">
                    <span><?php echo htmlspecialchars(mb_convert_encoding($_SESSION['nomeUsuario'], 'UTF-8', 'ISO-8859-1')); ?></span>
                </a>
            </div>
            
            <!-- Quiz Section -->
            <div class="quiz-section">
                <div class="quiz-header">
                    <div class="user-info">
                        <img src="../imagens/avatar.png" alt="Avatar do usuário" class="user-avatar">
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars(mb_convert_encoding($_SESSION['nomeUsuario'], 'UTF-8', 'ISO-8859-1')); ?></span>
                            <div class="user-score-container">
                                <img src="../imagens/flag_icon.png" alt="Flag Icon" class="flag-icon">
                                <div class="user-score">
                                    <span class="score-value"><?php echo $quizzesCompletados; ?></span>
                                    <span class="score-text">Quiz Passed</span>
                                </div>
                            </div>
                        </div>                                             
                    </div>
                </div>
                

                <h2>Histórico</h2>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Quizz</th>
                                <th>Data</th>
                                <th>Pontuação</th>
                                <th>Perguntas Totais</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($historico)): ?>
                                <?php foreach ($historico as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row["quizName"]); ?></td>
                                        <td><?php echo date("d/m/Y", strtotime($row["Data"])); ?></td>
                                        <td><?php echo htmlspecialchars($row["Pontuacao_Obtida"]); ?></td>
                                        <td><?php echo htmlspecialchars($row["totalPerguntas"]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4">Nenhum histórico encontrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
    
                <!-- Paginação -->
                <div class="pagination">
                    <form method="get">
                        <label for="itens">Itens por página:</label>
                        <select name="itens" id="itens" onchange="this.form.submit()">
                            <option value="10" <?php if ($itensPorPagina == 10) echo 'selected'; ?>>10</option>
                            <option value="20" <?php if ($itensPorPagina == 20) echo 'selected'; ?>>20</option>
                        </select>
                    </form>
    
                    <div class="page-controls">
                        <?php if ($paginaAtual > 1): ?>
                            <a href="?pagina=<?php echo ($paginaAtual - 1); ?>&itens=<?php echo $itensPorPagina; ?>">❮ Anterior</a>
                        <?php endif; ?>
                        <span>Página <?php echo $paginaAtual; ?> de <?php echo $totalPaginas; ?></span>
                        <?php if ($paginaAtual < $totalPaginas): ?>
                            <a href="?pagina=<?php echo ($paginaAtual + 1); ?>&itens=<?php echo $itensPorPagina; ?>">Próxima ❯</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <!-- Modal de Logout -->
    <div class="modal-overlay" id="modal">
        <div class="modal-content">
            <h2>Tem certeza que deseja sair?</h2>
            <div class="modal-buttons">
                <button class="btn-yes" id="confirmYes">Sim</button>
                <button class="btn-no" id="confirmNo">Não</button>
            </div>
        </div>
    </div>

    <script>
        const logoutLink = document.getElementById('logout');
        const modal = document.getElementById('modal');
        const confirmYes = document.getElementById('confirmYes');
        const confirmNo = document.getElementById('confirmNo');

        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.add('show');
        });

        confirmYes.addEventListener('click', () => {
            window.location.href = 'logout.php';
        });

        confirmNo.addEventListener('click', () => {
            modal.classList.remove('show');
        });
    </script>
</body>
</html>
