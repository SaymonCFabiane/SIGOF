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
$setorId = (int) ($dados['setor_id'] ?? 0);

if (!$setorId) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Selecione o setor a envolver.']);
    exit;
}

$stmtExiste = $pdo->prepare("SELECT COUNT(*) FROM oficio_destinatarios WHERE oficio_id = ? AND setor_id = ?");
$stmtExiste->execute([$oficioId, $setorId]);
if ($stmtExiste->fetchColumn() > 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Este setor ja esta envolvido nesta demanda.']);
    exit;
}

$stmtSetor = $pdo->prepare("SELECT s.nome, sec.sigla FROM setores s JOIN departamentos d ON d.id = s.departamento_id JOIN secretarias sec ON sec.id = d.secretaria_id WHERE s.id = ?");
$stmtSetor->execute([$setorId]);
$setor = $stmtSetor->fetch();

$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO oficio_destinatarios (oficio_id, setor_id) VALUES (?, ?)")->execute([$oficioId, $setorId]);
    registrarEvento($pdo, $oficioId, 'alteracao', 'Setor envolvido', "{$setor['sigla']} - {$setor['nome']} foi envolvido na demanda (sem transferir a responsabilidade principal).", $usuarioId, 'setor_envolvido', null, $setor['sigla']);

    $stmtUsuarios = $pdo->prepare("SELECT id FROM usuarios_simulados WHERE setor_id = ? AND ativo = 1");
    $stmtUsuarios->execute([$setorId]);
    foreach ($stmtUsuarios->fetchAll(PDO::FETCH_COLUMN) as $usuarioDestino) {
        registrarNotificacao($pdo, $usuarioDestino, $oficioId, 'Seu setor foi envolvido em uma demanda.');
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao envolver setor: ' . $e->getMessage()]);
}
