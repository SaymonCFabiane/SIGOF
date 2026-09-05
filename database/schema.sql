-- =====================================================================
-- SIGOF - Sistema Municipal de Rastreabilidade de Demandas
-- Script de criacao do banco de dados - Protótipo (Hack'n Hub One Challenge 4)
-- Importe este arquivo direto no phpMyAdmin (aba "Importar").
-- =====================================================================

CREATE DATABASE IF NOT EXISTS sigof CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigof;

-- ---------------------------------------------------------------------
-- Secretarias (escopo do protótipo: apenas as que aparecem na demo)
-- ---------------------------------------------------------------------
CREATE TABLE secretarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sigla VARCHAR(20) NOT NULL,
    nome VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Setores dentro de cada secretaria
-- ---------------------------------------------------------------------
CREATE TABLE setores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    secretaria_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    FOREIGN KEY (secretaria_id) REFERENCES secretarias(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Usuarios simulados: sem login/senha real. Servem apenas para
-- identificar "quem esta agindo" durante a demonstracao do prototipo
-- e para assinar os eventos da linha do tempo.
-- ---------------------------------------------------------------------
CREATE TABLE usuarios_simulados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    setor_id INT NOT NULL,
    FOREIGN KEY (setor_id) REFERENCES setores(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Oficios: elemento central do sistema (a "Demanda" do documento
-- funcional), com foco em envio/recebimento entre setores.
-- ---------------------------------------------------------------------
CREATE TABLE oficios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_oficio VARCHAR(50) NULL,
    tipo ENUM('solicitacao','solicitacao_informacao','documento','analise','providencia','encaminhamento','oficio') NOT NULL DEFAULT 'oficio',
    assunto VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    setor_origem_id INT NOT NULL,
    setor_destino_id INT NOT NULL,
    responsavel_atual_id INT NULL,
    prioridade ENUM('baixa','normal','alta','critica') NOT NULL DEFAULT 'normal',
    urgencia ENUM('urgente','nao_urgente') NOT NULL DEFAULT 'nao_urgente',
    prazo DATE NOT NULL,
    status ENUM('enviada','recebida','aguardando_responsavel','aguardando_informacao','em_andamento','devolvida','concluida') NOT NULL DEFAULT 'enviada',
    criado_por_id INT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (setor_origem_id) REFERENCES setores(id),
    FOREIGN KEY (setor_destino_id) REFERENCES setores(id),
    FOREIGN KEY (responsavel_atual_id) REFERENCES usuarios_simulados(id),
    FOREIGN KEY (criado_por_id) REFERENCES usuarios_simulados(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Linha do tempo / eventos do oficio.
-- Principio da Evidencia Permanente: esta tabela e' somente-insercao,
-- reforcado pelos triggers logo abaixo (nao basta disciplina no
-- codigo PHP, o proprio banco recusa UPDATE/DELETE).
-- ---------------------------------------------------------------------
CREATE TABLE oficio_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oficio_id INT NOT NULL,
    tipo_evento ENUM('status','comunicacao','anexo') NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    autor_id INT NOT NULL,
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

-- =====================================================================
-- Dados de exemplo (seed) - fluxo SEMED -> Compras e SESAU -> Compras
-- =====================================================================
INSERT INTO secretarias (sigla, nome) VALUES
('SEMED', 'Secretaria Municipal de Educacao'),
('SESAU', 'Secretaria Municipal de Saude'),
('COMPRAS', 'Secretaria Municipal de Compras e Licitacoes');

INSERT INTO setores (secretaria_id, nome) VALUES
(1, 'Protocolo Administrativo'),
(2, 'Protocolo Administrativo'),
(3, 'Recebimento e Analise de Compras');

INSERT INTO usuarios_simulados (nome, setor_id) VALUES
('Ana Ribeiro', 1),
('Carlos Souza', 2),
('Beatriz Lima', 3);

INSERT INTO oficios (numero_oficio, tipo, assunto, descricao, setor_origem_id, setor_destino_id, responsavel_atual_id, prioridade, urgencia, prazo, status, criado_por_id)
VALUES ('123/2026', 'oficio', 'Solicitacao de compra de material escolar',
        'Necessidade de aquisicao de material didatico para o proximo semestre letivo.',
        1, 3, NULL, 'alta', 'urgente', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'enviada', 1);

INSERT INTO oficio_eventos (oficio_id, tipo_evento, titulo, descricao, autor_id)
VALUES (1, 'status', 'Oficio criado e enviado',
        'Oficio no 123/2026 criado por Ana Ribeiro (SEMED) e enviado para Compras.', 1);
