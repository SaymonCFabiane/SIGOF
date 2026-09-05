<?php
// Funcoes auxiliares compartilhadas entre paginas e endpoints da API.

function carregarUsuarioAtual(PDO $pdo, $usuarioId)
{
    if (!$usuarioId) {
        return null;
    }
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.perfil, u.nivel_plus, u.setor_id,
               s.nome AS setor_nome, s.departamento_id,
               d.nome AS departamento_nome, d.secretaria_id,
               sec.sigla AS secretaria_sigla, sec.nome AS secretaria_nome
        FROM usuarios_simulados u
        JOIN setores s ON s.id = u.setor_id
        JOIN departamentos d ON d.id = s.departamento_id
        JOIN secretarias sec ON sec.id = d.secretaria_id
        WHERE u.id = ?
    ");
    $stmt->execute([$usuarioId]);
    return $stmt->fetch() ?: null;
}

function usuarioEhPlusDaSecretaria(PDO $pdo, $usuarioId, $secretariaId)
{
    $u = carregarUsuarioAtual($pdo, $usuarioId);
    if (!$u || empty($u['nivel_plus'])) {
        return false;
    }
    return (int) $u['secretaria_id'] === (int) $secretariaId;
}

function secretariaIdDoSetor(PDO $pdo, $setorId)
{
    $stmt = $pdo->prepare("SELECT d.secretaria_id FROM setores s JOIN departamentos d ON d.id = s.departamento_id WHERE s.id = ?");
    $stmt->execute([$setorId]);
    return $stmt->fetchColumn();
}

function registrarEvento(PDO $pdo, $oficioId, $tipoEvento, $titulo, $descricao, $autorId, $campoAlterado = null, $valorAnterior = null, $valorNovo = null, $justificativa = null)
{
    $autor = carregarUsuarioAtual($pdo, $autorId);
    $perfilSnapshot = $autor['perfil'] ?? null;
    $localSnapshot = $autor ? ($autor['secretaria_sigla'] . ' - ' . $autor['setor_nome']) : null;

    $stmt = $pdo->prepare("
        INSERT INTO oficio_eventos (oficio_id, tipo_evento, titulo, descricao, campo_alterado, valor_anterior, valor_novo, justificativa, autor_id, autor_perfil_snapshot, autor_local_snapshot)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$oficioId, $tipoEvento, $titulo, $descricao, $campoAlterado, $valorAnterior, $valorNovo, $justificativa, $autorId, $perfilSnapshot, $localSnapshot]);
}

function registrarNotificacao(PDO $pdo, $usuarioId, $oficioId, $mensagem)
{
    if (!$usuarioId) {
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO notificacoes (usuario_id, oficio_id, mensagem) VALUES (?, ?, ?)");
    $stmt->execute([$usuarioId, $oficioId, $mensagem]);
}

// Feriados nacionais fixos (simplificado para o prototipo - pode ser expandido).
function feriadosNacionais()
{
    $ano = date('Y');
    return [
        "{$ano}-01-01", "{$ano}-04-21", "{$ano}-05-01",
        "{$ano}-09-07", "{$ano}-10-12", "{$ano}-11-02",
        "{$ano}-11-15", "{$ano}-12-25",
    ];
}

function ehDiaUtil($data)
{
    $diaSemana = (int) date('N', strtotime($data));
    if ($diaSemana >= 6) {
        return false;
    }
    return !in_array(date('Y-m-d', strtotime($data)), feriadosNacionais(), true);
}

// Conta quantos dias uteis existem entre duas datas (usado para "atrasada ha N dias").
function diasUteisEntreDatas($dataInicio, $dataFim)
{
    $inicio = new DateTime($dataInicio);
    $fim = new DateTime($dataFim);
    if ($inicio >= $fim) {
        return 0;
    }
    $contador = 0;
    $atual = clone $inicio;
    $atual->modify('+1 day');
    while ($atual <= $fim) {
        if (ehDiaUtil($atual->format('Y-m-d'))) {
            $contador++;
        }
        $atual->modify('+1 day');
    }
    return $contador;
}

function labelStatus($status)
{
    $labels = [
        'rascunho' => 'Rascunho',
        'enviada' => 'Enviada',
        'recebida' => 'Recebida',
        'aguardando_responsavel' => 'Aguardando Responsavel',
        'em_analise' => 'Em Analise',
        'em_andamento' => 'Em Andamento',
        'aguardando_informacao' => 'Aguardando Informacao',
        'aguardando_validacao' => 'Aguardando Validacao',
        'encaminhada' => 'Encaminhada',
        'devolvida' => 'Devolvida',
        'concluida' => 'Concluida',
        'cancelada' => 'Cancelada',
        'reaberta' => 'Reaberta',
    ];
    return $labels[$status] ?? $status;
}

function labelPerfil($perfil)
{
    $labels = [
        'admin_geral' => 'Administrador Geral',
        'secretario' => 'Secretario',
        'diretor' => 'Diretor',
        'coordenador' => 'Coordenador',
        'supervisor' => 'Supervisor',
        'servidor' => 'Servidor/Analista',
    ];
    return $labels[$perfil] ?? $perfil;
}

function labelTipo($tipo)
{
    $labels = [
        'solicitacao' => 'Solicitacao',
        'solicitacao_informacao' => 'Solicitacao de Informacao',
        'solicitacao_documento' => 'Solicitacao de Documento',
        'analise' => 'Analise',
        'providencia' => 'Providencia',
        'encaminhamento' => 'Encaminhamento',
        'oficio' => 'Oficio',
        'denuncia' => 'Denuncia',
        'outro' => 'Outro',
    ];
    return $labels[$tipo] ?? $tipo;
}
