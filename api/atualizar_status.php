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
$acao = $dados['acao'] ?? '';
$observacao = trim($dados['observacao'] ?? '');
$justificativa = trim($dados['justificativa'] ?? '');

$autor = carregarUsuarioAtual($pdo, $usuarioId);

$stmt = $pdo->prepare("
    SELECT o.*, dd.secretaria_id AS destino_secretaria_id
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

$ehPlus = usuarioEhPlusDaSecretaria($pdo, $usuarioId, $oficio['destino_secretaria_id']);
$ehResponsavel = (int) $oficio['responsavel_atual_id'] === (int) $usuarioId;
$ehDoSetorDestino = (int) $autor['setor_id'] === (int) $oficio['setor_destino_id'];
$ehDemandante = (int) $oficio['criado_por_id'] === (int) $usuarioId;

function finalizarAcao(PDO $pdo, $oficioId, $novoStatus, $titulo, $descricao, $usuarioId, $justificativa = null, array $camposExtra = [])
{
    $sets = ['status = ?'];
    $valores = [$novoStatus];
    foreach ($camposExtra as $campo => $valor) {
        $sets[] = "{$campo} = ?";
        $valores[] = $valor;
    }
    $valores[] = $oficioId;
    $pdo->prepare("UPDATE oficios SET " . implode(', ', $sets) . " WHERE id = ?")->execute($valores);
    registrarEvento($pdo, $oficioId, 'status', $titulo, $descricao, $usuarioId, 'status', null, $novoStatus, $justificativa);
}

// ---------------------------------------------------------------
// Encaminhamento: transfere a responsabilidade principal (11.4)
// ---------------------------------------------------------------
if ($acao === 'encaminhar') {
    if (!$ehResponsavel && !$ehPlus && !$ehDoSetorDestino) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Voce nao tem permissao para encaminhar esta demanda.']);
        exit;
    }
    $novoSetorDestinoId = (int) ($dados['novo_setor_destino_id'] ?? 0);
    if (!$novoSetorDestinoId) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Selecione o setor de destino do encaminhamento.']);
        exit;
    }

    $stmtNovoSetor = $pdo->prepare("SELECT sec.sigla FROM setores s JOIN departamentos d ON d.id = s.departamento_id JOIN secretarias sec ON sec.id = d.secretaria_id WHERE s.id = ?");
    $stmtNovoSetor->execute([$novoSetorDestinoId]);
    $novaSigla = $stmtNovoSetor->fetchColumn();

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE oficios SET setor_origem_id = ?, setor_destino_id = ?, status = 'enviada', responsavel_atual_id = NULL WHERE id = ?")
            ->execute([$oficio['setor_destino_id'], $novoSetorDestinoId, $oficioId]);

        $pdo->prepare("DELETE FROM oficio_colaboradores WHERE oficio_id = ?")->execute([$oficioId]);

        $descricao = "Encaminhado por {$autor['nome']} para {$novaSigla}." . ($observacao !== '' ? " Observacao: {$observacao}" : '');
        registrarEvento($pdo, $oficioId, 'status', 'Oficio encaminhado', $descricao, $usuarioId, 'status', $oficio['status'], 'enviada');

        $stmtUsuarios = $pdo->prepare("SELECT id FROM usuarios_simulados WHERE setor_id = ? AND ativo = 1");
        $stmtUsuarios->execute([$novoSetorDestinoId]);
        foreach ($stmtUsuarios->fetchAll(PDO::FETCH_COLUMN) as $destinatarioId) {
            registrarNotificacao($pdo, $destinatarioId, $oficioId, "Demanda encaminhada para o seu setor: {$oficio['assunto']}");
        }

        $pdo->commit();
        echo json_encode(['sucesso' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao encaminhar: ' . $e->getMessage()]);
    }
    exit;
}

