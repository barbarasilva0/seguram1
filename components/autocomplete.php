<div class="autocomplete-wrapper">
    <div class="search-container">
        <img src="../imagens/lupa.png" alt="Lupa" class="search-icon">
        <input type="text" id="search-input" placeholder="Pesquisar quiz..." autocomplete="off">
        <div id="autocomplete-results" class="autocomplete-results"></div>
    </div>
</div>


<script>
    const input = document.getElementById('search-input');
    const resultsBox = document.getElementById('autocomplete-results');

    input.addEventListener('input', () => {
        const termo = input.value.trim();

        if (termo.length < 2) {
            resultsBox.innerHTML = '';
            return;
        }

        fetch(`pesquisar_ajax.php?search=${encodeURIComponent(termo)}`)
            .then(response => response.json())
            .then(data => {
                resultsBox.innerHTML = '';
                if (data.length > 0) {
                    resultsBox.innerHTML = `<li>Temas</li>`;
                    data.forEach(item => {
                        const li = document.createElement('li');
                        li.innerHTML = `<a href="jogar_quizz.php?id=${item.id}">${item.nome}</a>`;
                        resultsBox.appendChild(li);
                    });
                } else {
                    resultsBox.innerHTML = '<li>Sem resultados</li>';
                }
            });
    });

    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.innerHTML = '';
        }
    });
</script>
