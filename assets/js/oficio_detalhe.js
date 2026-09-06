(function () {
    const container = document.getElementById('detalhe-oficio');
    const oficioId = container.dataset.oficioId;

    function iconeEvento(tipo) {
        if (tipo === 'comunicacao') return '💬';
        if (tipo === 'anexo') return '📎';
        if (tipo === 'alteracao') return '✏️';
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

    async function executarAcao(endpoint, corpo, mensagemSucesso) {
        const resposta = await chamarApi(endpoint, corpo, 'POST');
        if (!resposta.sucesso) { alert(resposta.mensagem); return; }
        carregar();
    }

    function renderizar(d) {
        const { oficio, eventos, colaboradores, destinatarios_extras, setores, usuarios_destino, permissoes } = d;
        const status = oficio.status;
        const finalizado = ['concluida', 'cancelada'].includes(status);

        const opcoesSetoresEncaminhar = setores
            .filter(s => String(s.id) !== String(oficio.setor_destino_id))
            .map(s => `<option value="${s.id}">${s.sigla} - ${s.nome}</option>`).join('');

        const opcoesSetoresEnvolver = setores
            .filter(s => String(s.id) !== String(oficio.setor_destino_id) && !destinatarios_extras.some(x => String(x.id) === String(s.id)))
            .map(s => `<option value="${s.id}">${s.sigla} - ${s.nome}</option>`).join('');

        const opcoesResponsavel = usuarios_destino.map(u => `<option value="${u.id}">${u.nome}</option>`).join('');
        const opcoesColaboradores = usuarios_destino.map(u => `<label class="flex items-center gap-1 text-xs mr-3"><input type="checkbox" class="chk-colaborador" value="${u.id}"> ${u.nome}</label>`).join('');

        container.innerHTML = `
            <div class="flex items-start justify-between mb-4 flex-wrap gap-2">
                <div>
                    <span class="text-[10px] uppercase tracking-wide text-slate-400">${LABELS_TIPO[oficio.tipo] || oficio.tipo}${oficio.confidencial == 1 ? ' &bull; CONFIDENCIAL' : ''}${oficio.origem_legado == 1 ? ' &bull; <span class="text-amber-600">LEGADO</span>' : ''}</span>
                    <h1 class="text-xl font-bold text-slate-800">${oficio.assunto}</h1>
                    <p class="text-xs text-slate-400">${oficio.numero_oficio ? `Oficio no ${oficio.numero_oficio} &bull; ` : ''}#${String(oficio.id).padStart(6, '0')} &bull; criado por ${oficio.criador_nome || 'Denunciante (identidade protegida)'}</p>
                </div>
                <span class="text-xs px-3 py-1 rounded-full font-semibold ${CORES_STATUS[status] || 'bg-slate-100'}">${LABELS_STATUS[status] || status}</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-white border rounded-lg p-4 mb-4 text-sm">
                <div><span class="block text-slate-400 text-xs">Origem</span>${oficio.origem_sigla}</div>
                <div><span class="block text-slate-400 text-xs">Destino</span>${oficio.destino_sigla}</div>
                <div><span class="block text-slate-400 text-xs">Responsavel</span>${oficio.responsavel_nome || '<em>a definir</em>'}</div>
                <div><span class="block text-slate-400 text-xs">Prioridade / Urgencia</span>${oficio.prioridade} ${oficio.urgencia === 'urgente' ? '⚡' : ''}</div>
                <div><span class="block text-slate-400 text-xs">Prazo</span>${oficio.prazo_formatado}${oficio.prazo !== oficio.prazo_original ? ` <span class="text-slate-400">(original: ${oficio.prazo_original_formatado})</span>` : ''}</div>
                ${oficio.prazo_resposta_formatado ? `<div><span class="block text-slate-400 text-xs">Prazo de resposta</span>${oficio.prazo_resposta_formatado}</div>` : ''}
                ${colaboradores.length ? `<div class="col-span-2"><span class="block text-slate-400 text-xs">Colaboradores</span>${colaboradores.map(c => c.nome).join(', ')}</div>` : ''}
                ${destinatarios_extras.length ? `<div class="col-span-2"><span class="block text-slate-400 text-xs">Setores envolvidos</span>${destinatarios_extras.map(s => `${s.sigla} - ${s.nome}`).join(', ')}</div>` : ''}
            </div>

            ${oficio.descricao ? `<p class="text-sm text-slate-600 bg-white border rounded-lg p-4 mb-4"><strong>Descricao:</strong> ${oficio.descricao}</p>` : ''}
            ${oficio.observacoes ? `<p class="text-sm text-slate-600 bg-white border rounded-lg p-4 mb-4"><strong>Observacoes:</strong> ${oficio.observacoes}</p>` : ''}
            ${oficio.resposta_final ? `<p class="text-sm text-green-800 bg-green-50 border border-green-200 rounded-lg p-4 mb-4"><strong>Resposta final:</strong> ${oficio.resposta_final}</p>` : ''}

            <div class="bg-white border rounded-lg p-4 mb-4">
                <h2 class="font-semibold text-sm mb-3">Acoes de fluxo</h2>
                <div class="flex flex-wrap gap-2" id="acoes-status"></div>
            </div>

            ${(status === 'enviada' && (permissoes.eh_do_setor_destino || permissoes.eh_plus)) ? `
            <div class="bg-white border rounded-lg p-4 mb-4">
                <h2 class="font-semibold text-sm mb-3">Confirmar recebimento e classificar</h2>
                <p class="text-xs text-slate-500 mb-2">Quem recebe define a prioridade e a urgencia da demanda.</p>
                <div class="flex gap-2 items-end flex-wrap">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Prioridade</label>
                        <select id="select-prioridade-recebimento" class="border rounded px-2 py-1 text-sm">
                            <option value="baixa">Baixa</option>
                            <option value="normal" selected>Normal</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Critica</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Urgencia</label>
                        <select id="select-urgencia-recebimento" class="border rounded px-2 py-1 text-sm">
                            <option value="nao_urgente" selected>Nao Urgente</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    <button id="btn-confirmar-recebimento" class="bg-blue-700 text-white text-xs px-3 py-2 rounded hover:bg-blue-800">Confirmar Recebimento</button>
                </div>
            </div>` : ''}

            ${(!finalizado && (status === 'recebida' || status === 'aguardando_responsavel') && permissoes.eh_do_setor_destino) ? `
            <div class="bg-white border rounded-lg p-4 mb-4">
                <h2 class="font-semibold text-sm mb-3">Definir responsavel e colaboradores</h2>
                <div class="flex gap-2 items-end flex-wrap mb-2">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Responsavel principal</label>
                        <select id="select-responsavel" class="border rounded px-2 py-1 text-sm">
                            <option value="">Selecione...</option>
                            ${opcoesResponsavel}
                        </select>
                    </div>
                </div>
                <div class="mb-2"><span class="text-xs text-slate-500 block mb-1">Colaboradores (opcional)</span>${opcoesColaboradores}</div>
                <button id="btn-definir-responsavel" class="bg-blue-700 text-white text-xs px-3 py-2 rounded hover:bg-blue-800">Confirmar</button>
            </div>` : ''}

            ${!finalizado ? `
            <div class="bg-white border rounded-lg p-4 mb-4 grid md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Encaminhar (transfere a responsabilidade)</p>
                    <div class="flex gap-2 items-end flex-wrap">
                        <select id="select-encaminhar" class="border rounded px-2 py-1 text-sm">
                            <option value="">Selecione o setor...</option>
                            ${opcoesSetoresEncaminhar}
                        </select>
                        <button id="btn-encaminhar" class="bg-cyan-700 text-white text-xs px-3 py-2 rounded hover:bg-cyan-800">Encaminhar</button>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Envolver outro setor (sem transferir)</p>
                    <div class="flex gap-2 items-end flex-wrap">
                        <select id="select-envolver" class="border rounded px-2 py-1 text-sm">
                            <option value="">Selecione o setor...</option>
                            ${opcoesSetoresEnvolver}
                        </select>
                        <button id="btn-envolver" class="bg-slate-600 text-white text-xs px-3 py-2 rounded hover:bg-slate-700">Envolver</button>
                    </div>
                </div>
            </div>` : ''}

            ${(permissoes.eh_plus || permissoes.eh_responsavel) && !finalizado ? `
            <div class="bg-white border rounded-lg p-4 mb-4 grid md:grid-cols-2 gap-4">
                ${permissoes.eh_plus ? `
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Alterar prazo (PLUS, exige justificativa)</p>
                    <div class="flex gap-2 items-end flex-wrap">
                        <input type="date" id="input-novo-prazo" class="border rounded px-2 py-1 text-sm">
                        <button id="btn-alterar-prazo" class="bg-amber-600 text-white text-xs px-3 py-2 rounded hover:bg-amber-700">Alterar</button>
                    </div>
                </div>` : '<div></div>'}
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Alterar prioridade/urgencia (exige justificativa)</p>
                    <div class="flex gap-2 items-end flex-wrap">
                        <select id="select-nova-prioridade" class="border rounded px-2 py-1 text-sm">
                            <option value="">Prioridade...</option>
                            <option value="baixa">Baixa</option><option value="normal">Normal</option><option value="alta">Alta</option><option value="critica">Critica</option>
                        </select>
                        <select id="select-nova-urgencia" class="border rounded px-2 py-1 text-sm">
                            <option value="">Urgencia...</option>
                            <option value="nao_urgente">Nao Urgente</option><option value="urgente">Urgente</option>
                        </select>
                        <button id="btn-alterar-prioridade" class="bg-orange-600 text-white text-xs px-3 py-2 rounded hover:bg-orange-700">Alterar</button>
                    </div>
                </div>
            </div>` : ''}

            <div class="bg-white border rounded-lg p-4 mb-4">
                <h2 class="font-semibold text-sm mb-3">Linha do Tempo</h2>
                <div class="space-y-4 border-l-2 border-slate-200 pl-4">
                    ${eventos.map(ev => `
                        <div>
                            <p class="text-sm"><span class="mr-1">${iconeEvento(ev.tipo_evento)}</span><strong>${ev.titulo}</strong></p>
                            ${ev.descricao ? `<p class="text-sm text-slate-600">${ev.descricao}</p>` : ''}
                            ${ev.justificativa ? `<p class="text-xs text-slate-500 italic">Justificativa: ${ev.justificativa}</p>` : ''}
                            <p class="text-xs text-slate-400">${ev.autor_nome} (${ev.autor_perfil_snapshot || ''}${ev.autor_local_snapshot ? ' - ' + ev.autor_local_snapshot : ''}) &bull; ${new Date(ev.criado_em.replace(' ', 'T')).toLocaleString('pt-BR')}</p>
                        </div>
                    `).join('')}
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white border rounded-lg p-4">
                    <h2 class="font-semibold text-sm mb-2">Comunicacao</h2>
                    <textarea id="input-comunicacao" rows="2" class="w-full border rounded px-2 py-1 text-sm mb-2" placeholder="Escreva uma mensagem para o setor envolvido..."></textarea>
                    <label class="flex items-center gap-1 text-xs mb-2"><input type="checkbox" id="chk-formal"> Marcar como comunicacao formal</label>
                    <button id="btn-comunicacao" class="bg-slate-700 text-white text-xs px-3 py-2 rounded hover:bg-slate-800">Enviar mensagem</button>
                </div>
                <div class="bg-white border rounded-lg p-4">
                    <h2 class="font-semibold text-sm mb-2">Anexar Documento</h2>
                    <input id="input-anexo" type="file" class="w-full border rounded px-2 py-1 text-sm mb-2 text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-slate-100 file:text-slate-700 file:text-xs">
                    <button id="btn-anexo" class="bg-slate-700 text-white text-xs px-3 py-2 rounded hover:bg-slate-800">Anexar Documento</button>
                </div>
            </div>
        `;

        renderizarAcoesStatus(status, permissoes);
        ligarEventos(permissoes);
    }

    function renderizarAcoesStatus(status, permissoes) {
        const botoes = document.getElementById('acoes-status');
        const acoes = [];

        if (status === 'rascunho') acoes.push(['enviar_rascunho', 'Enviar agora', 'bg-blue-700 hover:bg-blue-800']);
        if (status === 'aguardando_responsavel') acoes.push(['iniciar_analise', 'Iniciar analise', 'bg-cyan-700 hover:bg-cyan-800']);
        if (['em_analise', 'aguardando_responsavel', 'aguardando_informacao', 'reaberta'].includes(status)) acoes.push(['iniciar_andamento', 'Iniciar/Retomar andamento', 'bg-purple-700 hover:bg-purple-800']);
        if (['em_analise', 'em_andamento', 'aguardando_responsavel'].includes(status)) acoes.push(['solicitar_informacao', 'Solicitar informacao', 'bg-amber-600 hover:bg-amber-700']);
        if (status === 'aguardando_informacao') acoes.push(['responder_informacao', 'Informacao respondida', 'bg-purple-700 hover:bg-purple-800']);
        if (status === 'em_andamento') acoes.push(['enviar_validacao', 'Enviar para validacao', 'bg-amber-600 hover:bg-amber-700']);
        if (['em_andamento', 'aguardando_validacao'].includes(status)) acoes.push(['concluir', 'Concluir', 'bg-green-700 hover:bg-green-800']);
        if (['recebida', 'em_analise', 'em_andamento', 'aguardando_responsavel', 'aguardando_informacao', 'aguardando_validacao'].includes(status)) acoes.push(['devolver', 'Devolver', 'bg-red-700 hover:bg-red-800']);
        if (!['concluida', 'cancelada'].includes(status)) acoes.push(['cancelar', 'Cancelar', 'bg-slate-600 hover:bg-slate-700']);
        if (['concluida', 'cancelada'].includes(status) && permissoes.eh_demandante) acoes.push(['reabrir', 'Reabrir', 'bg-orange-600 hover:bg-orange-700']);

        botoes.innerHTML = acoes.map(([acao, label, cor]) => `
            <button data-acao="${acao}" class="botao-acao text-white text-xs px-3 py-2 rounded ${cor}">${label}</button>
        `).join('') || '<span class="text-xs text-slate-400">Nenhuma acao disponivel neste status.</span>';
    }

    function ligarEventos(permissoes) {
        document.querySelectorAll('.botao-acao').forEach(btn => {
            btn.addEventListener('click', async () => {
                const acao = btn.dataset.acao;

                if (acao === 'enviar_rascunho') {
                    return executarAcao('enviar_rascunho.php', { oficio_id: oficioId });
                }
                if (acao === 'devolver') {
                    const observacao = prompt('Justificativa da devolucao (obrigatoria):') || '';
                    if (!observacao.trim()) return;
                    return executarAcao('atualizar_status.php', { oficio_id: oficioId, acao, observacao });
                }
                if (acao === 'cancelar') {
                    const justificativa = prompt('Justificativa do cancelamento (obrigatoria):') || '';
                    if (!justificativa.trim()) return;
                    return executarAcao('atualizar_status.php', { oficio_id: oficioId, acao, justificativa });
                }
                if (acao === 'reabrir') {
                    const justificativa = prompt('Justificativa da reabertura (obrigatoria):') || '';
                    if (!justificativa.trim()) return;
                    return executarAcao('atualizar_status.php', { oficio_id: oficioId, acao, justificativa });
                }
                if (acao === 'concluir') {
                    const respostaFinal = prompt('Resposta final (obrigatoria para concluir):') || '';
                    if (!respostaFinal.trim()) return;
                    return executarAcao('atualizar_status.php', { oficio_id: oficioId, acao, resposta_final: respostaFinal });
                }

                return executarAcao('atualizar_status.php', { oficio_id: oficioId, acao, observacao: '' });
            });
        });

        const btnConfirmarRecebimento = document.getElementById('btn-confirmar-recebimento');
        if (btnConfirmarRecebimento) {
            btnConfirmarRecebimento.addEventListener('click', async () => {
                const prioridade = document.getElementById('select-prioridade-recebimento').value;
                const urgencia = document.getElementById('select-urgencia-recebimento').value;
                return executarAcao('atualizar_status.php', { oficio_id: oficioId, acao: 'receber', prioridade, urgencia });
            });
        }

        const btnDefinirResponsavel = document.getElementById('btn-definir-responsavel');
        if (btnDefinirResponsavel) {
            btnDefinirResponsavel.addEventListener('click', async () => {
                const responsavelId = document.getElementById('select-responsavel').value;
                if (!responsavelId) { alert('Selecione o responsavel principal.'); return; }
                const colaboradores = Array.from(document.querySelectorAll('.chk-colaborador:checked')).map(c => c.value);
                return executarAcao('definir_responsavel.php', { oficio_id: oficioId, responsavel_id: responsavelId, colaboradores });
            });
        }

        const btnEncaminhar = document.getElementById('btn-encaminhar');
        if (btnEncaminhar) {
            btnEncaminhar.addEventListener('click', async () => {
                const setorId = document.getElementById('select-encaminhar').value;
                if (!setorId) { alert('Selecione o setor de destino.'); return; }
                return executarAcao('atualizar_status.php', { oficio_id: oficioId, acao: 'encaminhar', novo_setor_destino_id: setorId });
            });
        }

        const btnEnvolver = document.getElementById('btn-envolver');
        if (btnEnvolver) {
            btnEnvolver.addEventListener('click', async () => {
                const setorId = document.getElementById('select-envolver').value;
                if (!setorId) { alert('Selecione o setor a envolver.'); return; }
                return executarAcao('envolver_setor.php', { oficio_id: oficioId, setor_id: setorId });
            });
        }

        const btnAlterarPrazo = document.getElementById('btn-alterar-prazo');
        if (btnAlterarPrazo) {
            btnAlterarPrazo.addEventListener('click', async () => {
                const novoPrazo = document.getElementById('input-novo-prazo').value;
                if (!novoPrazo) { alert('Selecione o novo prazo.'); return; }
                const justificativa = prompt('Justificativa da alteracao de prazo (obrigatoria):') || '';
                if (!justificativa.trim()) return;
                return executarAcao('alterar_prazo.php', { oficio_id: oficioId, novo_prazo: novoPrazo, justificativa });
            });
        }

        const btnAlterarPrioridade = document.getElementById('btn-alterar-prioridade');
        if (btnAlterarPrioridade) {
            btnAlterarPrioridade.addEventListener('click', async () => {
                const prioridade = document.getElementById('select-nova-prioridade').value;
                const urgencia = document.getElementById('select-nova-urgencia').value;
                if (!prioridade && !urgencia) { alert('Selecione ao menos uma alteracao.'); return; }
                const justificativa = prompt('Justificativa da alteracao (obrigatoria):') || '';
                if (!justificativa.trim()) return;
                return executarAcao('alterar_prioridade.php', { oficio_id: oficioId, prioridade, urgencia, justificativa });
            });
        }

        const btnComunicacao = document.getElementById('btn-comunicacao');
        if (btnComunicacao) {
            btnComunicacao.addEventListener('click', async () => {
                const mensagem = document.getElementById('input-comunicacao').value.trim();
                if (!mensagem) return;
                const formal = document.getElementById('chk-formal').checked;
                return executarAcao('adicionar_comunicacao.php', { oficio_id: oficioId, mensagem, formal });
            });
        }

        const btnAnexo = document.getElementById('btn-anexo');
        if (btnAnexo) {
            btnAnexo.addEventListener('click', async () => {
                const inputAnexo = document.getElementById('input-anexo');
                const arquivo = inputAnexo.files[0];
                if (!arquivo) return;
                const resultado = await executarAcao('adicionar_anexo.php', { oficio_id: oficioId, nome_arquivo: arquivo.name });
                inputAnexo.value = '';
                return resultado;
            });
        }
    }

    carregar();
})();
