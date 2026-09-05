<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes.php';

$usuarioId = usuarioAtualId();
if (!$usuarioId) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum ator selecionado.']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$oficioId = (int) ($dados['oficio_id'] ?? 0);
$mensagem = trim($dados['mensagem'] ?? '');
$formal = !empty($dados['formal']);

if ($mensagem === '') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Mensagem vazia.']);
    exit;
}

$titulo = $formal ? 'Comunicacao Formal' : 'Mensagem';
registrarEvento($pdo, $oficioId, 'comunicacao', $titulo, $mensagem, $usuarioId);

$stmt = $pdo->prepare("SELECT criado_por_id, responsavel_atual_id, assunto FROM oficios WHERE id = ?");
$stmt->execute([$oficioId]);
$oficio = $stmt->fetch();

$participantes = array_unique(array_filter([$oficio['criado_por_id'], $oficio['responsavel_atual_id']]));
$stmtColab = $pdo->prepare("SELECT usuario_id FROM oficio_colaboradores WHERE oficio_id = ?");
$stmtColab->execute([$oficioId]);
$participantes = array_merge($participantes, $stmtColab->fetchAll(PDO::FETCH_COLUMN));

foreach (array_unique($participantes) as $participanteId) {
    if ((int) $participanteId !== (int) $usuarioId) {
        registrarNotificacao($pdo, $participanteId, $oficioId, "Nova interacao na demanda \"{$oficio['assunto']}\".");
    }
}

echo json_encode(['sucesso' => true]);
