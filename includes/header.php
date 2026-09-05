<?php
require_once __DIR__ . '/sessao.php';
require_once __DIR__ . '/../config/database.php';

$usuarioAtualId = usuarioAtualId();
$usuarioAtualNome = null;
$usuarioAtualSetor = null;

if ($usuarioAtualId) {
    $stmt = $pdo->prepare("SELECT u.nome, s.nome AS setor_nome, sec.sigla AS secretaria_sigla
                            FROM usuarios_simulados u
                            JOIN setores s ON s.id = u.setor_id
                            JOIN secretarias sec ON sec.id = s.secretaria_id
                            WHERE u.id = ?");
    $stmt->execute([$usuarioAtualId]);
    $u = $stmt->fetch();
    if ($u) {
        $usuarioAtualNome = $u['nome'];
        $usuarioAtualSetor = $u['secretaria_sigla'] . ' - ' . $u['setor_nome'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGOF - Sistema Municipal de Rastreabilidade de Demandas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white shadow">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between flex-wrap gap-2">
            <a href="oficios.php" class="font-bold text-lg tracking-tight">SIGOF</a>
            <nav class="flex items-center gap-4 text-sm">
                <a href="oficios.php" class="hover:underline">Oficios</a>
                <a href="novo_oficio.php" class="hover:underline">+ Novo Oficio</a>
                <?php if ($usuarioAtualNome): ?>
                    <span class="bg-blue-800 rounded-full px-3 py-1 text-xs">
                        Agindo como: <strong><?= htmlspecialchars($usuarioAtualNome) ?></strong> (<?= htmlspecialchars($usuarioAtualSetor) ?>)
                    </span>
                    <a href="index.php" class="text-xs underline text-blue-200">trocar</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="max-w-6xl mx-auto px-4 py-6">
