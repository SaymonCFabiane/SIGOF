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
?>
<div class="max-w-2xl mx-auto">
<h1 class="text-xl font-bold text-slate-800 mb-1 text-center">+ Registrar Oficio Legado</h1>
<p class="text-sm text-slate-500 mb-6 text-center">Use esta tela para migrar demandas do sistema antigo ou documentos em papel para o SIGOF, anexando o arquivo digitalizado ou exportado como comprovante.</p>

<form id="form-oficio-legado" class="bg-white border rounded-lg p-6 space-y-4">
    <div>
        <label class="block text-sm font-medium mb-1">Assunto</label>
        <input type="text" name="assunto" required class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Numero do documento original (opcional)</label>
        <input type="text" name="numero_oficio" placeholder="Ex: 045/2023 (sistema antigo)" class="w-full border rounded-lg px-3 py-2">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Data original do documento</label>
            <input type="date" name="data_original" class="w-full border rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Setor responsavel</label>
            <select name="setor_destino_id" required class="w-full border rounded-lg px-3 py-2">
                <option value="">Selecione...</option>
                <?php foreach ($setores as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['sigla']) ?> - <?= htmlspecialchars($s['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Status atual</label>
        <select name="status" class="w-full border rounded-lg px-3 py-2">
            <option value="concluida">Concluida</option>
            <option value="em_andamento">Em Andamento</option>
            <option value="aguardando_responsavel">Aguardando Responsavel</option>
            <option value="cancelada">Cancelada</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Descricao (opcional)</label>
        <textarea name="descricao" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Observacoes sobre a migracao (opcional)</label>
        <textarea name="observacoes" rows="2" class="w-full border rounded-lg px-3 py-2" placeholder="Ex: migrado do sistema antigo XYZ / documento em papel digitalizado"></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Documento do sistema antigo ou digitalizado</label>
        <input id="input-arquivo-legado" name="arquivo" type="file" required class="w-full border rounded-lg px-3 py-2 text-sm text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-slate-100 file:text-slate-700 file:text-xs">
    </div>

    <div id="mensagem-erro" class="text-red-600 text-sm hidden"></div>

    <div class="flex gap-3">
        <button type="submit" class="bg-blue-700 text-white px-5 py-2 rounded-lg hover:bg-blue-800">Registrar Oficio Legado</button>
    </div>
</form>
</div>

<script src="assets/js/api.js?v=<?= @filemtime(__DIR__ . '/assets/js/api.js') ?>"></script>
<script src="assets/js/novo_oficio_legado.js?v=<?= @filemtime(__DIR__ . '/assets/js/novo_oficio_legado.js') ?>"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
