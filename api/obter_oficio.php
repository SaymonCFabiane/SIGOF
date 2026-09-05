<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes.php';

$usuarioAtualId = usuarioAtualId();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT o.*,
           so.nome AS origem_setor, sec_o.sigla AS origem_sigla,
           sd.nome AS destino_setor, sec_d.sigla AS destino_sigla, dd.secretaria_id AS destino_secretaria_id,
           DATE_FORMAT(o.prazo, '%d/%m/%Y') AS prazo_formatado,
           DATE_FORMAT(o.prazo_original, '%d/%m/%Y') AS prazo_original_formatado,
           DATE_FORMAT(o.prazo_resposta, '%d/%m/%Y') AS prazo_resposta_formatado,
           r.nome AS responsavel_nome,
           c.nome AS criador_nome
    FROM oficios o
    JOIN setores so ON so.id = o.setor_origem_id
    JOIN departamentos do_ ON do_.id = so.departamento_id
    JOIN secretarias sec_o ON sec_o.id = do_.secretaria_id
    JOIN setores sd ON sd.id = o.setor_destino_id
    JOIN departamentos dd ON dd.id = sd.departamento_id
    JOIN secretarias sec_d ON sec_d.id = dd.secretaria_id
    LEFT JOIN usuarios_simulados r ON r.id = o.responsavel_atual_id
    LEFT JOIN usuarios_simulados c ON c.id = o.criado_por_id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$oficio = $stmt->fetch();

if (!$oficio) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Oficio nao encontrado.']);
    exit;
}

if ($oficio['status'] === 'rascunho' && (int) $oficio['criado_por_id'] !== (int) $usuarioAtualId) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Este rascunho e visivel apenas para quem o criou.']);
    exit;
}

$confidencial = ((int) $oficio['confidencial'] === 1) && ((int) $oficio['criado_por_id'] !== (int) $usuarioAtualId);
if ($confidencial) {
    $oficio['criador_nome'] = 'Denunciante (identidade protegida)';
    $oficio['criado_por_id'] = null;
}

$stmtEventos = $pdo->prepare("
    SELECT e.*
    FROM oficio_eventos e
    WHERE e.oficio_id = ?
    ORDER BY e.criado_em ASC, e.id ASC
");
$stmtEventos->execute([$id]);
$eventos = $stmtEventos->fetchAll();

// Recarrega o autor original real (antes de mascarar) para comparar nos eventos.
$stmtCriador = $pdo->prepare("SELECT criado_por_id FROM oficios WHERE id = ?");
$stmtCriador->execute([$id]);
$criadorRealId = (int) $stmtCriador->fetchColumn();

foreach ($eventos as &$ev) {
    $autor = carregarUsuarioAtual($pdo, $ev['autor_id']);
    $ev['autor_nome'] = $autor['nome'] ?? 'Usuario removido';
    if ($confidencial && (int) $ev['autor_id'] === $criadorRealId) {
        $ev['autor_nome'] = 'Denunciante (identidade protegida)';
    }
}
unset($ev);

$stmtColaboradores = $pdo->prepare("
    SELECT u.id, u.nome
    FROM oficio_colaboradores oc
    JOIN usuarios_simulados u ON u.id = oc.usuario_id
    WHERE oc.oficio_id = ?
");
$stmtColaboradores->execute([$id]);
$colaboradores = $stmtColaboradores->fetchAll();

$stmtDestinatarios = $pdo->prepare("
    SELECT s.id, s.nome, sec.sigla
    FROM oficio_destinatarios od
    JOIN setores s ON s.id = od.setor_id
    JOIN departamentos d ON d.id = s.departamento_id
    JOIN secretarias sec ON sec.id = d.secretaria_id
    WHERE od.oficio_id = ?
");
$stmtDestinatarios->execute([$id]);
$destinatariosExtras = $stmtDestinatarios->fetchAll();

$setores = $pdo->query("
    SELECT s.id, s.nome, sec.sigla
    FROM setores s
    JOIN departamentos d ON d.id = s.departamento_id
    JOIN secretarias sec ON sec.id = d.secretaria_id
    ORDER BY sec.sigla, s.nome
")->fetchAll();

$usuariosSetorDestino = $pdo->prepare("SELECT id, nome FROM usuarios_simulados WHERE setor_id = ? AND ativo = 1");
$usuariosSetorDestino->execute([$oficio['setor_destino_id']]);
$usuariosDestino = $usuariosSetorDestino->fetchAll();

$ehPlus = usuarioEhPlusDaSecretaria($pdo, $usuarioAtualId, $oficio['destino_secretaria_id']);
$ehResponsavel = (int) $oficio['responsavel_atual_id'] === (int) $usuarioAtualId;
$autorAtual = carregarUsuarioAtual($pdo, $usuarioAtualId);
$ehDoSetorDestino = $autorAtual && (int) $autorAtual['setor_id'] === (int) $oficio['setor_destino_id'];
$ehDemandante = $criadorRealId === (int) $usuarioAtualId;

echo json_encode(['sucesso' => true, 'dados' => [
    'oficio' => $oficio,
    'eventos' => $eventos,
    'colaboradores' => $colaboradores,
    'destinatarios_extras' => $destinatariosExtras,
    'setores' => $setores,
    'usuarios_destino' => $usuariosDestino,
    'permissoes' => [
        'eh_plus' => $ehPlus,
        'eh_responsavel' => $ehResponsavel,
        'eh_do_setor_destino' => $ehDoSetorDestino,
        'eh_demandante' => $ehDemandante,
    ],
]]);
