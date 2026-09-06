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
$acao = $dados['acao'] ?? 'enviar'; // 'enviar' ou 'rascunho'

$camposObrigatorios = ['assunto', 'setor_destino_id', 'prazo', 'tipo'];
foreach ($camposObrigatorios as $campo) {
    if (empty($dados[$campo])) {
        echo json_encode(['sucesso' => false, 'mensagem' => "Campo obrigatorio ausente: {$campo}"]);
        exit;
    }
}

$autor = carregarUsuarioAtual($pdo, $usuarioId);
$setorOrigemId = $autor['setor_id'];
$confidencial = ($dados['tipo'] === 'denuncia') ? 1 : 0;
$statusInicial = $acao === 'rascunho' ? 'rascunho' : 'enviada';

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("
        INSERT INTO oficios (numero_oficio, tipo, confidencial, assunto, descricao, observacoes, setor_origem_id, setor_destino_id, responsavel_atual_id, prioridade, urgencia, prazo_original, prazo, prazo_resposta, status, criado_por_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $dados['numero_oficio'] ?: null,
        $dados['tipo'],
        $confidencial,
        $dados['assunto'],
        $dados['descricao'] ?? null,
        $dados['observacoes'] ?? null,
        $setorOrigemId,
        $dados['setor_destino_id'],
        'normal', // prioridade inicial neutra - quem recebe classifica de verdade
        'nao_urgente', // urgencia inicial neutra - quem recebe classifica de verdade
        $dados['prazo'],
        $dados['prazo'],
        $dados['prazo_resposta'] ?: null,
        $statusInicial,
        $usuarioId,
    ]);
    $oficioId = $pdo->lastInsertId();

    // Setores adicionais envolvidos (destino multiplo / 5.2)
    if (!empty($dados['setores_envolvidos']) && is_array($dados['setores_envolvidos'])) {
        $stmtDest = $pdo->prepare("INSERT INTO oficio_destinatarios (oficio_id, setor_id) VALUES (?, ?)");
        foreach ($dados['setores_envolvidos'] as $setorId) {
            $setorId = (int) $setorId;
            if ($setorId > 0 && $setorId !== (int) $dados['setor_destino_id']) {
                $stmtDest->execute([$oficioId, $setorId]);
            }
        }
    }

    if ($statusInicial === 'rascunho') {
        registrarEvento($pdo, $oficioId, 'status', 'Rascunho salvo', 'Rascunho criado por ' . $autor['nome'] . '. Ainda nao enviado.', $usuarioId);
    } else {
        $anexosTexto = trim($dados['anexos'] ?? '');
        registrarEvento($pdo, $oficioId, 'status', 'Oficio criado e enviado', "Oficio criado por {$autor['nome']} ({$autor['secretaria_sigla']}) e enviado.", $usuarioId);
        if ($anexosTexto !== '') {
            foreach (array_filter(array_map('trim', explode(',', $anexosTexto))) as $nomeArquivo) {
                registrarEvento($pdo, $oficioId, 'anexo', 'Anexo adicionado', $nomeArquivo, $usuarioId);
            }
        }
        // Notifica o setor destino (todos os usuarios ativos daquele setor)
        $stmtUsuariosDestino = $pdo->prepare("SELECT id FROM usuarios_simulados WHERE setor_id = ? AND ativo = 1");
        $stmtUsuariosDestino->execute([$dados['setor_destino_id']]);
        foreach ($stmtUsuariosDestino->fetchAll(PDO::FETCH_COLUMN) as $destinatarioId) {
            registrarNotificacao($pdo, $destinatarioId, $oficioId, "Nova demanda recebida: {$dados['assunto']}");
        }
        recalcularFilaSetor($pdo, $dados['setor_destino_id']);
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'dados' => ['id' => $oficioId]]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar oficio: ' . $e->getMessage()]);
}
