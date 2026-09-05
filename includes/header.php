<?php
require_once __DIR__ . '/sessao.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/funcoes.php';

$usuarioAtualId = usuarioAtualId();
$usuarioAtual = $usuarioAtualId ? carregarUsuarioAtual($pdo, $usuarioAtualId) : null;

$naoLidas = 0;
if ($usuarioAtual) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0");
    $stmt->execute([$usuarioAtual['id']]);
    $naoLidas = (int) $stmt->fetchColumn();
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
            <nav class="flex items-center gap-4 text-sm flex-wrap">
                <a href="oficios.php" class="hover:underline">Oficios</a>
                <a href="novo_oficio.php" class="hover:underline">+ Novo Oficio</a>
                <a href="busca.php" class="hover:underline">Buscar</a>
                <a href="dashboard.php" class="hover:underline">Dashboard</a>
                <a href="notificacoes.php" class="hover:underline relative">
                    Notificacoes
                    <?php if ($naoLidas > 0): ?>
                        <span class="absolute -top-2 -right-3 bg-red-600 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center"><?= $naoLidas > 9 ? '9+' : $naoLidas ?></span>
                    <?php endif; ?>
                </a>
                <?php if ($usuarioAtual): ?>
                    <span class="bg-blue-800 rounded-full px-3 py-1 text-xs">
                        <strong><?= htmlspecialchars($usuarioAtual['nome']) ?></strong>
                        &bull; <?= htmlspecialchars(labelPerfil($usuarioAtual['perfil'])) ?>
                        <?php if ($usuarioAtual['nivel_plus']): ?>
                            &bull; PLUS <?= htmlspecialchars(ucfirst($usuarioAtual['nivel_plus'])) ?>
                        <?php endif; ?>
                        (<?= htmlspecialchars($usuarioAtual['secretaria_sigla']) ?>)
                    </span>
                    <a href="index.php" class="text-xs underline text-blue-200">trocar</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="max-w-6xl mx-auto px-4 py-6">
