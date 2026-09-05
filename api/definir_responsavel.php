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
$responsavelId = (int) ($dados['responsavel_id'] ?? 0);
$colaboradores = $dados['colaboradores'] ?? [];

if (!$responsavelId) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Selecione o responsavel principal.']);
    exit;
}

$stmt = $pdo->prepare("SELECT status FROM oficios WHERE id = ?");
$stmt->execute([$oficioId]);
$statusAtual = $stmt->fetchColumn();

if (!in_array($statusAtual, ['recebida', 'aguardando_responsavel'], true)) {
    echo json_encode(['sucesso' => false, 'mensagem' => "Nao e possivel definir responsavel a partir do status atual ({$statusAtual})."]);
    exit;
}

$responsavel = carregarUsuarioAtual($pdo, $responsavelId);

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE oficios SET responsavel_atual_id = ?, status = 'aguardando_responsavel' WHERE id = ?");
    $stmt->execute([$responsavelId, $oficioId]);

    $pdo->prepare("DELETE FROM oficio_colaboradores WHERE oficio_id = ?")->execute([$oficioId]);
    $nomesColaboradores = [];
    if (is_array($colaboradores)) {
        $stmtColab = $pdo->prepare("INSERT INTO oficio_colaboradores (oficio_id, usuario_id) VALUES (?, ?)");
        foreach ($colaboradores as $colaboradorId) {
            $colaboradorId = (int) $colaboradorId;
            if ($colaboradorId > 0 && $colaboradorId !== $responsavelId) {
                $stmtColab->execute([$oficioId, $colaboradorId]);
                $colab = carregarUsuarioAtual($pdo, $colaboradorId);
                if ($colab) { $nomesColaboradores[] = $colab['nome']; }
                registrarNotificacao($pdo, $colaboradorId, $oficioId, 'Voce foi adicionado como colaborador em uma demanda.');
            }
        }
    }

    $descricao = "Responsavel principal: {$responsavel['nome']}." . ($nomesColaboradores ? ' Colaboradores: ' . implode(', ', $nomesColaboradores) . '.' : '');
    registrarEvento($pdo, $oficioId, 'alteracao', 'Responsavel definido', $descricao, $usuarioId, 'responsavel', null, $responsavel['nome']);
    registrarNotificacao($pdo, $responsavelId, $oficioId, 'Voce foi definido responsavel por uma demanda.');

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao definir responsavel: ' . $e->getMessage()]);
}
