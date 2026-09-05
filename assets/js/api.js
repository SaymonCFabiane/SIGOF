async function chamarApi(endpoint, dados = null, metodo = 'GET') {
    const opcoes = { method: metodo, headers: { 'Content-Type': 'application/json' } };
    if (dados !== null) opcoes.body = JSON.stringify(dados);
    const resposta = await fetch('api/' + endpoint, opcoes);
    return resposta.json();
}

const CORES_STATUS = {
    rascunho: 'bg-slate-100 text-slate-600',
    enviada: 'bg-blue-100 text-blue-800',
    recebida: 'bg-indigo-100 text-indigo-800',
    aguardando_responsavel: 'bg-amber-100 text-amber-800',
    em_analise: 'bg-cyan-100 text-cyan-800',
    em_andamento: 'bg-purple-100 text-purple-800',
    aguardando_informacao: 'bg-amber-100 text-amber-800',
    aguardando_validacao: 'bg-amber-100 text-amber-800',
    encaminhada: 'bg-cyan-100 text-cyan-800',
    devolvida: 'bg-red-100 text-red-800',
    concluida: 'bg-green-100 text-green-800',
    cancelada: 'bg-slate-200 text-slate-700',
    reaberta: 'bg-orange-100 text-orange-800',
};

const LABELS_STATUS = {
    rascunho: 'Rascunho',
    enviada: 'Enviada',
    recebida: 'Recebida',
    aguardando_responsavel: 'Aguardando Responsavel',
    em_analise: 'Em Analise',
    em_andamento: 'Em Andamento',
    aguardando_informacao: 'Aguardando Informacao',
    aguardando_validacao: 'Aguardando Validacao',
    encaminhada: 'Encaminhada',
    devolvida: 'Devolvida',
    concluida: 'Concluida',
    cancelada: 'Cancelada',
    reaberta: 'Reaberta',
};

const LABELS_TIPO = {
    solicitacao: 'Solicitacao',
    solicitacao_informacao: 'Solicitacao de Informacao',
    solicitacao_documento: 'Solicitacao de Documento',
    analise: 'Analise',
    providencia: 'Providencia',
    encaminhamento: 'Encaminhamento',
    oficio: 'Oficio',
    denuncia: 'Denuncia',
    outro: 'Outro',
};

function corPrioridade(prioridade) {
    if (prioridade === 'critica') return 'text-red-600';
    if (prioridade === 'alta') return 'text-orange-600';
    return 'text-slate-500';
}
