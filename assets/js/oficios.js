const OPCOES_STATUS_ATIVOS = [
    ['', 'Todos (ativos)'],
    ['rascunho', 'Meus rascunhos'],
    ['enviada', 'Enviada'],
    ['recebida', 'Recebida'],
    ['aguardando_responsavel', 'Aguardando Responsavel'],
    ['em_analise', 'Em Analise'],
    ['em_andamento', 'Em Andamento'],
    ['aguardando_informacao', 'Aguardando Informacao'],
    ['aguardando_validacao', 'Aguardando Validacao'],
    ['devolvida', 'Devolvida'],
    ['reaberta', 'Reaberta'],
];

const OPCOES_STATUS_CONCLUIDOS = [
    ['', 'Todos (concluidos e cancelados)'],
    ['concluida', 'Concluida'],
    ['cancelada', 'Cancelada'],
];

const OPCOES_STATUS_POR_ABA = {
    ativos_recebidos: OPCOES_STATUS_ATIVOS,
    ativos_solicitados: OPCOES_STATUS_ATIVOS,
    concluidos_recebidos: OPCOES_STATUS_CONCLUIDOS,
    concluidos_solicitados: OPCOES_STATUS_CONCLUIDOS,
};

let abaAtual = 'ativos_recebidos';

function atualizarAbaVisual() {
    document.querySelectorAll('.aba-oficios').forEach(btn => {
        const ativo = btn.dataset.aba === abaAtual;
        btn.classList.toggle('border-blue-700', ativo);
        btn.classList.toggle('text-blue-700', ativo);
        btn.classList.toggle('border-transparent', !ativo);
        btn.classList.toggle('text-slate-500', !ativo);
    });
}

function atualizarOpcoesStatus() {
    const select = document.getElementById('filtro-status');
    select.innerHTML = OPCOES_STATUS_POR_ABA[abaAtual].map(([valor, label]) => `<option value="${valor}">${label}</option>`).join('');
}

async function carregarOficios() {
    const container = document.getElementById('lista-oficios');
    container.innerHTML = '<p class="text-slate-400 text-sm">Carregando oficios...</p>';

    const params = new URLSearchParams({
        aba: abaAtual,
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

    container.innerHTML = resposta.dados.map(o => {
        const emFila = o.posicao_fila && !['concluida', 'cancelada'].includes(o.status);
        const destacarFila = abaAtual === 'ativos_recebidos' && emFila;
        return `
        <a href="oficio_detalhe.php?id=${o.id}" class="flex items-center gap-4 bg-white border rounded-lg p-4 hover:shadow-md transition ${o.atrasada ? 'border-red-300' : ''}">
            ${destacarFila ? `
            <div class="shrink-0 flex items-center justify-center w-10 text-2xl font-bold text-slate-700" title="Posicao na fila de recebimento">
                ${o.posicao_fila}&ordm;
            </div>` : ''}
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <span class="text-[10px] uppercase tracking-wide text-slate-400">${LABELS_TIPO[o.tipo] || o.tipo}${o.origem_legado == 1 ? ' &bull; <span class="text-amber-600">LEGADO</span>' : ''}</span>
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
                    ${!destacarFila && emFila ? `<span class="font-semibold text-indigo-700">Fila: ${o.posicao_fila}&ordm; lugar</span>` : ''}
                </div>
            </div>
        </a>
        `;
    }).join('');
}

document.querySelectorAll('.aba-oficios').forEach(btn => {
    btn.addEventListener('click', () => {
        abaAtual = btn.dataset.aba;
        atualizarAbaVisual();
        atualizarOpcoesStatus();
        carregarOficios();
    });
});

document.getElementById('btn-filtrar').addEventListener('click', carregarOficios);

atualizarAbaVisual();
atualizarOpcoesStatus();
carregarOficios();
