<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/includes/header.php';
?>
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-slate-800">Central de Notificacoes</h1>
    <button id="btn-marcar-todas" class="text-xs text-blue-700 underline">Marcar todas como lidas</button>
</div>
<div id="lista-notificacoes" class="space-y-2">
    <p class="text-slate-400 text-sm">Carregando...</p>
</div>

<script src="assets/js/api.js"></script>
<script src="assets/js/notificacoes.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