// ---------------------------------------------------------------
// Cancelamento (8.4): demandante, setor destino ou PLUS
// ---------------------------------------------------------------
if ($acao === 'cancelar') {
    if (!$ehDemandante && !$ehDoSetorDestino && !$ehPlus) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Somente o demandante, o setor responsavel ou um usuario PLUS pode cancelar.']);
        exit;
    }
    if ($justificativa === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Justificativa obrigatoria para cancelar a demanda.']);
        exit;
    }
    if (in_array($oficio['status'], ['concluida', 'cancelada'], true)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Esta demanda ja esta finalizada.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        finalizarAcao($pdo, $oficioId, 'cancelada', 'Oficio cancelado', "Cancelado por {$autor['nome']}.", $usuarioId, $justificativa);
        registrarNotificacao($pdo, $oficio['criado_por_id'], $oficioId, "Demanda \"{$oficio['assunto']}\" foi cancelada.");
        $pdo->commit();
        echo json_encode(['sucesso' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao cancelar: ' . $e->getMessage()]);
    }
    exit;
}

// ---------------------------------------------------------------
// Reabertura (8.5): somente o demandante, com justificativa
// ---------------------------------------------------------------
if ($acao === 'reabrir') {
    if (!$ehDemandante) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Somente o demandante pode solicitar a reabertura.']);
        exit;
    }
    if ($justificativa === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Justificativa obrigatoria para reabrir a demanda.']);
        exit;
    }
    if (!in_array($oficio['status'], ['concluida', 'cancelada'], true)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Somente demandas concluidas ou canceladas podem ser reabertas.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        finalizarAcao($pdo, $oficioId, 'reaberta', 'Oficio reaberto', "Reaberto por {$autor['nome']}.", $usuarioId, $justificativa);
        registrarNotificacao($pdo, $oficio['responsavel_atual_id'], $oficioId, "Demanda \"{$oficio['assunto']}\" foi reaberta pelo demandante.");
        $pdo->commit();
        echo json_encode(['sucesso' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao reabrir: ' . $e->getMessage()]);
    }
    exit;
}

// ---------------------------------------------------------------
// Conclusao (8.3): exige resposta final
// ---------------------------------------------------------------
if ($acao === 'concluir') {
    if (!in_array($oficio['status'], ['em_andamento', 'aguardando_validacao'], true)) {
        echo json_encode(['sucesso' => false, 'mensagem' => "Nao e possivel concluir a partir do status atual ({$oficio['status']})."]);
        exit;
    }
    $respostaFinal = trim($dados['resposta_final'] ?? '');
    if ($respostaFinal === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A conclusao exige uma resposta final.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        finalizarAcao($pdo, $oficioId, 'concluida', 'Oficio concluido', "Concluido por {$autor['nome']}. Resposta: {$respostaFinal}", $usuarioId, null, ['resposta_final' => $respostaFinal]);
        registrarNotificacao($pdo, $oficio['criado_por_id'], $oficioId, "Demanda \"{$oficio['assunto']}\" foi concluida.");
        $pdo->commit();
        echo json_encode(['sucesso' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao concluir: ' . $e->getMessage()]);
    }
    exit;
}

// ---------------------------------------------------------------
// Demais transicoes simples de status
// ---------------------------------------------------------------
$acoesValidas = [
    'receber'               => ['de' => ['enviada'], 'para' => 'recebida', 'titulo' => 'Oficio recebido'],
    'iniciar_analise'       => ['de' => ['aguardando_responsavel'], 'para' => 'em_analise', 'titulo' => 'Em analise'],
    'iniciar_andamento'     => ['de' => ['em_analise', 'aguardando_responsavel', 'aguardando_informacao', 'reaberta'], 'para' => 'em_andamento', 'titulo' => 'Em andamento'],
    'solicitar_informacao'  => ['de' => ['em_analise', 'em_andamento', 'aguardando_responsavel'], 'para' => 'aguardando_informacao', 'titulo' => 'Aguardando informacao'],
    'responder_informacao'  => ['de' => ['aguardando_informacao'], 'para' => 'em_andamento', 'titulo' => 'Informacao respondida'],
    'enviar_validacao'      => ['de' => ['em_andamento'], 'para' => 'aguardando_validacao', 'titulo' => 'Aguardando validacao'],
    'devolver'              => ['de' => ['recebida', 'em_analise', 'em_andamento', 'aguardando_responsavel', 'aguardando_informacao', 'aguardando_validacao'], 'para' => 'devolvida', 'titulo' => 'Oficio devolvido'],
];

if (!isset($acoesValidas[$acao])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acao invalida.']);
    exit;
}

$regra = $acoesValidas[$acao];

if (!in_array($oficio['status'], $regra['de'], true)) {
    echo json_encode(['sucesso' => false, 'mensagem' => "Nao e possivel executar essa acao a partir do status atual ({$oficio['status']})."]);
    exit;
}

if ($acao === 'devolver' && $observacao === '') {
    echo json_encode(['sucesso' => false, 'mensagem' => '8.2 Devolucao exige justificativa.']);
    exit;
}

$pdo->beginTransaction();
try {
    $descricaoEvento = "{$regra['titulo']} por {$autor['nome']}." . ($observacao !== '' ? " Observacao: {$observacao}" : '');
    finalizarAcao($pdo, $oficioId, $regra['para'], $regra['titulo'], $descricaoEvento, $usuarioId, $acao === 'devolver' ? $observacao : null);

    if ($acao === 'devolver') {
        registrarNotificacao($pdo, $oficio['criado_por_id'], $oficioId, "Demanda \"{$oficio['assunto']}\" foi devolvida.");
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar status: ' . $e->getMessage()]);
}
