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

$stmt = $pdo->prepare("SELECT * FROM oficios WHERE id = ?");
$stmt->execute([$oficioId]);
$oficio = $stmt->fetch();

if (!$oficio || $oficio['status'] !== 'rascunho' || (int) $oficio['criado_por_id'] !== (int) $usuarioId) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Este rascunho nao pode ser enviado por voce.']);
    exit;
}

$autor = carregarUsuarioAtual($pdo, $usuarioId);

$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE oficios SET status = 'enviada' WHERE id = ?")->execute([$oficioId]);
    registrarEvento($pdo, $oficioId, 'status', 'Oficio enviado', "Rascunho enviado por {$autor['nome']}.", $usuarioId, 'status', 'rascunho', 'enviada');

    $stmtUsuariosDestino = $pdo->prepare("SELECT id FROM usuarios_simulados WHERE setor_id = ? AND ativo = 1");
    $stmtUsuariosDestino->execute([$oficio['setor_destino_id']]);
    foreach ($stmtUsuariosDestino->fetchAll(PDO::FETCH_COLUMN) as $destinatarioId) {
        registrarNotificacao($pdo, $destinatarioId, $oficioId, "Nova demanda recebida: {$oficio['assunto']}");
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao enviar rascunho: ' . $e->getMessage()]);
}
