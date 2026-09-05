<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/includes/header.php';
?>
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <h1 class="text-xl font-bold text-slate-800">Oficios</h1>
    <a href="novo_oficio.php" class="bg-blue-700 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-800">+ Nova Demanda</a>
</div>

<div id="lista-oficios" class="space-y-3">
    <p class="text-slate-400 text-sm">Carregando oficios...</p>
</div>

<script src="assets/js/api.js"></script>
<script src="assets/js/oficios.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
