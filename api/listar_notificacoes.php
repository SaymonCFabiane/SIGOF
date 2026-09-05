<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../config/database.php';

$usuarioId = usuarioAtualId();
if (!$usuarioId) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum ator selecionado.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT n.*, o.assunto AS oficio_assunto
    FROM notificacoes n
    LEFT JOIN oficios o ON o.id = n.oficio_id
    WHERE n.usuario_id = ?
    ORDER BY n.criado_em DESC
    LIMIT 100
");
$stmt->execute([$usuarioId]);
$notificacoes = $stmt->fetchAll();

echo json_encode(['sucesso' => true, 'dados' => $notificacoes]);
