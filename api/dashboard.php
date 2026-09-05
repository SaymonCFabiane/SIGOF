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

$usuario = carregarUsuarioAtual($pdo, $usuarioId);
$secretariaId = $usuario['secretaria_id'];
$setorId = $usuario['setor_id'];

// --- 14.1 Bloco pessoal (Servidor) ---
$stmt = $pdo->prepare("SELECT COUNT(*) FROM oficios WHERE criado_por_id = ?");
$stmt->execute([$usuarioId]);
$minhasDemandas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM oficios WHERE responsavel_atual_id = ? AND status NOT IN ('concluida','cancelada','devolvida')");
$stmt->execute([$usuarioId]);
$aguardandoMinhaAcao = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM oficios WHERE responsavel_atual_id = ? AND status = 'aguardando_informacao'");
$stmt->execute([$usuarioId]);
$solicitacoesInformacao = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM oficios WHERE responsavel_atual_id = ? AND prazo BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND status NOT IN ('concluida','cancelada')");
$stmt->execute([$usuarioId]);
$prazosProximosMeus = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM oficios WHERE responsavel_atual_id = ? AND prazo < CURDATE() AND status NOT IN ('concluida','cancelada')");
$stmt->execute([$usuarioId]);
$vencidasMinhas = (int) $stmt->fetchColumn();

$pessoal = [
    'minhas_demandas' => $minhasDemandas,
    'aguardando_minha_acao' => $aguardandoMinhaAcao,
    'solicitacoes_informacao' => $solicitacoesInformacao,
    'prazos_proximos' => $prazosProximosMeus,
    'vencidas' => $vencidasMinhas,
];

// --- 14.2 Bloco do setor (Coordenacao/Supervisao/Direcao) ---
$stmt = $pdo->prepare("SELECT COUNT(*) FROM oficios WHERE setor_destino_id = ?");
$stmt->execute([$setorId]);
$totalSetor = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM oficios WHERE setor_destino_id = ? AND status NOT IN ('concluida','cancelada')");
$stmt->execute([$setorId]);
$pendentesSetor = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM oficios WHERE setor_destino_id = ? AND prazo < CURDATE() AND status NOT IN ('concluida','cancelada')");
$stmt->execute([$setorId]);
$atrasosSetor = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM oficios WHERE setor_destino_id = ? AND prioridade = 'critica' AND status NOT IN ('concluida','cancelada')");
$stmt->execute([$setorId]);
$criticasSetor = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COALESCE(r.nome, 'Sem responsavel definido') AS nome, COUNT(*) AS total
    FROM oficios o
    LEFT JOIN usuarios_simulados r ON r.id = o.responsavel_atual_id
    WHERE o.setor_destino_id = ? AND o.status NOT IN ('concluida','cancelada')
    GROUP BY r.nome
    ORDER BY total DESC
");
$stmt->execute([$setorId]);
$distribuicaoTrabalho = $stmt->fetchAll();

$setorBloco = [
    'total' => $totalSetor,
    'pendentes' => $pendentesSetor,
    'atrasos' => $atrasosSetor,
    'criticas' => $criticasSetor,
    'distribuicao_trabalho' => $distribuicaoTrabalho,
];

// --- 14.4 Bloco da Secretaria (Secretario/Admin Geral/Diretor) ---
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM oficios o
    JOIN setores sd ON sd.id = o.setor_destino_id
    JOIN departamentos dd ON dd.id = sd.departamento_id
    WHERE dd.secretaria_id = ?
");
$stmt->execute([$secretariaId]);
$totalSecretaria = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT o.status, COUNT(*) AS total FROM oficios o
    JOIN setores sd ON sd.id = o.setor_destino_id
    JOIN departamentos dd ON dd.id = sd.departamento_id
    WHERE dd.secretaria_id = ?
    GROUP BY o.status
");
$stmt->execute([$secretariaId]);
$porStatus = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR, o.criado_em, o.atualizado_em)) / 24, 1) AS media_dias
    FROM oficios o
    JOIN setores sd ON sd.id = o.setor_destino_id
    JOIN departamentos dd ON dd.id = sd.departamento_id
    WHERE dd.secretaria_id = ? AND o.status = 'concluida'
");
$stmt->execute([$secretariaId]);
$tempoMedioDias = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN o.status = 'concluida' AND DATE(o.atualizado_em) <= o.prazo THEN 1 ELSE 0 END) AS no_prazo,
        SUM(CASE WHEN o.status = 'concluida' THEN 1 ELSE 0 END) AS total_concluidas
    FROM oficios o
    JOIN setores sd ON sd.id = o.setor_destino_id
    JOIN departamentos dd ON dd.id = sd.departamento_id
    WHERE dd.secretaria_id = ?
");
$stmt->execute([$secretariaId]);
$prazoInfo = $stmt->fetch();
$taxaNoPrazo = $prazoInfo['total_concluidas'] > 0 ? round(($prazoInfo['no_prazo'] / $prazoInfo['total_concluidas']) * 100) : null;

$stmt = $pdo->prepare("
    SELECT sec_o.sigla AS origem, COUNT(*) AS total
    FROM oficios o
    JOIN setores so ON so.id = o.setor_origem_id
    JOIN departamentos do_ ON do_.id = so.departamento_id
    JOIN secretarias sec_o ON sec_o.id = do_.secretaria_id
    JOIN setores sd ON sd.id = o.setor_destino_id
    JOIN departamentos dd ON dd.id = sd.departamento_id
    WHERE dd.secretaria_id = ? AND so.departamento_id NOT IN (SELECT id FROM departamentos WHERE secretaria_id = ?)
    GROUP BY sec_o.sigla
    ORDER BY total DESC
");
$stmt->execute([$secretariaId, $secretariaId]);
$matrizOrigemDestino = $stmt->fetchAll();

$secretariaBloco = [
    'total' => $totalSecretaria,
    'por_status' => $porStatus,
    'tempo_medio_dias' => $tempoMedioDias,
    'taxa_no_prazo' => $taxaNoPrazo,
    'matriz_origem' => $matrizOrigemDestino,
];

echo json_encode(['sucesso' => true, 'dados' => [
    'perfil' => $usuario['perfil'],
    'secretaria_sigla' => $usuario['secretaria_sigla'],
    'setor_nome' => $usuario['setor_nome'],
    'pessoal' => $pessoal,
    'setor' => $setorBloco,
    'secretaria' => $secretariaBloco,
]]);
