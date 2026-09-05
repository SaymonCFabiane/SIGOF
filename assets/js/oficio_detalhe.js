(function () {
    const container = document.getElementById('detalhe-oficio');
    const oficioId = container.dataset.oficioId;

    function iconeEvento(tipo) {
        if (tipo === 'comunicacao') return '💬';
        if (tipo === 'anexo') return '📎';
        return '🔄';
    }

    async function carregar() {
        const resposta = await chamarApi(`obter_oficio.php?id=${oficioId}`);
        if (!resposta.sucesso) {
            container.innerHTML = `<p class="text-red-600 text-sm">${resposta.mensagem}</p>`;
            return;
        }
        renderizar(resposta.dados);
    }

    function renderizar({ oficio, eventos, setores }) {
        const opcoesSetores = setores
            .filter(s => String(s.id) !== String(oficio.setor_destino_id))
            .map(s => `<option value="${s.id}">${s.sigla} - ${s.nome}</option>`)
            .join('');

        container.innerHTML = `
            <div class="flex items-start justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">${oficio.assunto}</h1>
                    <p class="text-xs text-slate-400">${oficio.numero_oficio ? `Oficio no ${oficio.numero_oficio} &bull; ` : ''}#${String(oficio.id).padStart(6, '0')}</p>
                </div>
                <span class="text-xs px-3 py-1 rounded-full font-semibold ${CORES_STATUS[oficio.status] || 'bg-slate-100'}">${LABELS_STATUS[oficio.status] || oficio.status}</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-white border rounded-lg p-4 mb-6 text-sm">
                <div><span class="block text-slate-400 text-xs">Origem</span>${oficio.origem_sigla}</div>
                <div><span class="block text-slate-400 text-xs">Destino</span>${oficio.destino_sigla}</div>
                <div><span class="block text-slate-400 text-xs">Prazo</span>${oficio.prazo_formatado}</div>
                <div><span class="block text-slate-400 text-xs">Prioridade / Urgencia</span>${oficio.prioridade} ${oficio.urgencia === 'urgente' ? '⚡' : ''}</div>
            </div>

            ${oficio.descricao ? `<p class="text-sm text-slate-600 bg-white border rounded-lg p-4 mb-6">${oficio.descricao}</p>` : ''}

            <div class="bg-white border rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-sm mb-3">Acoes</h2>
                <div class="flex flex-wrap gap-2 mb-3" id="acoes-status"></div>

                <div class="flex gap-2 items-end flex-wrap border-t pt-3 mt-3">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Encaminhar para</label>
                        <select id="select-encaminhar" class="border rounded px-2 py-1 text-sm">
                            <option value="">Selecione o setor...</option>
                            ${opcoesSetores}
                        </select>
                    </div>
                    <button id="btn-encaminhar" class="bg-cyan-700 text-white text-xs px-3 py-2 rounded hover:bg-cyan-800">Encaminhar</button>
                </div>
            </div>

            <div class="bg-white border rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-sm mb-3">Linha do Tempo</h2>
                <div class="space-y-4 border-l-2 border-slate-200 pl-4">
                    ${eventos.map(ev => `
                        <div>
                            <p class="text-sm"><span class="mr-1">${iconeEvento(ev.tipo_evento)}</span><strong>${ev.titulo}</strong></p>
                            ${ev.descricao ? `<p class="text-sm text-slate-600">${ev.descricao}</p>` : ''}
                            <p class="text-xs text-slate-400">${ev.autor_nome} &bull; ${new Date(ev.criado_em.replace(' ', 'T')).toLocaleString('pt-BR')}</p>
                        </div>
                    `).join('')}
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white border rounded-lg p-4">
                    <h2 class="font-semibold text-sm mb-2">Comunicacao</h2>
                    <textarea id="input-comunicacao" rows="2" class="w-full border rounded px-2 py-1 text-sm mb-2" placeholder="Escreva uma mensagem para o setor envolvido..."></textarea>
                    <button id="btn-comunicacao" class="bg-slate-700 text-white text-xs px-3 py-2 rounded hover:bg-slate-800">Enviar mensagem</button>
                </div>
                <div class="bg-white border rounded-lg p-4">
                    <h2 class="font-semibold text-sm mb-2">Anexar Documento</h2>
                    <input id="input-anexo" type="text" class="w-full border rounded px-2 py-1 text-sm mb-2" placeholder="Nome do arquivo (ex: oficio_123.pdf)">
                    <button id="btn-anexo" class="bg-slate-700 text-white text-xs px-3 py-2 rounded hover:bg-slate-800">Registrar anexo</button>
                </div>
            </div>
        `;

        renderizarAcoesStatus(oficio.status);
        ligarEventos();
    }

    function renderizarAcoesStatus(status) {
        const botoes = document.getElementById('acoes-status');
        const acoes = [];

        if (status === 'enviada') acoes.push(['receber', 'Marcar como recebida']);
        if (['recebida', 'aguardando_responsavel', 'aguardando_informacao'].includes(status)) acoes.push(['iniciar_andamento', 'Iniciar andamento']);
        if (['recebida', 'em_andamento', 'aguardando_responsavel'].includes(status)) acoes.push(['solicitar_informacao', 'Solicitar informacao']);
        if (['recebida', 'em_andamento', 'aguardando_responsavel', 'aguardando_informacao'].includes(status)) acoes.push(['devolver', 'Devolver']);
        if (status === 'em_andamento') acoes.push(['concluir', 'Concluir']);

        botoes.innerHTML = acoes.map(([acao, label]) => `
            <button data-acao="${acao}" class="botao-acao bg-blue-700 text-white text-xs px-3 py-2 rounded hover:bg-blue-800">${label}</button>
        `).join('') || '<span class="text-xs text-slate-400">Nenhuma acao disponivel neste status.</span>';
    }

    function ligarEventos() {
        document.querySelectorAll('.botao-acao').forEach(btn => {
            btn.addEventListener('click', async () => {
                const acao = btn.dataset.acao;
                let observacao = '';
                if (acao === 'devolver') {
                    observacao = prompt('Justificativa da devolucao (obrigatoria):') || '';
                    if (!observacao.trim()) return;
                }
                const resposta = await chamarApi('atualizar_status.php', { oficio_id: oficioId, acao, observacao }, 'POST');
                if (!resposta.sucesso) { alert(resposta.mensagem); return; }
                carregar();
            });
        });

        document.getElementById('btn-encaminhar').addEventListener('click', async () => {
            const setorId = document.getElementById('select-encaminhar').value;
            if (!setorId) { alert('Selecione o setor de destino.'); return; }
            const resposta = await chamarApi('atualizar_status.php', { oficio_id: oficioId, acao: 'encaminhar', novo_setor_destino_id: setorId }, 'POST');
            if (!resposta.sucesso) { alert(resposta.mensagem); return; }
            carregar();
        });

        document.getElementById('btn-comunicacao').addEventListener('click', async () => {
            const mensagem = document.getElementById('input-comunicacao').value.trim();
            if (!mensagem) return;
            const resposta = await chamarApi('adicionar_comunicacao.php', { oficio_id: oficioId, mensagem }, 'POST');
            if (!resposta.sucesso) { alert(resposta.mensagem); return; }
            carregar();
        });

        document.getElementById('btn-anexo').addEventListener('click', async () => {
            const nomeArquivo = document.getElementById('input-anexo').value.trim();
            if (!nomeArquivo) return;
            const resposta = await chamarApi('adicionar_anexo.php', { oficio_id: oficioId, nome_arquivo: nomeArquivo }, 'POST');
            if (!resposta.sucesso) { alert(resposta.mensagem); return; }
            carregar();
        });
    }

    carregar();
})();
