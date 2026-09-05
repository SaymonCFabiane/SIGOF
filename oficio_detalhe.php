<?php
require_once __DIR__ . '/includes/sessao.php';
if (!usuarioAtualId()) { header('Location: index.php'); exit; }
require_once __DIR__ . '/includes/header.php';
$oficioId = (int) ($_GET['id'] ?? 0);
?>
<div id="detalhe-oficio" data-oficio-id="<?= $oficioId ?>">
    <p class="text-slate-400 text-sm">Carregando...</p>
</div>

<script src="assets/js/api.js"></script>
<script src="assets/js/oficio_detalhe.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
