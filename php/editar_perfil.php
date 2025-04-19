<?php
session_start();
require_once '/home/seguram1/config.php';
include '../components/user_info.php'; 

if (!isset($_SESSION['idUsuario'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBUtilizadores();
$idUsuario = intval($_SESSION['idUsuario']);
$conn->set_charset("utf8mb4");

$query = "SELECT Nome, Username, Email, Foto_Google FROM Utilizador WHERE ID_Utilizador = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($nome, $username, $email, $fotoGoogle);
$stmt->fetch();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $novoNome = trim($_POST['full-name']);
    $novoUsername = trim($_POST['username']);
    $novoEmail = trim($_POST['email']);

    $queryCheck = "SELECT ID_Utilizador FROM Utilizador WHERE (Username = ? OR Email = ?) AND ID_Utilizador != ?";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->bind_param("ssi", $novoUsername, $novoEmail, $idUsuario);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    
    if (!file_exists("../uploads/avatars")) {
    mkdir("../uploads/avatars", 0755, true);
    }

    if ($stmtCheck->num_rows > 0) {
        $erro = "Nome de usuário ou email já estão em uso.";
    } else {
        $novaFoto = $fotoGoogle;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $ext = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
            $nomeUnico = uniqid("avatar_") . '.' . $ext;
            $targetDir = "../uploads/avatars/";
            $targetFile = $targetDir . $nomeUnico;

            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile)) {
                    $novaFoto = $targetFile;
                }
            }
        }

        $queryUpdate = "UPDATE Utilizador SET Nome = ?, Username = ?, Email = ?, Foto_Google = ? WHERE ID_Utilizador = ?";
        $stmtUpdate = $conn->prepare($queryUpdate);
        $stmtUpdate->bind_param("ssssi", $novoNome, $novoUsername, $novoEmail, $novaFoto, $idUsuario);

        if ($stmtUpdate->execute()) {
            $_SESSION['nomeUsuario'] = $novoNome;
            header("Location: perfil.php?success=Perfil atualizado com sucesso");
            exit();
        } else {
            $erro = "Erro ao atualizar perfil. Tente novamente.";
        }
        $stmtUpdate->close();
    }
    $stmtCheck->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - SeguraMenteKIDS</title>
    <link rel="stylesheet" href="../css/editar_perfil.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">

</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h1>
              <a href="dashboard.php" style="text-decoration: none; color: inherit;">
                SeguraMente<span class="kids-text">KIDS</span>
              </a>
            </h1>

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
            <a href="#" class="sidebar-item" id="logout">
                <img src="../imagens/logout_icon.png" alt="Sair" style="width: 20px; height: 20px;">
                Sair
            </a>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Header -->
            <div class="header">
                <?php include '../components/autocomplete.php'; ?>

                <a href="criar_quizz.php" class="create-quiz">Criar Quizz</a>
                <a href="perfil.php" class="profile">
                    <img src="<?php echo !empty($fotoGoogle) ? htmlspecialchars($fotoGoogle, ENT_QUOTES, 'UTF-8') : '../imagens/avatar.png'; ?>" alt="Avatar">
                    <span><?php echo htmlspecialchars($nomeUsuario); ?></span>
                </a>
            </div>

            <div class="edit-profile-section">
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="avatar-upload">
                        <label for="avatar">
                            <img id="preview-avatar" src="<?php echo !empty($fotoGoogle) ? htmlspecialchars($fotoGoogle, ENT_QUOTES, 'UTF-8') : '../imagens/avatar.png'; ?>" alt="Avatar do usuário" class="user-avatar">
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/*">
                    </div>

                    <?php if (isset($erro)) { echo "<p style='color: red;'>$erro</p>"; } ?>

                    <div class="form-group">
                        <label for="full-name">Nome</label>
                        <input type="text" id="full-name" name="full-name" value="<?php echo htmlspecialchars($nome); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Nome de Utilizador</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="buttons">
                        <button type="submit" class="btn-primary">Atualizar Perfil</button>
                        <button type="button" class="btn-secondary" onclick="history.back()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
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
    
        const avatarInput = document.getElementById('avatar');
        const previewImg = document.getElementById('preview-avatar');

        avatarInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
    

