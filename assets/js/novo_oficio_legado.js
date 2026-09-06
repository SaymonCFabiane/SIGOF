document.getElementById('form-oficio-legado').addEventListener('submit', async (evento) => {
    evento.preventDefault();
    const form = evento.target;
    const erroDiv = document.getElementById('mensagem-erro');
    erroDiv.classList.add('hidden');

    const arquivo = document.getElementById('input-arquivo-legado').files[0];
    if (!arquivo) {
        erroDiv.textContent = 'Selecione o documento (arquivo digitalizado ou exportado do sistema antigo).';
        erroDiv.classList.remove('hidden');
        return;
    }

    const dados = {
        assunto: form.assunto.value.trim(),
        numero_oficio: form.numero_oficio.value.trim(),
        data_original: form.data_original.value,
        setor_destino_id: form.setor_destino_id.value,
        status: form.status.value,
        descricao: form.descricao.value.trim(),
        observacoes: form.observacoes.value.trim(),
        nome_arquivo: arquivo.name,
    };

    const resposta = await chamarApi('criar_oficio_legado.php', dados, 'POST');

    if (!resposta.sucesso) {
        erroDiv.textContent = resposta.mensagem;
        erroDiv.classList.remove('hidden');
        return;
    }

    window.location.href = 'oficio_detalhe.php?id=' + resposta.dados.id;
});
