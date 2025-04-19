<?php
session_start();
require_once '/home/seguram1/config.php';

$conn = getDBJogos();
$conn->set_charset("utf8mb4");

// Obter o PIN
$pin = isset($_GET['pin']) ? intval($_GET['pin']) : null;
$modoJogo = $pin ? 'multi' : 'solo';
$_SESSION['modoJogo'] = $modoJogo;

// Obter dados do jogador
$idUsuario = $_SESSION['idUsuario'] ?? null;
$nickname = $_SESSION['nomeUsuario'] ?? $_SESSION['username'] ?? $_SESSION['nicknameTemporario'] ?? null;

if ($modoJogo === 'multi' && (!$pin || !$nickname)) {
    die("Erro: PIN ou nickname inválido.");
}

$idJogo = null;
$perguntaAtualID = 0;
$indiceAtual = 0;
$perguntas = [];

if ($modoJogo === 'multi') {
    // Validar se o lobby existe e obter o ID do jogo
    $stmt = $conn->prepare("SELECT ID_Jogo FROM Lobby WHERE PIN = ?");
    $stmt->bind_param("i", $pin);
    $stmt->execute();
    $stmt->bind_result($idJogo);
    if (!$stmt->fetch()) {
        $stmt->close();
        die("Erro: Lobby com o PIN $pin não encontrado.");
    }
    $stmt->close();

    // Inserir jogador se ainda não estiver
    $stmt = $conn->prepare("SELECT 1 FROM JogadoresLobby WHERE PIN = ? AND Nickname = ?");
    $stmt->bind_param("is", $pin, $nickname);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        $tipo = $idUsuario ? 'registado' : 'anonimo';
        $stmt = $conn->prepare("INSERT INTO JogadoresLobby (PIN, ID_Utilizador, Nickname, Tipo, Last_Active, Status) 
                                VALUES (?, ?, ?, ?, NOW(), 'online')");
        $stmt->bind_param("iiss", $pin, $idUsuario, $nickname, $tipo);
        if (!$stmt->execute()) {
            error_log("Erro ao inserir jogador no lobby (quizz.php): " . $stmt->error);
            die("Erro ao entrar no lobby.");
        }
        $stmt->close();
    } else {
        $stmt->close();
        // Atualizar presença se já estiver
        $stmt = $conn->prepare("UPDATE JogadoresLobby SET Last_Active = NOW(), Status = 'online' WHERE PIN = ? AND Nickname = ?");
        $stmt->bind_param("is", $pin, $nickname);
        $stmt->execute();
        $stmt->close();
    }

    // Obter pergunta atual
    $stmt = $conn->prepare("SELECT Pergunta_Atual FROM EstadoQuiz WHERE PIN = ?");
    $stmt->bind_param("i", $pin);
    $stmt->execute();
    $stmt->bind_result($perguntaAtualID);
    $stmt->fetch();
    $stmt->close();
} else {
    // Quiz solo
    $idJogo = isset($_GET['id']) ? intval($_GET['id']) : 1;
}

// Obter perguntas
$stmt = $conn->prepare("SELECT ID_Pergunta, Texto, Opcoes, Resposta_Correta, Imagem, Pontos 
                        FROM Pergunta WHERE ID_Jogo = ? ORDER BY ID_Pergunta ASC");
$stmt->bind_param("i", $idJogo);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $perguntas[] = $row;
}
$stmt->close();
$conn->close();

