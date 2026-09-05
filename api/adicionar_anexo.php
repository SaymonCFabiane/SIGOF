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
$nomeArquivo = trim($dados['nome_arquivo'] ?? '');

if ($nomeArquivo === '') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Informe o nome do arquivo.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO oficio_eventos (oficio_id, tipo_evento, titulo, descricao, autor_id) VALUES (?, 'anexo', 'Anexo adicionado', ?, ?)");
$stmt->execute([$oficioId, $nomeArquivo, $usuarioId]);

echo json_encode(['sucesso' => true]);
