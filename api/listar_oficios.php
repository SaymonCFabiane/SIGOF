<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes.php';

$usuarioAtualId = usuarioAtualId();

$condicoes = ["(o.status <> 'rascunho' OR o.criado_por_id = :usuario_atual)"];
$parametros = ['usuario_atual' => $usuarioAtualId];

$status = $_GET['status'] ?? '';
if ($status !== '') {
    $condicoes[] = 'o.status = :status';
    $parametros['status'] = $status;
} else {
    $condicoes[] = "o.status <> 'rascunho'";
}

if (!empty($_GET['prioridade'])) {
    $condicoes[] = 'o.prioridade = :prioridade';
    $parametros['prioridade'] = $_GET['prioridade'];
}

if (!empty($_GET['tipo'])) {
    $condicoes[] = 'o.tipo = :tipo';
    $parametros['tipo'] = $_GET['tipo'];
}

$situacaoPrazo = $_GET['situacao_prazo'] ?? '';
if ($situacaoPrazo === 'vencidas') {
    $condicoes[] = "o.prazo < CURDATE() AND o.status NOT IN ('concluida','cancelada')";
} elseif ($situacaoPrazo === 'proximas') {
    $condicoes[] = "o.prazo BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND o.status NOT IN ('concluida','cancelada')";
}

$sql = "
    SELECT o.id, o.numero_oficio, o.tipo, o.assunto, o.prioridade, o.urgencia, o.status,
           o.prazo, DATE_FORMAT(o.prazo, '%d/%m/%Y') AS prazo_formatado,
           sec_o.sigla AS origem_sigla, sec_d.sigla AS destino_sigla,
           r.nome AS responsavel_nome
    FROM oficios o
    JOIN setores so ON so.id = o.setor_origem_id
    JOIN departamentos do_ ON do_.id = so.departamento_id
    JOIN secretarias sec_o ON sec_o.id = do_.secretaria_id
    JOIN setores sd ON sd.id = o.setor_destino_id
    JOIN departamentos dd ON dd.id = sd.departamento_id
    JOIN secretarias sec_d ON sec_d.id = dd.secretaria_id
    LEFT JOIN usuarios_simulados r ON r.id = o.responsavel_atual_id
    WHERE " . implode(' AND ', $condicoes) . "
    ORDER BY o.criado_em DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$oficios = $stmt->fetchAll();

foreach ($oficios as &$o) {
    $atrasada = $o['prazo'] < date('Y-m-d') && !in_array($o['status'], ['concluida', 'cancelada'], true);
    $o['atrasada'] = $atrasada;
    $o['dias_atraso'] = $atrasada ? diasUteisEntreDatas($o['prazo'], date('Y-m-d')) : 0;
}
unset($o);

echo json_encode(['sucesso' => true, 'dados' => $oficios]);
