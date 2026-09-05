<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/sessao.php';
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("
    SELECT o.*,
           so.nome AS origem_setor, sec_o.sigla AS origem_sigla,
           sd.nome AS destino_setor, sec_d.sigla AS destino_sigla,
           DATE_FORMAT(o.prazo, '%d/%m/%Y') AS prazo_formatado
    FROM oficios o
    JOIN setores so ON so.id = o.setor_origem_id
    JOIN secretarias sec_o ON sec_o.id = so.secretaria_id
    JOIN setores sd ON sd.id = o.setor_destino_id
    JOIN secretarias sec_d ON sec_d.id = sd.secretaria_id
    ORDER BY o.criado_em DESC
");
$oficios = $stmt->fetchAll();

echo json_encode(['sucesso' => true, 'dados' => $oficios]);
