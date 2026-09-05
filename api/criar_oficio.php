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

$camposObrigatorios = ['assunto', 'setor_destino_id', 'prazo', 'tipo', 'prioridade', 'urgencia'];
foreach ($camposObrigatorios as $campo) {
    if (empty($dados[$campo])) {
        echo json_encode(['sucesso' => false, 'mensagem' => "Campo obrigatorio ausente: {$campo}"]);
        exit;
    }
}

$stmt = $pdo->prepare("SELECT setor_id FROM usuarios_simulados WHERE id = ?");
$stmt->execute([$usuarioId]);
$setorOrigemId = $stmt->fetchColumn();

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("
        INSERT INTO oficios (numero_oficio, tipo, assunto, descricao, setor_origem_id, setor_destino_id, responsavel_atual_id, prioridade, urgencia, prazo, status, criado_por_id)
        VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, 'enviada', ?)
    ");
    $stmt->execute([
        $dados['numero_oficio'] ?: null,
        $dados['tipo'],
        $dados['assunto'],
        $dados['descricao'] ?? null,
        $setorOrigemId,
        $dados['setor_destino_id'],
        $dados['prioridade'],
        $dados['urgencia'],
        $dados['prazo'],
        $usuarioId,
    ]);
    $oficioId = $pdo->lastInsertId();

    $stmtNome = $pdo->prepare("SELECT nome FROM usuarios_simulados WHERE id = ?");
    $stmtNome->execute([$usuarioId]);
    $nomeAutor = $stmtNome->fetchColumn();

    $stmtEvento = $pdo->prepare("
        INSERT INTO oficio_eventos (oficio_id, tipo_evento, titulo, descricao, autor_id)
        VALUES (?, 'status', 'Oficio criado e enviado', ?, ?)
    ");
    $stmtEvento->execute([$oficioId, "Oficio criado por {$nomeAutor} e enviado.", $usuarioId]);

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'dados' => ['id' => $oficioId]]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar oficio: ' . $e->getMessage()]);
}
