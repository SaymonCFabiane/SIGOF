(async function () {
    const container = document.getElementById('lista-oficios');
    const resposta = await chamarApi('listar_oficios.php');

    if (!resposta.sucesso) {
        container.innerHTML = `<p class="text-red-600 text-sm">${resposta.mensagem}</p>`;
        return;
    }

    if (resposta.dados.length === 0) {
        container.innerHTML = `<p class="text-slate-400 text-sm">Nenhum oficio cadastrado ainda.</p>`;
        return;
    }

    container.innerHTML = resposta.dados.map(o => `
        <a href="oficio_detalhe.php?id=${o.id}" class="block bg-white border rounded-lg p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <span class="font-semibold text-slate-800">${o.assunto}</span>
                    ${o.numero_oficio ? `<span class="text-xs text-slate-400 ml-2">Oficio no ${o.numero_oficio}</span>` : ''}
                </div>
                <span class="text-xs px-2 py-1 rounded-full font-medium ${CORES_STATUS[o.status] || 'bg-slate-100'}">${LABELS_STATUS[o.status] || o.status}</span>
            </div>
            <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 flex-wrap">
                <span>${o.origem_sigla} &rarr; ${o.destino_sigla}</span>
                <span>Prazo: ${o.prazo_formatado}</span>
                <span class="uppercase font-semibold ${o.prioridade === 'critica' ? 'text-red-600' : ''}">${o.prioridade}</span>
            </div>
        </a>
    `).join('');
})();
