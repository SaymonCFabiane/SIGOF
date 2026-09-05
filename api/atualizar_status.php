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
$acao = $dados['acao'] ?? '';
$observacao = trim($dados['observacao'] ?? '');

$stmtAutor = $pdo->prepare("SELECT nome FROM usuarios_simulados WHERE id = ?");
$stmtAutor->execute([$usuarioId]);
$nomeAutor = $stmtAutor->fetchColumn();

// Encaminhamento e tratado a parte: muda o setor de destino do oficio.
if ($acao === 'encaminhar') {
    $novoSetorDestinoId = (int) ($dados['novo_setor_destino_id'] ?? 0);
    if (!$novoSetorDestinoId) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Selecione o setor de destino do encaminhamento.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT setor_destino_id FROM oficios WHERE id = ?");
    $stmt->execute([$oficioId]);
    $setorAtualId = $stmt->fetchColumn();

    $pdo->beginTransaction();
    try {
        $stmtUpdate = $pdo->prepare("UPDATE oficios SET setor_origem_id = ?, setor_destino_id = ?, status = 'enviada', responsavel_atual_id = NULL WHERE id = ?");
        $stmtUpdate->execute([$setorAtualId, $novoSetorDestinoId, $oficioId]);

        $stmtNovoSetor = $pdo->prepare("SELECT sec.sigla FROM setores s JOIN secretarias sec ON sec.id = s.secretaria_id WHERE s.id = ?");
        $stmtNovoSetor->execute([$novoSetorDestinoId]);
        $novaSigla = $stmtNovoSetor->fetchColumn();

        $descricao = "Encaminhado por {$nomeAutor} para {$novaSigla}." . ($observacao !== '' ? " Observacao: {$observacao}" : '');
        $stmtEvento = $pdo->prepare("INSERT INTO oficio_eventos (oficio_id, tipo_evento, titulo, descricao, autor_id) VALUES (?, 'status', 'Oficio encaminhado', ?, ?)");
        $stmtEvento->execute([$oficioId, $descricao, $usuarioId]);

        $pdo->commit();
        echo json_encode(['sucesso' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao encaminhar: ' . $e->getMessage()]);
    }
    exit;
}

$acoesValidas = [
    'receber'               => ['de' => ['enviada'], 'para' => 'recebida', 'titulo' => 'Oficio recebido'],
    'iniciar_andamento'     => ['de' => ['recebida', 'aguardando_responsavel', 'aguardando_informacao'], 'para' => 'em_andamento', 'titulo' => 'Em analise / andamento'],
    'solicitar_informacao'  => ['de' => ['recebida', 'em_andamento', 'aguardando_responsavel'], 'para' => 'aguardando_informacao', 'titulo' => 'Aguardando informacao'],
    'devolver'              => ['de' => ['recebida', 'em_andamento', 'aguardando_responsavel', 'aguardando_informacao'], 'para' => 'devolvida', 'titulo' => 'Oficio devolvido'],
    'concluir'              => ['de' => ['em_andamento'], 'para' => 'concluida', 'titulo' => 'Oficio concluido'],
];

if (!isset($acoesValidas[$acao])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acao invalida.']);
    exit;
}

$regra = $acoesValidas[$acao];

$stmt = $pdo->prepare("SELECT status FROM oficios WHERE id = ?");
$stmt->execute([$oficioId]);
$statusAtual = $stmt->fetchColumn();

if (!in_array($statusAtual, $regra['de'], true)) {
    echo json_encode(['sucesso' => false, 'mensagem' => "Nao e possivel executar essa acao a partir do status atual ({$statusAtual})."]);
    exit;
}

if ($acao === 'devolver' && $observacao === '') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Justificativa obrigatoria para devolver o oficio.']);
    exit;
}

$pdo->beginTransaction();
try {
    $stmtUpdate = $pdo->prepare("UPDATE oficios SET status = ?, responsavel_atual_id = ? WHERE id = ?");
    $stmtUpdate->execute([$regra['para'], $usuarioId, $oficioId]);

    $descricaoEvento = "{$regra['titulo']} por {$nomeAutor}." . ($observacao !== '' ? " Observacao: {$observacao}" : '');
    $stmtEvento = $pdo->prepare("INSERT INTO oficio_eventos (oficio_id, tipo_evento, titulo, descricao, autor_id) VALUES (?, 'status', ?, ?, ?)");
    $stmtEvento->execute([$oficioId, $regra['titulo'], $descricaoEvento, $usuarioId]);

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar status: ' . $e->getMessage()]);
}
