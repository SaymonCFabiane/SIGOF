const formNovoOficio = document.getElementById('form-novo-oficio');
let acaoClicada = 'enviar';

formNovoOficio.querySelectorAll('button[type=submit]').forEach(btn => {
    btn.addEventListener('click', () => { acaoClicada = btn.dataset.acao; });
});

formNovoOficio.addEventListener('submit', async (e) => {
    e.preventDefault();
    const erroEl = document.getElementById('mensagem-erro');
    erroEl.classList.add('hidden');

    const formData = new FormData(formNovoOficio);
    const dados = Object.fromEntries(formData.entries());
    dados.setores_envolvidos = formData.getAll('setores_envolvidos[]');
    dados.acao = acaoClicada;

    if (acaoClicada === 'enviar') {
        const tipoLabel = LABELS_TIPO[dados.tipo] || dados.tipo;
        const destinoLabel = formNovoOficio.querySelector('[name=setor_destino_id]').selectedOptions[0].text;
        const resumo = `Revisar antes de enviar:\n\nTipo: ${tipoLabel}\nAssunto: ${dados.assunto}\nDestino: ${destinoLabel}\nPrazo: ${dados.prazo}\n\nA prioridade e urgencia serao definidas por quem receber a demanda.\n\nConfirma o envio?`;
        if (!confirm(resumo)) {
            return;
        }
    }

    const resposta = await chamarApi('criar_oficio.php', dados, 'POST');

    if (!resposta.sucesso) {
        erroEl.textContent = resposta.mensagem;
        erroEl.classList.remove('hidden');
        return;
    }

    window.location.href = `oficio_detalhe.php?id=${resposta.dados.id}`;
});
