document.getElementById('form-novo-oficio').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const dados = Object.fromEntries(new FormData(form).entries());
    const erroEl = document.getElementById('mensagem-erro');
    erroEl.classList.add('hidden');

    const resposta = await chamarApi('criar_oficio.php', dados, 'POST');

    if (!resposta.sucesso) {
        erroEl.textContent = resposta.mensagem;
        erroEl.classList.remove('hidden');
        return;
    }

    window.location.href = `oficio_detalhe.php?id=${resposta.dados.id}`;
});
