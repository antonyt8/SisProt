CREATE DATABASE IF NOT EXISTS sisprot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sisprot;

CREATE TABLE IF NOT EXISTS setores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    setor_id INT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_setor FOREIGN KEY (setor_id) REFERENCES setores(id)
);

CREATE TABLE IF NOT EXISTS processos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_protocolo VARCHAR(20) NOT NULL UNIQUE,
    interessado_nome VARCHAR(100) NOT NULL,
    interessado_cpf VARCHAR(11) NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    status ENUM('aberto', 'em_tramitacao', 'finalizado') NOT NULL DEFAULT 'aberto',
    setor_atual_id INT NOT NULL,
    user_id INT NOT NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_processo_setor FOREIGN KEY (setor_atual_id) REFERENCES setores(id),
    CONSTRAINT fk_processo_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS tramitacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    processo_id INT NOT NULL,
    origem_setor_id INT NOT NULL,
    destino_setor_id INT NOT NULL,
    despacho TEXT NOT NULL,
    user_id INT NOT NULL,
    ip VARCHAR(45) NULL,
    user_agent TEXT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tramitacao_processo FOREIGN KEY (processo_id) REFERENCES processos(id),
    CONSTRAINT fk_tramitacao_origem_setor FOREIGN KEY (origem_setor_id) REFERENCES setores(id),
    CONSTRAINT fk_tramitacao_destino_setor FOREIGN KEY (destino_setor_id) REFERENCES setores(id),
    CONSTRAINT fk_tramitacao_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_processos_cpf ON processos(interessado_cpf);
CREATE INDEX idx_processos_status ON processos(status);
CREATE INDEX idx_processos_setor ON processos(setor_atual_id);
CREATE INDEX idx_tramitacoes_processo ON tramitacoes(processo_id);
CREATE INDEX idx_tramitacoes_data_hora ON tramitacoes(data_hora);

INSERT INTO setores (nome) VALUES
('Protocolo Geral'),
('Gabinete'),
('Financeiro'),
('Recursos Humanos')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Senha do admin padrao: admin123
INSERT INTO users (nome, email, senha, setor_id)
SELECT 'Administrador', 'admin@sisprot.local', '$2y$10$wSC4r7jppktnqO3pPhC4ruvopBLPf6R8k8gj.BiGCeiWBMT5eYecO', 1
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@sisprot.local'
);
