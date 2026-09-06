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
    <header class="bg-white text-slate-700 shadow-sm border-b border-slate-200">
        <div class="w-full px-6 py-2 flex items-center justify-between gap-4">
            <a href="dashboard.php" class="flex items-center shrink-0">
                <img src="includes/img/sigov.jpeg" alt="SIGOF" class="h-16 w-auto">
            </a>
            <nav class="flex items-center gap-5 text-sm flex-wrap justify-end">
                <a href="busca.php" class="flex items-center justify-center text-slate-600 hover:text-blue-700 transition-colors">Buscar</a>
                <a href="dashboard.php" class="text-slate-600 hover:text-blue-700 transition-colors">Dashboard</a>
                <a href="novo_oficio.php" class="flex items-center justify-center text-slate-600 hover:text-blue-700 transition-colors">+ Novo Oficio</a>
                <a href="oficios.php" class="text-slate-600 hover:text-blue-700 transition-colors">Oficios</a>
                <a href="oficios_legados.php" class="text-slate-600 hover:text-blue-700 transition-colors">Oficios Legados</a>
                <a href="notificacoes.php" class="relative text-slate-500 hover:text-blue-700 transition-colors" title="Notificacoes">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3a5 5 0 0 0-5 5v2.28c0 .69-.22 1.36-.63 1.92L5 14.5c-.6.8 0 1.94 1 1.94h12c1 0 1.6-1.14 1-1.94l-1.37-2.3a3.2 3.2 0 0 1-.63-1.92V8a5 5 0 0 0-5-5Z"></path>
                        <path d="M9.5 18.5a2.5 2.5 0 0 0 5 0"></path>
                    </svg>
                    <?php if ($naoLidas > 0): ?>
                        <span class="absolute -top-1.5 -right-1.5 bg-red-600 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center"><?= $naoLidas > 9 ? '9+' : $naoLidas ?></span>
                    <?php endif; ?>
                </a>
                <?php if ($usuarioAtual): ?>
                    <span class="bg-slate-100 rounded-full px-3 py-1 text-xs text-slate-700">
                        <strong><?= htmlspecialchars($usuarioAtual['nome']) ?></strong>
                        &bull; <?= htmlspecialchars(labelPerfil($usuarioAtual['perfil'])) ?>
                        <?php if ($usuarioAtual['nivel_plus']): ?>
                            &bull; PLUS <?= htmlspecialchars(ucfirst($usuarioAtual['nivel_plus'])) ?>
                        <?php endif; ?>
                        (<?= htmlspecialchars($usuarioAtual['secretaria_sigla']) ?>)
                        <a href="index.php" class="underline text-blue-600 ml-1">trocar</a>
                    </span>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="max-w-6xl mx-auto px-4 py-6">
