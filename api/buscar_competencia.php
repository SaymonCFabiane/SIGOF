<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../config/database.php';

$termo = trim($_GET['termo'] ?? '');
if ($termo === '') {
    echo json_encode(['sucesso' => true, 'dados' => []]);
    exit;
}

$like = '%' . $termo . '%';
$stmt = $pdo->prepare("
    SELECT s.id AS setor_id, s.nome AS setor_nome, s.competencias AS setor_competencias,
           d.nome AS departamento_nome,
           sec.sigla AS secretaria_sigla, sec.nome AS secretaria_nome, sec.competencias AS secretaria_competencias
    FROM setores s
    JOIN departamentos d ON d.id = s.departamento_id
    JOIN secretarias sec ON sec.id = d.secretaria_id
    WHERE s.nome LIKE ? OR s.competencias LIKE ? OR sec.nome LIKE ? OR sec.sigla LIKE ? OR sec.competencias LIKE ?
    ORDER BY sec.sigla, s.nome
");
$stmt->execute([$like, $like, $like, $like, $like]);
$resultados = $stmt->fetchAll();

echo json_encode(['sucesso' => true, 'dados' => $resultados]);
