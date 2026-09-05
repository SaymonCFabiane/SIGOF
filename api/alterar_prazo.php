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
$novoPrazo = $dados['novo_prazo'] ?? '';
$justificativa = trim($dados['justificativa'] ?? '');

if ($novoPrazo === '' || $justificativa === '') {
    echo json_encode(['sucesso' => false, 'mensagem' => '6.2 Alteracao de prazo exige novo prazo e justificativa.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.prazo, dd.secretaria_id
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

if (!usuarioEhPlusDaSecretaria($pdo, $usuarioId, $oficio['secretaria_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Somente um usuario PLUS da secretaria responsavel pode alterar o prazo.']);
    exit;
}

$prazoAnterior = $oficio['prazo'];

$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE oficios SET prazo = ? WHERE id = ?")->execute([$novoPrazo, $oficioId]);
    registrarEvento(
        $pdo, $oficioId, 'alteracao', 'Prazo alterado',
        "Prazo alterado de " . date('d/m/Y', strtotime($prazoAnterior)) . " para " . date('d/m/Y', strtotime($novoPrazo)) . ".",
        $usuarioId, 'prazo', $prazoAnterior, $novoPrazo, $justificativa
    );

    $stmtDemandante = $pdo->prepare("SELECT criado_por_id, assunto FROM oficios WHERE id = ?");
    $stmtDemandante->execute([$oficioId]);
    $info = $stmtDemandante->fetch();
    registrarNotificacao($pdo, $info['criado_por_id'], $oficioId, "Prazo da demanda \"{$info['assunto']}\" foi alterado.");

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao alterar prazo: ' . $e->getMessage()]);
}
