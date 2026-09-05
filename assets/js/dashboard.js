function tile(valor, label, cor) {
    return `
        <div class="bg-white border rounded-lg p-4">
            <p class="text-2xl font-bold ${cor || 'text-slate-800'}">${valor}</p>
            <p class="text-xs text-slate-500 mt-1">${label}</p>
        </div>
    `;
}

(async function () {
    const container = document.getElementById('dashboard-conteudo');
    const resposta = await chamarApi('dashboard.php');

    if (!resposta.sucesso) {
        container.innerHTML = `<p class="text-red-600 text-sm">${resposta.mensagem}</p>`;
        return;
    }

    const { perfil, secretaria_sigla, setor_nome, pessoal, setor, secretaria } = resposta.dados;
    const mostrarSetor = ['coordenador', 'supervisor', 'diretor', 'secretario', 'admin_geral'].includes(perfil);
    const mostrarSecretaria = ['diretor', 'secretario', 'admin_geral'].includes(perfil);

    let html = `
        <section class="mb-8">
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Minha visao (Servidor/Analista)</h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                ${tile(pessoal.minhas_demandas, 'Minhas demandas criadas', 'text-blue-700')}
                ${tile(pessoal.aguardando_minha_acao, 'Aguardando minha acao', 'text-purple-700')}
                ${tile(pessoal.solicitacoes_informacao, 'Solicitacoes de informacao', 'text-amber-600')}
                ${tile(pessoal.prazos_proximos, 'Prazos proximos (3 dias)', 'text-orange-600')}
                ${tile(pessoal.vencidas, 'Demandas vencidas', 'text-red-600')}
            </div>
        </section>
    `;

    if (mostrarSetor) {
        const maxTrabalho = Math.max(1, ...setor.distribuicao_trabalho.map(d => d.total));
        html += `
        <section class="mb-8">
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Setor: ${setor_nome} (Coordenacao/Supervisao/Direcao)</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                ${tile(setor.total, 'Total de demandas do setor', 'text-blue-700')}
                ${tile(setor.pendentes, 'Pendentes', 'text-purple-700')}
                ${tile(setor.atrasos, 'Atrasadas', 'text-red-600')}
                ${tile(setor.criticas, 'Criticas em aberto', 'text-orange-600')}
            </div>
            <div class="bg-white border rounded-lg p-4">
                <p class="text-xs font-semibold text-slate-500 mb-2">Distribuicao de carga de trabalho por responsavel</p>
                ${setor.distribuicao_trabalho.map(d => `
                    <div class="flex items-center gap-2 mb-1 text-xs">
                        <span class="w-40 truncate">${d.nome}</span>
                        <div class="flex-1 bg-slate-100 rounded h-3">
                            <div class="bg-blue-600 h-3 rounded" style="width:${(d.total / maxTrabalho) * 100}%"></div>
                        </div>
                        <span class="w-6 text-right">${d.total}</span>
                    </div>
                `).join('') || '<p class="text-slate-400 text-xs">Sem dados.</p>'}
            </div>
        </section>
        `;
    }

    if (mostrarSecretaria) {
        const maxOrigem = Math.max(1, ...secretaria.matriz_origem.map(m => m.total));
        html += `
        <section>
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Secretaria: ${secretaria_sigla} (Secretario/Gestao)</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                ${tile(secretaria.total, 'Total de demandas', 'text-blue-700')}
                ${tile(secretaria.tempo_medio_dias !== null ? secretaria.tempo_medio_dias + 'd' : '-', 'Tempo medio ate conclusao', 'text-slate-700')}
                ${tile(secretaria.taxa_no_prazo !== null ? secretaria.taxa_no_prazo + '%' : '-', 'Taxa de cumprimento de prazo', 'text-green-700')}
                ${tile(secretaria.por_status.reduce((acc, s) => acc + (s.status === 'concluida' ? Number(s.total) : 0), 0), 'Concluidas', 'text-green-700')}
            </div>
            <div class="bg-white border rounded-lg p-4">
                <p class="text-xs font-semibold text-slate-500 mb-2">Matriz Origem &rarr; ${secretaria_sigla}</p>
                ${secretaria.matriz_origem.map(m => `
                    <div class="flex items-center gap-2 mb-1 text-xs">
                        <span class="w-24">${m.origem}</span>
                        <div class="flex-1 bg-slate-100 rounded h-3">
                            <div class="bg-indigo-600 h-3 rounded" style="width:${(m.total / maxOrigem) * 100}%"></div>
                        </div>
                        <span class="w-6 text-right">${m.total}</span>
                    </div>
                `).join('') || '<p class="text-slate-400 text-xs">Sem dados.</p>'}
            </div>
        </section>
        `;
    }

    container.innerHTML = html;
})();
