<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../config/database.php';

$usuarioId = usuarioAtualId();
if (!$usuarioId) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum ator selecionado.']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];

if (!empty($dados['todas'])) {
    $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
} else {
    $notificacaoId = (int) ($dados['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$notificacaoId, $usuarioId]);
}

echo json_encode(['sucesso' => true]);
