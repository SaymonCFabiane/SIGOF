async function carregarNotificacoes() {
    const container = document.getElementById('lista-notificacoes');
    const resposta = await chamarApi('listar_notificacoes.php');

    if (!resposta.sucesso) {
        container.innerHTML = `<p class="text-red-600 text-sm">${resposta.mensagem}</p>`;
        return;
    }
    if (resposta.dados.length === 0) {
        container.innerHTML = '<p class="text-slate-400 text-sm">Nenhuma notificacao ainda.</p>';
        return;
    }

    container.innerHTML = resposta.dados.map(n => `
        <a href="${n.oficio_id ? 'oficio_detalhe.php?id=' + n.oficio_id : '#'}" class="block border rounded-lg p-3 ${n.lida ? 'bg-white' : 'bg-blue-50 border-blue-200'} hover:shadow-sm transition">
            <p class="text-sm ${n.lida ? 'text-slate-600' : 'text-slate-800 font-medium'}">${n.mensagem}</p>
            <p class="text-xs text-slate-400 mt-1">${new Date(n.criado_em.replace(' ', 'T')).toLocaleString('pt-BR')}${n.oficio_assunto ? ' &bull; ' + n.oficio_assunto : ''}</p>
        </a>
    `).join('');
}

document.getElementById('btn-marcar-todas').addEventListener('click', async () => {
    await chamarApi('marcar_notificacao_lida.php', { todas: true }, 'POST');
    carregarNotificacoes();
});

carregarNotificacoes();
