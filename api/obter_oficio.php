<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../config/database.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT o.*,
           so.nome AS origem_setor, sec_o.sigla AS origem_sigla,
           sd.nome AS destino_setor, sec_d.sigla AS destino_sigla,
           DATE_FORMAT(o.prazo, '%d/%m/%Y') AS prazo_formatado,
           r.nome AS responsavel_nome
    FROM oficios o
    JOIN setores so ON so.id = o.setor_origem_id
    JOIN secretarias sec_o ON sec_o.id = so.secretaria_id
    JOIN setores sd ON sd.id = o.setor_destino_id
    JOIN secretarias sec_d ON sec_d.id = sd.secretaria_id
    LEFT JOIN usuarios_simulados r ON r.id = o.responsavel_atual_id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$oficio = $stmt->fetch();

if (!$oficio) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Oficio nao encontrado.']);
    exit;
}

$stmtEventos = $pdo->prepare("
    SELECT e.*, u.nome AS autor_nome
    FROM oficio_eventos e
    JOIN usuarios_simulados u ON u.id = e.autor_id
    WHERE e.oficio_id = ?
    ORDER BY e.criado_em ASC, e.id ASC
");
$stmtEventos->execute([$id]);
$eventos = $stmtEventos->fetchAll();

$setores = $pdo->query("
    SELECT s.id, s.nome, sec.sigla
    FROM setores s JOIN secretarias sec ON sec.id = s.secretaria_id
    ORDER BY sec.sigla
")->fetchAll();

echo json_encode(['sucesso' => true, 'dados' => ['oficio' => $oficio, 'eventos' => $eventos, 'setores' => $setores]]);
