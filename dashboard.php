<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/includes/header.php';
?>
<div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Dashboard</h1>
    <p class="text-sm text-slate-500">Visao geral adaptada ao seu perfil e nivel de acesso.</p>
</div>
<div id="dashboard-conteudo">
    <p class="text-slate-400 text-sm">Carregando indicadores...</p>
</div>

<script src="assets/js/api.js"></script>
<script src="assets/js/dashboard.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
