<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/includes/header.php';
?>
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Oficios Legados</h1>
        <p class="text-sm text-slate-500">Demandas migradas do sistema antigo ou de documentos em papel.</p>
    </div>
    <a href="novo_oficio_legado.php" class="bg-blue-700 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-800">+ Registrar Oficio Legado</a>
</div>

<div id="lista-oficios-legados" class="space-y-3">
    <p class="text-slate-400 text-sm">Carregando oficios legados...</p>
</div>

<script src="assets/js/api.js?v=<?= @filemtime(__DIR__ . '/assets/js/api.js') ?>"></script>
<script src="assets/js/oficios_legados.js?v=<?= @filemtime(__DIR__ . '/assets/js/oficios_legados.js') ?>"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
