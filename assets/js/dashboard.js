function tile(icone, valor, label, corTexto, corFundo, destaque) {
    return `
        <div class="group relative bg-white border ${destaque ? 'border-red-200' : 'border-slate-200'} rounded-2xl p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-150">
            <div class="flex items-start justify-between gap-2 mb-3">
                <span class="text-xs font-medium text-slate-500 leading-snug">${label}</span>
                <div class="w-9 h-9 rounded-lg ${corFundo} flex items-center justify-center text-base shrink-0">${icone}</div>
            </div>
            <p class="text-3xl font-semibold ${corTexto} tracking-tight">${valor}</p>
        </div>
    `;
}

function tituloSecao(icone, titulo, subtitulo) {
    return `
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-base">${icone}</div>
            <div>
                <h2 class="text-sm font-bold text-slate-700">${titulo}</h2>
                ${subtitulo ? `<p class="text-xs text-slate-400">${subtitulo}</p>` : ''}
            </div>
        </div>
    `;
}

function listaBarras(itens, corBarra, rotuloVazio) {
    if (!itens.length) {
        return `<p class="text-slate-400 text-xs py-2">${rotuloVazio}</p>`;
    }
    const max = Math.max(1, ...itens.map(i => Number(i.total)));
    return itens.map(i => `
        <div class="flex items-center gap-3 py-2 text-xs border-b border-slate-50 last:border-0">
            <span class="w-32 md:w-40 truncate text-slate-600 font-medium">${i.label}</span>
            <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                <div class="${corBarra} h-2.5 rounded-full transition-all duration-500" style="width:${Math.max(4, (i.total / max) * 100)}%"></div>
            </div>
            <span class="w-6 text-right font-semibold text-slate-700">${i.total}</span>
        </div>
    `).join('');
}

function painel(conteudoTitulo, conteudoCards, conteudoExtra) {
    return `
        <section class="mb-8 bg-slate-50/60 border border-slate-100 rounded-2xl p-5">
            ${conteudoTitulo}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 ${conteudoExtra ? 'mb-4' : ''}">
                ${conteudoCards}
            </div>
            ${conteudoExtra || ''}
        </section>
    `;
}

function painelExtra(titulo, corpo) {
    return `
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 mb-1">${titulo}</p>
            ${corpo}
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

    let html = painel(
        tituloSecao('🧑‍💼', 'Minha visao', 'Servidor / Analista'),
        `
        ${tile('📋', pessoal.minhas_demandas, 'Minhas demandas criadas', 'text-blue-700', 'bg-blue-50')}
        ${tile('⏳', pessoal.aguardando_minha_acao, 'Aguardando minha acao', 'text-purple-700', 'bg-purple-50')}
        ${tile('❓', pessoal.solicitacoes_informacao, 'Solicitacoes de informacao', 'text-amber-600', 'bg-amber-50')}
        ${tile('⏰', pessoal.prazos_proximos, 'Prazos proximos (3 dias)', 'text-orange-600', 'bg-orange-50', pessoal.prazos_proximos > 0)}
        ${tile('🚨', pessoal.vencidas, 'Demandas vencidas', 'text-red-600', 'bg-red-50', pessoal.vencidas > 0)}
        `
    );

    if (mostrarSetor) {
        html += painel(
            tituloSecao('🗂️', `Setor: ${setor_nome}`, 'Coordenacao / Supervisao / Direcao'),
            `
            ${tile('📊', setor.total, 'Total de demandas do setor', 'text-blue-700', 'bg-blue-50')}
            ${tile('⏳', setor.pendentes, 'Pendentes', 'text-purple-700', 'bg-purple-50')}
            ${tile('🚨', setor.atrasos, 'Atrasadas', 'text-red-600', 'bg-red-50', setor.atrasos > 0)}
            ${tile('🔥', setor.criticas, 'Criticas em aberto', 'text-orange-600', 'bg-orange-50', setor.criticas > 0)}
            `,
            painelExtra('Distribuicao de carga de trabalho por responsavel',
                listaBarras(setor.distribuicao_trabalho.map(d => ({ label: d.nome, total: d.total })), 'bg-blue-600', 'Sem demandas pendentes no setor.'))
        );
    }

    if (mostrarSecretaria) {
        const concluidas = secretaria.por_status.reduce((acc, s) => acc + (s.status === 'concluida' ? Number(s.total) : 0), 0);
        html += painel(
            tituloSecao('🏛️', `Secretaria: ${secretaria_sigla}`, 'Secretario / Gestao'),
            `
            ${tile('📈', secretaria.total, 'Total de demandas', 'text-blue-700', 'bg-blue-50')}
            ${tile('⏱️', secretaria.tempo_medio_dias !== null ? secretaria.tempo_medio_dias + 'd' : '-', 'Tempo medio ate conclusao', 'text-slate-700', 'bg-slate-100')}
            ${tile('✅', secretaria.taxa_no_prazo !== null ? secretaria.taxa_no_prazo + '%' : '-', 'Taxa de cumprimento de prazo', 'text-green-700', 'bg-green-50')}
            ${tile('🏁', concluidas, 'Concluidas', 'text-green-700', 'bg-green-50')}
            `,
            painelExtra(`Matriz Origem &rarr; ${secretaria_sigla}`,
                listaBarras(secretaria.matriz_origem.map(m => ({ label: m.origem, total: m.total })), 'bg-indigo-600', 'Nenhuma demanda recebida de outras secretarias ainda.'))
        );
    }

    container.innerHTML = html;
})();
