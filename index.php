<?php
require_once __DIR__ . '/includes/sessao.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id'])) {
    $_SESSION['usuario_atual_id'] = (int) $_POST['usuario_id'];
    header('Location: oficios.php');
    exit;
}

$stmt = $pdo->query("SELECT u.id, u.nome, s.nome AS setor_nome, sec.sigla AS secretaria_sigla
                      FROM usuarios_simulados u
                      JOIN setores s ON s.id = u.setor_id
                      JOIN secretarias sec ON sec.id = s.secretaria_id
                      ORDER BY sec.sigla");
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGOF - Selecionar Ator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center">
    <div class="bg-white shadow rounded-xl p-8 w-full max-w-md">
        <h1 class="text-2xl font-bold text-blue-900 mb-1">SIGOF</h1>
        <p class="text-slate-500 text-sm mb-6">Prototipo &mdash; selecione quem voce e para simular a demonstracao (sem login real).</p>
        <form method="POST" class="space-y-3">
            <?php foreach ($usuarios as $u): ?>
                <button type="submit" name="usuario_id" value="<?= $u['id'] ?>"
                        class="w-full text-left border rounded-lg px-4 py-3 hover:bg-blue-50 hover:border-blue-400 transition">
                    <span class="block font-semibold"><?= htmlspecialchars($u['nome']) ?></span>
                    <span class="block text-xs text-slate-500"><?= htmlspecialchars($u['secretaria_sigla']) ?> - <?= htmlspecialchars($u['setor_nome']) ?></span>
                </button>
            <?php endforeach; ?>
        </form>
    </div>
</body>
</html>
