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
$oficioId = (int) ($dados['oficio_id'] ?? 0);
$mensagem = trim($dados['mensagem'] ?? '');

if ($mensagem === '') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Mensagem vazia.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO oficio_eventos (oficio_id, tipo_evento, titulo, descricao, autor_id) VALUES (?, 'comunicacao', 'Mensagem', ?, ?)");
$stmt->execute([$oficioId, $mensagem, $usuarioId]);

echo json_encode(['sucesso' => true]);
