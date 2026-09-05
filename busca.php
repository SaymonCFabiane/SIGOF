<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/includes/header.php';
?>
<h1 class="text-xl font-bold text-slate-800 mb-2">Busca por Competencia</h1>
<p class="text-sm text-slate-500 mb-6">Pesquise por palavra-chave (ex: "transporte escolar") para descobrir a Secretaria/Setor responsavel.</p>

<div class="flex gap-2 mb-6 max-w-xl">
    <input id="input-busca" type="text" placeholder="Ex: material escolar, medicamentos, contratos..." class="flex-1 border rounded-lg px-3 py-2">
    <button id="btn-buscar" class="bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-800">Buscar</button>
</div>

<div id="resultados-busca" class="space-y-3"></div>

<script src="assets/js/api.js"></script>
<script src="assets/js/busca.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