// Determinar o índice da pergunta atual
if ($modoJogo === 'multi') {
    foreach ($perguntas as $i => $pergunta) {
        if ($pergunta['ID_Pergunta'] == $perguntaAtualID) {
            $indiceAtual = $i;
            break;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Quiz - SeguraMenteKIDS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/quizz.css">
    <link rel="icon" type="image/png" href="../imagens/favicon.png">
</head>
<body>
<div class="logo">SeguraMenteKIDS</div>

<?php if (empty($perguntas)): ?>
    <div class="quiz-container">
        <h2>Este quiz não contém perguntas ainda.</h2>
    </div>
<?php else: ?>
<div class="quiz-container">
    <div id="imagem-pergunta" class="question-image" style="display: none;">
        <img id="pergunta-img" src="" alt="Imagem da Pergunta" />
    </div>
    <div class="quiz-header" id="quiz-texto"></div>
    <div class="quiz-options" id="quiz-opcoes"></div>
    <div class="timer-wrapper">
    <div id="timer-text">Tempo: 60s</div>
        <div class="progress-bar-bg">
            <div id="progress-bar-fill"></div>
        </div>
    </div>

</div>

<script>
    const perguntas = <?= json_encode($perguntas) ?>;
    const pin = <?= $pin ? intval($pin) : 'null' ?>;
    let indiceAtual = <?= $indiceAtual ?>;
    const modo = "<?= $modoJogo ?>";
    const tempoMaximo = 60;
    let tempoInicio;
    let totalPontos = 0;

    function iniciarTimer(callback) {
        const timerText = document.getElementById("timer-text");
        const progressBar = document.getElementById("progress-bar-fill");
    
        let tempoRestante = tempoMaximo;
        tempoInicio = Date.now();
        timerText.textContent = `Tempo: ${tempoRestante}s`;
    
        const interval = setInterval(() => {
            tempoRestante--;
            timerText.textContent = `Tempo: ${tempoRestante}s`;
    
            const percent = (tempoRestante / tempoMaximo) * 100;
            progressBar.style.width = `${percent}%`;
    
            // Trocar cor com base no tempo restante
            if (tempoRestante <= 10) {
                progressBar.style.backgroundColor = '#f44336'; // vermelho
            } else if (tempoRestante <= 25) {
                progressBar.style.backgroundColor = '#ff9800'; // laranja
            } else {
                progressBar.style.backgroundColor = '#4CAF50'; // verde
            }
    
            if (tempoRestante <= 0) {
                clearInterval(interval);
                callback(null);
            }
        }, 1000);
    
        return interval;
    }

    function renderPergunta() {
        const pergunta = perguntas[indiceAtual];
        if (!pergunta) return;

        document.getElementById('quiz-texto').textContent = pergunta.Texto;
        const imgDiv = document.getElementById('imagem-pergunta');
        const img = document.getElementById('pergunta-img');

        if (pergunta.Imagem && pergunta.Imagem.trim() !== "") {
            img.src = pergunta.Imagem.replace(/^(\.\.\/)+/, '../');
            imgDiv.style.display = 'block';
        } else {
            imgDiv.style.display = 'none';
        }

        const opcoesDiv = document.getElementById('quiz-opcoes');
        opcoesDiv.innerHTML = '';

        const opcoes = pergunta.Opcoes.split(', ');
        const interval = iniciarTimer(resposta => registrarResposta(resposta, true));

        opcoes.forEach(opcao => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'quiz-option';
            btn.textContent = opcao;

            btn.addEventListener('click', () => {
                clearInterval(interval);
                registrarResposta(opcao);
            });

            opcoesDiv.appendChild(btn);
        });
    }

    function registrarResposta(resposta, tempoEsgotado = false) {
        const pergunta = perguntas[indiceAtual];
        const tempo = ((Date.now() - tempoInicio) / 1000).toFixed(2);
        const correta = pergunta.Resposta_Correta;
        const pontosTotais = parseInt(pergunta.Pontos);
        let pontosGanhos = 0;
        let acertou = resposta === correta;

        if (acertou) {
            pontosGanhos = tempoEsgotado
                ? Math.ceil(pontosTotais / 2)
                : Math.max(Math.ceil(pontosTotais * ((tempoMaximo - tempo) / tempoMaximo)), Math.ceil(pontosTotais / 2));
        }

        if (modo === "multi") {
            fetch("registar_resposta.php", {
                method: "POST",
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `pin=${pin}&id_pergunta=${pergunta.ID_Pergunta}&resposta=${encodeURIComponent(resposta || '')}&tempo=${tempo}`
            }).then(() => {
                aguardarTodosResponderem();
            });
        } else {
            mostrarFeedbackSolo(acertou, correta);
        }
    }

    function aguardarTodosResponderem() {
        const perguntaID = perguntas[indiceAtual].ID_Pergunta;
    
        const container = document.querySelector(".quiz-container");
        let existingMsg = document.getElementById("aguardando-msg");
        
        if (!existingMsg) {
            const loading = document.createElement("div");
            loading.id = "aguardando-msg";
            loading.className = "aguardando-box";
            loading.innerHTML = `
                <span class="loader"></span>
                <p>Aguardando todos os jogadores responderem...</p>
            `;
            container.appendChild(loading);
        }

        const ultimaPerguntaID = perguntas[perguntas.length - 1].ID_Pergunta;
    
        const intervalo = setInterval(() => {
            fetch(`verificar_respostas.php?pin=${pin}&id_pergunta=${perguntaID}`)
                .then(res => res.json())
                .then(data => {
                    if (data.todosResponderam) {
                        clearInterval(intervalo);
    
                        // ✅ Se for a última pergunta → vai direto para o ranking
                        if (perguntaID === ultimaPerguntaID) {
                            window.location.href = `ranking_final.php?pin=${pin}`;
                        } else {
                            window.location.href = `scoreboard.php?pin=${pin}&idPergunta=${perguntaID}`;
                        }
                    }
                })
                .catch(err => {
                    console.error("Erro ao verificar respostas:", err);
                });
        }, 3000);
    }

    function mostrarFeedbackSolo(acertou, correta) {
        const opcoes = document.querySelectorAll(".quiz-option");

        opcoes.forEach(btn => {
            if (btn.textContent === correta) {
                btn.classList.add("correct");
            } else {
                btn.classList.add("incorrect");
            }
            btn.disabled = true;
        });

        setTimeout(() => {
            indiceAtual++;
            if (indiceAtual < perguntas.length) {
                renderPergunta();
            } else {
                const container = document.querySelector(".quiz-container");
                container.innerHTML = `
                    <div class="quiz-fim-container">
                        <h2>Quiz concluído!</h2>
                        <p>Parabéns por concluir o quiz! O que deseja fazer a seguir?</p>
                        <div class="quiz-buttons">
                            <a href="dashboard.php" class="next-button">Ir para o Dashboard</a>
                        </div>
                    </div>`;
            }
        }, 2500);
    }

    renderPergunta();
    
    // ✅ Enviar heartbeat mesmo durante o quizz
    setInterval(() => {
        fetch("heartbeat.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `pin=${encodeURIComponent(pin)}&nickname=${encodeURIComponent("<?= addslashes($nickname) ?>")}`
        });
    }, 10000);
</script>
<?php endif; ?>
</body>
</html>
