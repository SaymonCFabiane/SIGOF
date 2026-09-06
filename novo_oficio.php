<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$setores = $pdo->query("
    SELECT s.id, s.nome, sec.sigla
    FROM setores s
    JOIN departamentos d ON d.id = s.departamento_id
    JOIN secretarias sec ON sec.id = d.secretaria_id
    ORDER BY sec.sigla, s.nome
")->fetchAll();

$destinoPreSelecionado = (int) ($_GET['destino_setor'] ?? 0);
$tipoPreSelecionado = $_GET['tipo'] ?? 'oficio';
?>
<div class="max-w-2xl mx-auto">
<h1 class="text-xl font-bold text-slate-800 mb-6 text-center">+ Nova Demanda / Oficio</h1>

<form id="form-novo-oficio" class="bg-white border rounded-lg p-6 space-y-4">
    <div>
        <label class="block text-sm font-medium mb-1">Tipo de Demanda</label>
        <select name="tipo" id="campo-tipo" class="w-full border rounded-lg px-3 py-2">
            <option value="oficio" <?= $tipoPreSelecionado === 'oficio' ? 'selected' : '' ?>>Oficio</option>
            <option value="solicitacao" <?= $tipoPreSelecionado === 'solicitacao' ? 'selected' : '' ?>>Solicitacao</option>
            <option value="solicitacao_informacao" <?= $tipoPreSelecionado === 'solicitacao_informacao' ? 'selected' : '' ?>>Solicitacao de Informacao</option>
            <option value="solicitacao_documento" <?= $tipoPreSelecionado === 'solicitacao_documento' ? 'selected' : '' ?>>Solicitacao de Documento</option>
            <option value="analise">Analise</option>
            <option value="providencia">Providencia</option>
            <option value="encaminhamento">Encaminhamento</option>
            <option value="denuncia">Denuncia (identidade fica confidencial)</option>
            <option value="outro">Outro</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Numero do Oficio (opcional)</label>
        <input type="text" name="numero_oficio" placeholder="Ex: 123/2026" class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Assunto</label>
        <input type="text" name="assunto" required class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Descricao</label>
        <textarea name="descricao" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Enviar para (Secretaria / Setor destino principal)</label>
        <select name="setor_destino_id" required class="w-full border rounded-lg px-3 py-2">
            <option value="">Selecione...</option>
            <?php foreach ($setores as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $destinoPreSelecionado ? 'selected' : '' ?>><?= htmlspecialchars($s['sigla']) ?> - <?= htmlspecialchars($s['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Envolver outros setores tambem (opcional)</label>
        <div class="flex flex-wrap gap-3 border rounded-lg px-3 py-2">
            <?php foreach ($setores as $s): ?>
                <label class="flex items-center gap-1 text-xs">
                    <input type="checkbox" name="setores_envolvidos[]" value="<?= $s['id'] ?>">
                    <?= htmlspecialchars($s['sigla']) ?> - <?= htmlspecialchars($s['nome']) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Anexos (opcional &mdash; nome do(s) arquivo(s), separados por virgula)</label>
        <input type="text" name="anexos" placeholder="Ex: oficio_123.pdf, planilha_custos.xlsx" class="w-full border rounded-lg px-3 py-2">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Prazo</label>
            <input type="date" name="prazo" required class="w-full border rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Prazo de resposta (opcional)</label>
            <input type="date" name="prazo_resposta" class="w-full border rounded-lg px-3 py-2">
        </div>
    </div>

    <p class="text-xs text-slate-400 italic">Prioridade e urgencia nao sao definidas aqui &mdash; quem receber a demanda ira classifica-las no momento do recebimento.</p>

    <div>
        <label class="block text-sm font-medium mb-1">Observacoes (opcional)</label>
        <textarea name="observacoes" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
    </div>

    <div id="mensagem-erro" class="text-red-600 text-sm hidden"></div>

    <div class="flex gap-3">
        <button type="submit" data-acao="enviar" class="bg-blue-700 text-white px-5 py-2 rounded-lg hover:bg-blue-800">Revisar e Enviar</button>
        <button type="submit" data-acao="rascunho" class="bg-slate-200 text-slate-700 px-5 py-2 rounded-lg hover:bg-slate-300">Salvar como Rascunho</button>
    </div>
</form>
</div>

<script src="assets/js/api.js?v=<?= @filemtime(__DIR__ . '/assets/js/api.js') ?>"></script>
<script src="assets/js/novo_oficio.js?v=<?= @filemtime(__DIR__ . '/assets/js/novo_oficio.js') ?>"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
