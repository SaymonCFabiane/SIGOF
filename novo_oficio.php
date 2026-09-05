<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$setores = $pdo->query("
    SELECT s.id, s.nome, sec.sigla
    FROM setores s JOIN secretarias sec ON sec.id = s.secretaria_id
    ORDER BY sec.sigla
")->fetchAll();
?>
<h1 class="text-xl font-bold text-slate-800 mb-6">+ Nova Demanda / Oficio</h1>

<form id="form-novo-oficio" class="bg-white border rounded-lg p-6 space-y-4 max-w-2xl">
    <div>
        <label class="block text-sm font-medium mb-1">Tipo de Demanda</label>
        <select name="tipo" class="w-full border rounded-lg px-3 py-2">
            <option value="oficio">Oficio</option>
            <option value="solicitacao">Solicitacao</option>
            <option value="solicitacao_informacao">Solicitacao de Informacao</option>
            <option value="documento">Documento</option>
            <option value="analise">Analise</option>
            <option value="providencia">Providencia</option>
            <option value="encaminhamento">Encaminhamento</option>
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
        <label class="block text-sm font-medium mb-1">Enviar para (Secretaria / Setor destino)</label>
        <select name="setor_destino_id" required class="w-full border rounded-lg px-3 py-2">
            <option value="">Selecione...</option>
            <?php foreach ($setores as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['sigla']) ?> - <?= htmlspecialchars($s['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Prazo</label>
            <input type="date" name="prazo" required class="w-full border rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Prioridade</label>
            <select name="prioridade" class="w-full border rounded-lg px-3 py-2">
                <option value="baixa">Baixa</option>
                <option value="normal" selected>Normal</option>
                <option value="alta">Alta</option>
                <option value="critica">Critica</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Urgencia</label>
            <select name="urgencia" class="w-full border rounded-lg px-3 py-2">
                <option value="nao_urgente" selected>Nao Urgente</option>
                <option value="urgente">Urgente</option>
            </select>
        </div>
    </div>

    <div id="mensagem-erro" class="text-red-600 text-sm hidden"></div>

    <button type="submit" class="bg-blue-700 text-white px-5 py-2 rounded-lg hover:bg-blue-800">Enviar Oficio</button>
</form>

<script src="assets/js/api.js"></script>
<script src="assets/js/novo_oficio.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
