-- =====================================================================
-- SIGOF - Sistema Municipal de Integracao, Comunicacao e Rastreabilidade
-- de Demandas. Protótipo (Hack'n Hub One Challenge 4).
-- Importe este arquivo direto no phpMyAdmin (aba "Importar").
-- Reconstrucao completa baseada na Especificacao Funcional do Prototipo.
-- =====================================================================

DROP DATABASE IF EXISTS sigof;
CREATE DATABASE sigof CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigof;

-- ---------------------------------------------------------------------
-- 3.1 Secretarias
-- ---------------------------------------------------------------------
CREATE TABLE secretarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sigla VARCHAR(20) NOT NULL,
    nome VARCHAR(150) NOT NULL,
    competencias TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.2 Departamentos (pertencem a uma Secretaria)
-- ---------------------------------------------------------------------
CREATE TABLE departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secretaria_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    FOREIGN KEY (secretaria_id) REFERENCES secretarias(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3.3 Setores (pertencem a um Departamento)
-- ---------------------------------------------------------------------
CREATE TABLE setores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departamento_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    competencias TEXT NULL,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. Usuarios simulados (sem login/senha real - protocolo combinado:
-- seletor "agir como"). Carregam perfil e nivel PLUS para permitir
-- demonstrar as regras de permissao do documento.
-- ---------------------------------------------------------------------
CREATE TABLE usuarios_simulados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    perfil ENUM('admin_geral','secretario','diretor','coordenador','supervisor','servidor') NOT NULL DEFAULT 'servidor',
    nivel_plus ENUM('geral','administrativo','operacional') NULL,
    setor_id INT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (setor_id) REFERENCES setores(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2/5. Oficios (a Demanda - elemento central do sistema)
-- ---------------------------------------------------------------------
CREATE TABLE oficios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_oficio VARCHAR(50) NULL,
    tipo ENUM('solicitacao','solicitacao_informacao','solicitacao_documento','analise','providencia','encaminhamento','oficio','denuncia','outro') NOT NULL DEFAULT 'oficio',
    origem_legado TINYINT(1) NOT NULL DEFAULT 0,
    confidencial TINYINT(1) NOT NULL DEFAULT 0,
    assunto VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    observacoes TEXT NULL,
    setor_origem_id INT NOT NULL,
    setor_destino_id INT NOT NULL,
    responsavel_atual_id INT NULL,
    prioridade ENUM('baixa','normal','alta','critica') NOT NULL DEFAULT 'normal',
    urgencia ENUM('urgente','nao_urgente') NOT NULL DEFAULT 'nao_urgente',
    posicao_fila INT NULL,
    prazo_original DATE NOT NULL,
    prazo DATE NOT NULL,
    prazo_resposta DATE NULL,
    status ENUM('rascunho','enviada','recebida','aguardando_responsavel','em_analise','em_andamento','aguardando_informacao','aguardando_validacao','encaminhada','devolvida','concluida','cancelada','reaberta') NOT NULL DEFAULT 'rascunho',
    resposta_final TEXT NULL,
    criado_por_id INT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (setor_origem_id) REFERENCES setores(id),
    FOREIGN KEY (setor_destino_id) REFERENCES setores(id),
    FOREIGN KEY (responsavel_atual_id) REFERENCES usuarios_simulados(id),
    FOREIGN KEY (criado_por_id) REFERENCES usuarios_simulados(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5.2 Destino multiplo / 11.3 Envolver setor: setores adicionais
-- (alem do setor_destino_id principal) que acompanham a demanda.
-- ---------------------------------------------------------------------
CREATE TABLE oficio_destinatarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oficio_id INT NOT NULL,
    setor_id INT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (oficio_id) REFERENCES oficios(id),
    FOREIGN KEY (setor_id) REFERENCES setores(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5.7 Colaboradores (alem do responsavel principal)
-- ---------------------------------------------------------------------
CREATE TABLE oficio_colaboradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oficio_id INT NOT NULL,
    usuario_id INT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (oficio_id) REFERENCES oficios(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_simulados(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9/10. Linha do tempo / eventos. Somente-insercao (Principio da
-- Evidencia Permanente), reforcado pelos triggers abaixo.
-- ---------------------------------------------------------------------
CREATE TABLE oficio_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oficio_id INT NOT NULL,
    tipo_evento ENUM('status','comunicacao','anexo','alteracao') NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    campo_alterado VARCHAR(60) NULL,
    valor_anterior VARCHAR(255) NULL,
    valor_novo VARCHAR(255) NULL,
    justificativa TEXT NULL,
    autor_id INT NOT NULL,
    autor_perfil_snapshot VARCHAR(40) NULL,
    autor_local_snapshot VARCHAR(200) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (oficio_id) REFERENCES oficios(id),
    FOREIGN KEY (autor_id) REFERENCES usuarios_simulados(id)
) ENGINE=InnoDB;

DELIMITER $$

CREATE TRIGGER trg_eventos_bloqueia_update
BEFORE UPDATE ON oficio_eventos
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A linha do tempo e imutavel: eventos nao podem ser alterados.';
END$$

CREATE TRIGGER trg_eventos_bloqueia_delete
BEFORE DELETE ON oficio_eventos
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A linha do tempo e imutavel: eventos nao podem ser apagados.';
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 12. Notificacoes (Central de Notificacoes - canal "sistema").
-- E-mail/WhatsApp sao apenas rotulados no texto (simulados), sem envio real.
-- ---------------------------------------------------------------------
CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    oficio_id INT NULL,
    mensagem VARCHAR(255) NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_simulados(id),
    FOREIGN KEY (oficio_id) REFERENCES oficios(id)
) ENGINE=InnoDB;

-- =====================================================================
-- SEED - dados de demonstracao (fluxo SEMED->Compras e SESAU->Compras)
-- =====================================================================
INSERT INTO secretarias (sigla, nome, competencias) VALUES
('SEMED', 'Secretaria Municipal de Educacao', 'educacao, escolas, matriculas, transporte escolar, merenda escolar'),
('SESAU', 'Secretaria Municipal de Saude', 'saude, hospitais, upa, postos de saude, medicamentos, ambulancia'),
('COMPRAS', 'Secretaria Municipal de Compras e Licitacoes', 'compras, licitacao, contratos, aquisicoes, fornecedores');

INSERT INTO departamentos (secretaria_id, nome) VALUES
(1, 'Departamento Administrativo'),
(2, 'Departamento Administrativo'),
(3, 'Departamento de Aquisicoes');

INSERT INTO setores (departamento_id, nome, competencias) VALUES
(1, 'Protocolo Administrativo', 'protocolo, recebimento de oficios, material didatico, transporte escolar'),
(2, 'Protocolo Administrativo', 'protocolo, recebimento de oficios, medicamentos, insumos hospitalares'),
(3, 'Recebimento e Analise de Compras', 'compras, licitacao, contratos, material escolar, equipamentos, aquisicoes');

INSERT INTO usuarios_simulados (nome, perfil, nivel_plus, setor_id) VALUES
('Ana Ribeiro', 'servidor', NULL, 1),
('Fernando Alves', 'coordenador', NULL, 1),
('Carlos Souza', 'servidor', NULL, 2),
('Juliana Prado', 'coordenador', NULL, 2),
('Beatriz Lima', 'servidor', NULL, 3),
('Roberto Dias', 'coordenador', 'administrativo', 3),
('Marcia Tavares', 'secretario', 'geral', 3);

-- Oficio de exemplo 1: SEMED -> Compras, ja em andamento
INSERT INTO oficios (numero_oficio, tipo, assunto, descricao, setor_origem_id, setor_destino_id, responsavel_atual_id, prioridade, urgencia, prazo_original, prazo, status, criado_por_id)
VALUES ('123/2026', 'oficio', 'Solicitacao de compra de material escolar',
        'Necessidade de aquisicao de material didatico para o proximo semestre letivo.',
        1, 3, 5, 'alta', 'urgente', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'em_andamento', 1);

INSERT INTO oficio_colaboradores (oficio_id, usuario_id) VALUES (1, 6);

INSERT INTO oficio_eventos (oficio_id, tipo_evento, titulo, descricao, autor_id, autor_perfil_snapshot, autor_local_snapshot)
VALUES
(1, 'status', 'Oficio criado e enviado', 'Oficio no 123/2026 criado por Ana Ribeiro (SEMED) e enviado para Compras.', 1, 'servidor', 'SEMED - Protocolo Administrativo'),
(1, 'status', 'Oficio recebido', 'Recebido por Beatriz Lima.', 5, 'servidor', 'COMPRAS - Recebimento e Analise de Compras'),
(1, 'alteracao', 'Responsavel definido', 'Beatriz Lima definida como responsavel principal. Roberto Dias adicionado como colaborador.', 5, 'servidor', 'COMPRAS - Recebimento e Analise de Compras'),
(1, 'status', 'Em analise', 'Beatriz Lima iniciou a analise do oficio.', 5, 'servidor', 'COMPRAS - Recebimento e Analise de Compras'),
(1, 'status', 'Em andamento', 'Andamento iniciado.', 5, 'servidor', 'COMPRAS - Recebimento e Analise de Compras');

-- Oficio de exemplo 2: SESAU -> Compras, recem enviado
INSERT INTO oficios (numero_oficio, tipo, assunto, descricao, setor_origem_id, setor_destino_id, responsavel_atual_id, prioridade, urgencia, prazo_original, prazo, status, criado_por_id)
VALUES (NULL, 'solicitacao', 'Aquisicao de medicamentos para UPA',
        'Reposicao urgente de estoque de medicamentos basicos.',
        2, 3, NULL, 'critica', 'urgente', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'enviada', 3);

INSERT INTO oficio_eventos (oficio_id, tipo_evento, titulo, descricao, autor_id, autor_perfil_snapshot, autor_local_snapshot)
VALUES (2, 'status', 'Oficio criado e enviado', 'Solicitacao criada por Carlos Souza (SESAU) e enviada para Compras.', 3, 'servidor', 'SESAU - Protocolo Administrativo');

INSERT INTO notificacoes (usuario_id, oficio_id, mensagem) VALUES
(5, 1, 'Voce foi definido responsavel pelo oficio no 123/2026.'),
(6, 2, 'Nova demanda critica recebida de SESAU para Compras.');
