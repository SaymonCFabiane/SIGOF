<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/includes/header.php';
?>
<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
    <h1 class="text-xl font-bold text-slate-800">Oficios</h1>
    <a href="novo_oficio.php" class="bg-blue-700 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-800">+ Nova Demanda</a>
</div>

<div class="bg-white border rounded-lg p-4 mb-6 grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
    <div>
        <label class="block text-xs text-slate-500 mb-1">Status</label>
        <select id="filtro-status" class="w-full border rounded px-2 py-1">
            <option value="">Todos (exceto rascunho)</option>
            <option value="rascunho">Meus rascunhos</option>
            <option value="enviada">Enviada</option>
            <option value="recebida">Recebida</option>
            <option value="aguardando_responsavel">Aguardando Responsavel</option>
            <option value="em_analise">Em Analise</option>
            <option value="em_andamento">Em Andamento</option>
            <option value="aguardando_informacao">Aguardando Informacao</option>
            <option value="aguardando_validacao">Aguardando Validacao</option>
            <option value="devolvida">Devolvida</option>
            <option value="concluida">Concluida</option>
            <option value="cancelada">Cancelada</option>
            <option value="reaberta">Reaberta</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">Prioridade</label>
        <select id="filtro-prioridade" class="w-full border rounded px-2 py-1">
            <option value="">Todas</option>
            <option value="baixa">Baixa</option>
            <option value="normal">Normal</option>
            <option value="alta">Alta</option>
            <option value="critica">Critica</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">Tipo</label>
        <select id="filtro-tipo" class="w-full border rounded px-2 py-1">
            <option value="">Todos</option>
            <option value="solicitacao">Solicitacao</option>
            <option value="solicitacao_informacao">Solicitacao de Informacao</option>
            <option value="solicitacao_documento">Solicitacao de Documento</option>
            <option value="analise">Analise</option>
            <option value="providencia">Providencia</option>
            <option value="encaminhamento">Encaminhamento</option>
            <option value="oficio">Oficio</option>
            <option value="denuncia">Denuncia</option>
            <option value="outro">Outro</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">Situacao do prazo</label>
        <select id="filtro-prazo" class="w-full border rounded px-2 py-1">
            <option value="">Todas</option>
            <option value="vencidas">Vencidas</option>
            <option value="proximas">Proximas do vencimento (3 dias)</option>
        </select>
    </div>
    <div class="flex items-end">
        <button id="btn-filtrar" class="bg-slate-700 text-white text-sm px-4 py-1.5 rounded hover:bg-slate-800 w-full">Filtrar</button>
    </div>
</div>

<div id="lista-oficios" class="space-y-3">
    <p class="text-slate-400 text-sm">Carregando oficios...</p>
</div>

<script src="assets/js/api.js"></script>
<script src="assets/js/oficios.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
