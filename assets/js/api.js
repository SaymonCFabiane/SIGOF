async function chamarApi(endpoint, dados = null, metodo = 'GET') {
    const opcoes = { method: metodo, headers: { 'Content-Type': 'application/json' } };
    if (dados !== null) opcoes.body = JSON.stringify(dados);
    const resposta = await fetch('api/' + endpoint, opcoes);
    return resposta.json();
}

const CORES_STATUS = {
    enviada: 'bg-blue-100 text-blue-800',
    recebida: 'bg-indigo-100 text-indigo-800',
    aguardando_responsavel: 'bg-amber-100 text-amber-800',
    aguardando_informacao: 'bg-amber-100 text-amber-800',
    em_andamento: 'bg-purple-100 text-purple-800',
    devolvida: 'bg-red-100 text-red-800',
    concluida: 'bg-green-100 text-green-800',
};

const LABELS_STATUS = {
    enviada: 'Enviada',
    recebida: 'Recebida',
    aguardando_responsavel: 'Aguardando Responsavel',
    aguardando_informacao: 'Aguardando Informacao',
    em_andamento: 'Em Analise / Andamento',
    devolvida: 'Devolvida',
    concluida: 'Concluida',
};
