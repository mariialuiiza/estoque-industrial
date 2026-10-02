CREATE DATABASE IF NOT EXISTS industria_db DEFAULT CHARACTER SET utf8;
USE industria_db;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(45) NOT NULL,
  nome VARCHAR(200) NOT NULL,
  fabricante VARCHAR(100) NOT NULL,
  preco DECIMAL(10,2) NOT NULL,
  estoque_atual INT NOT NULL,
  estoque_minimo INT NOT NULL,
  categoria VARCHAR(45),
  material_fabricacao VARCHAR(100),
  ativo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS movimentacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo INT NOT NULL,
  data DATE NOT NULL,
  quantidade INT NOT NULL,
  saldo_anterior INT NOT NULL,
  usuarios_id INT NOT NULL,
  produtos_id INT NOT NULL,
  FOREIGN KEY (usuarios_id) REFERENCES usuarios(id),
  FOREIGN KEY (produtos_id) REFERENCES produtos(id)
);

-- Inserir um usuário padrão para você conseguir fazer o login
INSERT INTO usuarios (nome, email, senha, ativo) 
VALUES ('Administrador', 'admin@email.com', '123456', 1);