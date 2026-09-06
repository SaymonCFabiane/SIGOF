<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-2xl mx-auto">
    <h1 class="text-xl font-bold text-slate-800 mb-2 text-center">Busca por Competencia</h1>
    <p class="text-sm text-slate-500 mb-6 text-center">Pesquise por palavra-chave (ex: "transporte escolar") para descobrir a Secretaria/Setor responsavel.</p>

    <div class="flex gap-2 mb-6">
        <input id="input-busca" type="text" placeholder="Ex: material escolar, medicamentos, contratos..." class="flex-1 border rounded-lg px-3 py-2">
        <button id="btn-buscar" class="bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-800">Buscar</button>
    </div>

    <div id="resultados-busca" class="space-y-3"></div>
</div>

<script src="assets/js/api.js?v=<?= @filemtime(__DIR__ . '/assets/js/api.js') ?>"></script>
<script src="assets/js/busca.js?v=<?= @filemtime(__DIR__ . '/assets/js/busca.js') ?>"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
