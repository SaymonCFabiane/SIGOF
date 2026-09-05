async function carregarOficios() {
    const container = document.getElementById('lista-oficios');
    container.innerHTML = '<p class="text-slate-400 text-sm">Carregando oficios...</p>';

    const params = new URLSearchParams({
        status: document.getElementById('filtro-status').value,
        prioridade: document.getElementById('filtro-prioridade').value,
        tipo: document.getElementById('filtro-tipo').value,
        situacao_prazo: document.getElementById('filtro-prazo').value,
    });

    const resposta = await chamarApi('listar_oficios.php?' + params.toString());

    if (!resposta.sucesso) {
        container.innerHTML = `<p class="text-red-600 text-sm">${resposta.mensagem}</p>`;
        return;
    }

    if (resposta.dados.length === 0) {
        container.innerHTML = `<p class="text-slate-400 text-sm">Nenhum oficio encontrado com esses filtros.</p>`;
        return;
    }

    container.innerHTML = resposta.dados.map(o => `
        <a href="oficio_detalhe.php?id=${o.id}" class="block bg-white border rounded-lg p-4 hover:shadow-md transition ${o.atrasada ? 'border-red-300' : ''}">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <span class="text-[10px] uppercase tracking-wide text-slate-400">${LABELS_TIPO[o.tipo] || o.tipo}</span>
                    <span class="font-semibold text-slate-800 block">${o.assunto}</span>
                    ${o.numero_oficio ? `<span class="text-xs text-slate-400">Oficio no ${o.numero_oficio}</span>` : ''}
                </div>
                <span class="text-xs px-2 py-1 rounded-full font-medium ${CORES_STATUS[o.status] || 'bg-slate-100'}">${LABELS_STATUS[o.status] || o.status}</span>
            </div>
            <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 flex-wrap">
                <span>${o.origem_sigla} &rarr; ${o.destino_sigla}</span>
                <span>Responsavel: ${o.responsavel_nome || '<em>a definir</em>'}</span>
                <span class="${o.atrasada ? 'text-red-600 font-semibold' : ''}">
                    Prazo: ${o.prazo_formatado}${o.atrasada ? ` (atrasada ha ${o.dias_atraso} dia(s) util(eis))` : ''}
                </span>
                <span class="uppercase font-semibold ${corPrioridade(o.prioridade)}">${o.prioridade}${o.urgencia === 'urgente' ? ' &bull; urgente' : ''}</span>
            </div>
        </a>
    `).join('');
}

document.getElementById('btn-filtrar').addEventListener('click', carregarOficios);
carregarOficios();
