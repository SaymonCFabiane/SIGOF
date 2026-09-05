<?php
require_once __DIR__ . '/includes/sessao.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id'])) {
    $_SESSION['usuario_atual_id'] = (int) $_POST['usuario_id'];
    header('Location: dashboard.php');
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT u.id, u.nome, u.perfil, u.nivel_plus, s.nome AS setor_nome, sec.sigla AS secretaria_sigla
        FROM usuarios_simulados u
        JOIN setores s ON s.id = u.setor_id
        JOIN departamentos d ON d.id = s.departamento_id
        JOIN secretarias sec ON sec.id = d.secretaria_id
        WHERE u.ativo = 1
        ORDER BY sec.sigla, u.nome
    ");
    $usuarios = $stmt->fetchAll();
} catch (\Throwable $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>SIGOF - Erro</title></head><body style="font-family: sans-serif; padding: 40px; background:#fef2f2;">';
    echo '<h1 style="color:#991b1b;">Erro ao carregar o SIGOF</h1>';
    echo '<p>Nao foi possivel consultar o banco de dados. Verifique se o banco <code>sigof</code> foi importado (arquivo <code>database/schema.sql</code>) e se o MySQL esta rodando.</p>';
    echo '<pre style="background:#fff; border:1px solid #fca5a5; padding:12px; border-radius:8px; white-space:pre-wrap;">' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '</body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGOF - Selecionar Ator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center py-10">
    <div class="bg-white shadow rounded-xl p-8 w-full max-w-lg">
        <h1 class="text-2xl font-bold text-blue-900 mb-1">SIGOF</h1>
        <p class="text-slate-500 text-sm mb-6">Prototipo &mdash; selecione quem voce e para simular a demonstracao (sem login/senha real).</p>
        <form method="POST" class="space-y-3">
            <?php foreach ($usuarios as $u): ?>
                <button type="submit" name="usuario_id" value="<?= $u['id'] ?>"
                        class="w-full text-left border rounded-lg px-4 py-3 hover:bg-blue-50 hover:border-blue-400 transition flex items-center justify-between">
                    <span>
                        <span class="block font-semibold"><?= htmlspecialchars($u['nome']) ?></span>
                        <span class="block text-xs text-slate-500"><?= htmlspecialchars($u['secretaria_sigla']) ?> - <?= htmlspecialchars($u['setor_nome']) ?></span>
                    </span>
                    <span class="text-xs text-right text-slate-400">
                        <?= htmlspecialchars(labelPerfil($u['perfil'])) ?>
                        <?php if ($u['nivel_plus']): ?><br><span class="text-amber-600 font-semibold">PLUS <?= htmlspecialchars(ucfirst($u['nivel_plus'])) ?></span><?php endif; ?>
                    </span>
                </button>
            <?php endforeach; ?>
        </form>
    </div>
</body>
</html>
