<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/includes/header.php';
?>
<h1 class="text-xl font-bold text-slate-800 mb-6">Central de Notificacoes</h1>
<div id="lista-notificacoes" class="space-y-2">
    <p class="text-slate-400 text-sm">Carregando...</p>
</div>

<script src="assets/js/api.js?v=<?= @filemtime(__DIR__ . '/assets/js/api.js') ?>"></script>
<script src="assets/js/notificacoes.js?v=<?= @filemtime(__DIR__ . '/assets/js/notificacoes.js') ?>"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
