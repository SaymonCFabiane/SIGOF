async function executarBusca() {
    const termo = document.getElementById('input-busca').value.trim();
    const container = document.getElementById('resultados-busca');
    if (!termo) { container.innerHTML = ''; return; }

    container.innerHTML = '<p class="text-slate-400 text-sm">Buscando...</p>';
    const resposta = await chamarApi('buscar_competencia.php?termo=' + encodeURIComponent(termo));

    if (!resposta.sucesso || resposta.dados.length === 0) {
        container.innerHTML = '<p class="text-slate-400 text-sm">Nenhum setor encontrado para esse termo.</p>';
        return;
    }

    container.innerHTML = resposta.dados.map(r => `
        <div class="bg-white border rounded-lg p-4 flex items-center justify-between flex-wrap gap-3">
            <div>
                <span class="font-semibold text-slate-800">${r.secretaria_sigla} &rarr; ${r.departamento_nome} &rarr; ${r.setor_nome}</span>
                <p class="text-xs text-slate-500 mt-1">${r.setor_competencias || ''}</p>
            </div>
            <a href="novo_oficio.php?destino_setor=${r.setor_id}&tipo=solicitacao_informacao" class="bg-blue-700 text-white text-xs px-3 py-2 rounded hover:bg-blue-800 whitespace-nowrap">
                Solicitar informacao
            </a>
        </div>
    `).join('');
}

document.getElementById('btn-buscar').addEventListener('click', executarBusca);
document.getElementById('input-busca').addEventListener('keydown', (e) => { if (e.key === 'Enter') executarBusca(); });
