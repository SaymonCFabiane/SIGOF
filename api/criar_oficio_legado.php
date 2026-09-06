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

$camposObrigatorios = ['assunto', 'setor_destino_id', 'nome_arquivo'];
foreach ($camposObrigatorios as $campo) {
    if (empty($dados[$campo])) {
        echo json_encode(['sucesso' => false, 'mensagem' => "Campo obrigatorio ausente: {$campo}"]);
        exit;
    }
}

$statusPermitidos = ['concluida', 'em_andamento', 'aguardando_responsavel', 'cancelada'];
$status = in_array($dados['status'] ?? '', $statusPermitidos, true) ? $dados['status'] : 'concluida';

$autor = carregarUsuarioAtual($pdo, $usuarioId);
$setorOrigemId = $autor['setor_id'];
$dataOriginal = !empty($dados['data_original']) ? $dados['data_original'] : date('Y-m-d');

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("
        INSERT INTO oficios (numero_oficio, tipo, origem_legado, confidencial, assunto, descricao, observacoes, setor_origem_id, setor_destino_id, responsavel_atual_id, prioridade, urgencia, prazo_original, prazo, prazo_resposta, status, criado_por_id, criado_em)
        VALUES (?, 'oficio', 1, 0, ?, ?, ?, ?, ?, NULL, 'normal', 'nao_urgente', ?, ?, NULL, ?, ?, ?)
    ");
    $stmt->execute([
        $dados['numero_oficio'] ?: null,
        $dados['assunto'],
        $dados['descricao'] ?? null,
        $dados['observacoes'] ?? null,
        $setorOrigemId,
        $dados['setor_destino_id'],
        $dataOriginal,
        $dataOriginal,
        $status,
        $usuarioId,
        $dataOriginal . ' 00:00:00',
    ]);
    $oficioId = $pdo->lastInsertId();

    registrarEvento(
        $pdo,
        $oficioId,
        'status',
        'Oficio legado registrado',
        "Registrado por {$autor['nome']} ({$autor['secretaria_sigla']}) a partir do sistema antigo / documento em papel. Data original: " . date('d/m/Y', strtotime($dataOriginal)) . '.',
        $usuarioId
    );
    registrarEvento($pdo, $oficioId, 'anexo', 'Anexo adicionado', $dados['nome_arquivo'], $usuarioId);
    recalcularFilaSetor($pdo, $dados['setor_destino_id']);

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'dados' => ['id' => $oficioId]]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao registrar oficio legado: ' . $e->getMessage()]);
}
