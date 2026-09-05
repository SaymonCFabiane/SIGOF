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
$novaPrioridade = $dados['prioridade'] ?? null;
$novaUrgencia = $dados['urgencia'] ?? null;
$justificativa = trim($dados['justificativa'] ?? '');

if (!$novaPrioridade && !$novaUrgencia) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Informe a nova prioridade e/ou urgencia.']);
    exit;
}
if ($justificativa === '') {
    echo json_encode(['sucesso' => false, 'mensagem' => '7.2/7.3 Alteracao de prioridade/urgencia exige justificativa.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.prioridade, o.urgencia, o.responsavel_atual_id, o.criado_por_id, o.assunto, dd.secretaria_id
    FROM oficios o
    JOIN setores sd ON sd.id = o.setor_destino_id
    JOIN departamentos dd ON dd.id = sd.departamento_id
    WHERE o.id = ?
");
$stmt->execute([$oficioId]);
$oficio = $stmt->fetch();

if (!$oficio) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Oficio nao encontrado.']);
    exit;
}

$ehPlus = usuarioEhPlusDaSecretaria($pdo, $usuarioId, $oficio['secretaria_id']);
$ehResponsavel = (int) $oficio['responsavel_atual_id'] === (int) $usuarioId;
if (!$ehPlus && !$ehResponsavel) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Somente o destinatario autorizado (responsavel) ou um usuario PLUS pode alterar prioridade/urgencia.']);
    exit;
}

$pdo->beginTransaction();
try {
    $campos = [];
    $valores = [];
    $descricaoPartes = [];

    if ($novaPrioridade && $novaPrioridade !== $oficio['prioridade']) {
        $campos[] = 'prioridade = ?';
        $valores[] = $novaPrioridade;
        $descricaoPartes[] = "Prioridade: {$oficio['prioridade']} -> {$novaPrioridade}";
    }
    if ($novaUrgencia && $novaUrgencia !== $oficio['urgencia']) {
        $campos[] = 'urgencia = ?';
        $valores[] = $novaUrgencia;
        $descricaoPartes[] = "Urgencia: {$oficio['urgencia']} -> {$novaUrgencia}";
    }

    if (empty($campos)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhuma mudanca detectada.']);
        $pdo->rollBack();
        exit;
    }

    $valores[] = $oficioId;
    $pdo->prepare("UPDATE oficios SET " . implode(', ', $campos) . " WHERE id = ?")->execute($valores);

    registrarEvento($pdo, $oficioId, 'alteracao', 'Prioridade/Urgencia alterada', implode('. ', $descricaoPartes) . '.', $usuarioId, 'prioridade_urgencia', null, null, $justificativa);
    registrarNotificacao($pdo, $oficio['criado_por_id'], $oficioId, "Prioridade/urgencia da demanda \"{$oficio['assunto']}\" foi alterada.");

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao alterar prioridade/urgencia: ' . $e->getMessage()]);
}
