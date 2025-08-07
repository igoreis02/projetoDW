-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07/08/2025 às 22:38
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `gerenciamento_manutencoes`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `cidades`
--

CREATE TABLE `cidades` (
  `id_cidade` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cidades`
--

INSERT INTO `cidades` (`id_cidade`, `nome`) VALUES
(1, 'Senador Canedo'),
(2, 'Caldas Novas'),
(3, 'Catalão');

-- --------------------------------------------------------

--
-- Estrutura para tabela `endereco`
--

CREATE TABLE `endereco` (
  `id_endereco` int(11) NOT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `bairro` varchar(255) DEFAULT NULL,
  `cep` varchar(20) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `endereco`
--

INSERT INTO `endereco` (`id_endereco`, `logradouro`, `numero`, `bairro`, `cep`, `complemento`, `latitude`, `longitude`) VALUES
(1, 'AV FLORESTA', '123', 'CENTRO', '74957070', '', NULL, NULL),
(2, 'RUA FLORESTA', '123', 'CENTRO', '741234565', '', NULL, NULL),
(3, 'RUA FLORESTA', '123', 'CENTRO', '74123456', '', NULL, NULL),
(4, 'rua floresta', '123', 'centro', '7415763', '', NULL, NULL),
(5, 'RUA FLORESTA', '123', 'CENTRO', '7498512', '', NULL, NULL),
(6, 'AV LOURO', '1258', 'MACAMBIRA', '74951123', '', NULL, NULL),
(7, 'RUA PRINCESA ISABEL', '15', 'VILA BRASILIA', '74958358', '', NULL, NULL),
(8, 'AV PRONCESA', '123', 'VILA BRA', '74158632', '', NULL, NULL),
(9, 'AV PRONCESA', '123', 'VILA BRA', '74158632', '', NULL, NULL),
(10, 'PONCES', '11235', 'CENTRO', '74958595', '', NULL, NULL),
(11, 'PONCES', '11235', 'CENTRO', '74958595', '', NULL, NULL),
(12, 'RUA PRINCESA', '1234', 'CENTRO', '74958589', '', NULL, NULL),
(13, 'RUA PRINCESA', '1234', 'CENTRO', '74958589', '', NULL, NULL),
(14, 'RUA PRINCESA', '1234', 'CENTRO', '74958589', '', NULL, NULL),
(15, 'PRONCES', '1234', 'CENTRO', '74958958', '', NULL, NULL),
(16, 'AV PRONCESS', '1234', 'CENTRO', '74958955', '', NULL, NULL),
(17, 'PRINCESA', '1234', 'CENTRO', '74958365', '', NULL, NULL),
(18, 'PRINCES', '123', 'CENTRO', '74958958', '', NULL, NULL),
(19, 'AV CEL. BENTO DE GODOY X GO-309', '00', 'ITANHANGÁ', '74680-358', NULL, -17.76213100, -48.63401000),
(20, 'AV. anibal dos R. junqueira, Pç maçom, Sentido Centro', '00', 'Est. Itanhangá', '75680368', NULL, -17.75240300, -48.63657900);

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipamentos`
--

CREATE TABLE `equipamentos` (
  `id_equipamento` int(11) NOT NULL,
  `nome_equip` varchar(255) NOT NULL,
  `referencia_equip` varchar(255) DEFAULT NULL,
  `tipo_equip` varchar(255) DEFAULT NULL,
  `id_cidade` int(11) DEFAULT NULL,
  `id_endereco` int(11) DEFAULT NULL,
  `status` enum('ativo','inativo','remanejado') NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `equipamentos`
--

INSERT INTO `equipamentos` (`id_equipamento`, `nome_equip`, `referencia_equip`, `tipo_equip`, `id_cidade`, `id_endereco`, `status`) VALUES
(1, 'ESCOLA', 'MT310', 'RADAR FIXO', 1, NULL, 'ativo'),
(2, 'PRAÇA', 'MT311', 'RADAR FIXO', 1, NULL, 'ativo'),
(3, 'PARQUE', 'MT320', 'RADAR FIXO', 2, NULL, 'ativo'),
(4, 'LAGO', 'MT321', 'RADAR FIXO', 2, NULL, 'ativo'),
(5, 'COLEGIO', 'MT330', 'RADAR FIXO', 3, NULL, 'ativo'),
(6, 'PREFEITURA', 'MT331', 'RADAR FIXO', 3, NULL, 'ativo'),
(19, 'MT400', 'FLORESTA', 'RADAR FIXO', 3, 1, 'ativo'),
(24, 'MT500', 'ESCOLA MUNICIPAL', 'RADAR FIXO', 2, 6, 'ativo'),
(25, 'MT700', 'DELTAWAY', 'RADAR FIXO', 2, 7, 'ativo'),
(26, 'MT701', 'DELTA', 'RADAR FIXO', 1, 17, 'ativo'),
(27, 'DELTAWAYSC', 'MT701', 'RADAR FIXO', 1, 18, 'ativo'),
(28, 'D21', 'FIAT', 'DOME', 2, 19, 'ativo'),
(29, 'MT413', 'PRACA MAÇOM', 'RADAR FIXO', 2, 20, 'ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `manutencoes`
--

CREATE TABLE `manutencoes` (
  `id_manutencao` int(11) NOT NULL,
  `id_equipamento` int(11) NOT NULL,
  `id_provedor` int(11) DEFAULT NULL,
  `status_reparo` enum('pendente','em andamento','concluido','cancelado') DEFAULT 'pendente',
  `inicio_reparo` datetime DEFAULT current_timestamp(),
  `fim_reparo` datetime DEFAULT NULL,
  `tempo_reparo` varchar(50) DEFAULT NULL,
  `tipo_manutencao` enum('preventiva','corretiva','preditiva','instalação') NOT NULL,
  `ocorrencia_reparo` text DEFAULT NULL,
  `reparo_finalizado` text DEFAULT NULL,
  `id_cidade` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `manutencoes`
--

INSERT INTO `manutencoes` (`id_manutencao`, `id_equipamento`, `id_provedor`, `status_reparo`, `inicio_reparo`, `fim_reparo`, `tempo_reparo`, `tipo_manutencao`, `ocorrencia_reparo`, `reparo_finalizado`, `id_cidade`) VALUES
(1, 4, NULL, 'pendente', '2025-08-02 01:52:49', NULL, NULL, 'corretiva', 'Troca fonte', NULL, 2),
(2, 5, NULL, 'pendente', '2025-08-02 02:16:47', NULL, NULL, 'corretiva', 'Correçção do zoom', NULL, 3),
(3, 4, NULL, 'concluido', '2025-08-02 02:20:18', NULL, NULL, 'preditiva', 'levanto a camera', NULL, 2),
(4, 19, NULL, 'concluido', '2025-08-02 02:44:57', NULL, NULL, 'instalação', 'Instalação de novo equipamento', NULL, 3),
(5, 24, NULL, 'concluido', '2025-08-02 02:50:46', NULL, NULL, 'instalação', 'Instalação de novo equipamento', NULL, 2),
(6, 25, NULL, 'pendente', '2025-08-02 02:57:38', NULL, NULL, 'instalação', 'Instalação de novo equipamento', NULL, 2),
(7, 24, NULL, 'pendente', '2025-08-02 03:02:46', NULL, NULL, 'corretiva', 'Verificar laço', NULL, 2),
(8, 26, NULL, 'pendente', '2025-08-02 03:17:50', NULL, NULL, 'instalação', 'Instalação de novo equipamento', NULL, 1),
(9, 27, NULL, 'pendente', '2025-08-02 03:20:08', NULL, NULL, 'instalação', 'Instalação de novo equipamento', NULL, 1),
(10, 29, NULL, 'em andamento', '2025-08-06 17:06:52', NULL, NULL, 'corretiva', 'teste', NULL, 2),
(11, 28, NULL, 'pendente', '2025-08-07 15:19:05', NULL, NULL, 'corretiva', 'Teste tecnico', NULL, 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `manutencoes_tecnicos`
--

CREATE TABLE `manutencoes_tecnicos` (
  `id_manutencao` int(11) NOT NULL,
  `id_tecnico` int(11) NOT NULL,
  `inicio_reparoTec` datetime DEFAULT NULL,
  `fim_reparoT` datetime DEFAULT NULL,
  `id_veiculo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `manutencoes_tecnicos`
--

INSERT INTO `manutencoes_tecnicos` (`id_manutencao`, `id_tecnico`, `inicio_reparoTec`, `fim_reparoT`, `id_veiculo`) VALUES
(10, 5, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `provedor`
--

CREATE TABLE `provedor` (
  `id_provedor` int(11) NOT NULL,
  `nome_prov` varchar(255) NOT NULL,
  `cidade_prov` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tecnicos`
--

CREATE TABLE `tecnicos` (
  `id_tecnico` int(11) NOT NULL,
  `especialidade` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  `senha_alterada` tinyint(1) DEFAULT 0,
  `tipo_usuario` enum('administrador','tecnico','provedor','comum') NOT NULL,
  `status_usuario` enum('ativo','inativo','pendente') DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome`, `email`, `senha`, `telefone`, `data_cadastro`, `senha_alterada`, `tipo_usuario`, `status_usuario`) VALUES
(1, 'Albert Igor Souza Pereira', 'albert@apsystem.com.br', 'Albert@igo7498', '62993094343', '2025-08-01 23:14:18', 1, 'administrador', 'ativo'),
(2, 'Sara carolina', 'sara@apsystem.com.br', '12345', '62982382799', '2025-08-02 15:12:50', 0, 'comum', 'ativo'),
(3, 'Maria vitoria', 'maria@apsystem.com.br', '12345', '(62) 9 9246-9568', '2025-08-02 15:15:30', 0, 'comum', 'ativo'),
(4, 'Pedro matos', 'pedro@apsystem.com.br', '12345', '(62) 9 8430-3673', '2025-08-02 15:17:59', 0, 'comum', 'ativo'),
(5, 'Kleyton dias', 'kleyton@apsystem.com.br', 'Albert@igo7498', '(62) 9 9393-9393', '2025-08-02 15:19:59', 1, 'tecnico', 'ativo'),
(6, 'Henrique', 'henrique@apsystem.com.br', '12345', '(62) 9 2929-2929', '2025-08-03 19:01:48', 0, 'tecnico', 'ativo'),
(7, 'Marcos', 'marcos@apsystem.com.br', '12345', '(62) 9 9292-9292', '2025-08-03 19:01:58', 0, 'tecnico', 'ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `veiculos`
--

CREATE TABLE `veiculos` (
  `id_veiculo` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `placa` varchar(255) NOT NULL,
  `modelo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `veiculos`
--

INSERT INTO `veiculos` (`id_veiculo`, `nome`, `placa`, `modelo`) VALUES
(1, 'Chevrolet Onix', 'ABC-1234', 'Onix Plus LTZ 1.0 AT'),
(2, 'Hyundai HB20', 'DEF-5678', 'Sense 1.0'),
(3, 'Volkswagen Gol', 'GHI-9012', '1.0 MPI'),
(4, 'Fiat Strada', 'JKL-3456', 'Freedom 1.3 CS Plus'),
(5, 'Toyota Corolla', 'MNO-7890', 'XEi 2.0');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `cidades`
--
ALTER TABLE `cidades`
  ADD PRIMARY KEY (`id_cidade`);

--
-- Índices de tabela `endereco`
--
ALTER TABLE `endereco`
  ADD PRIMARY KEY (`id_endereco`);

--
-- Índices de tabela `equipamentos`
--
ALTER TABLE `equipamentos`
  ADD PRIMARY KEY (`id_equipamento`),
  ADD UNIQUE KEY `referencia_equip` (`referencia_equip`),
  ADD KEY `fk_equipamento_cidade` (`id_cidade`),
  ADD KEY `fk_equipamento_endereco` (`id_endereco`);

--
-- Índices de tabela `manutencoes`
--
ALTER TABLE `manutencoes`
  ADD PRIMARY KEY (`id_manutencao`),
  ADD KEY `id_equipamento` (`id_equipamento`),
  ADD KEY `id_provedor` (`id_provedor`),
  ADD KEY `fk_manutencao_cidade` (`id_cidade`);

--
-- Índices de tabela `manutencoes_tecnicos`
--
ALTER TABLE `manutencoes_tecnicos`
  ADD PRIMARY KEY (`id_manutencao`,`id_tecnico`),
  ADD KEY `id_tecnico` (`id_tecnico`),
  ADD KEY `manutencoes_tecnicos_ibfk_3` (`id_veiculo`);

--
-- Índices de tabela `provedor`
--
ALTER TABLE `provedor`
  ADD PRIMARY KEY (`id_provedor`);

--
-- Índices de tabela `tecnicos`
--
ALTER TABLE `tecnicos`
  ADD PRIMARY KEY (`id_tecnico`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `veiculos`
--
ALTER TABLE `veiculos`
  ADD PRIMARY KEY (`id_veiculo`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cidades`
--
ALTER TABLE `cidades`
  MODIFY `id_cidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `endereco`
--
ALTER TABLE `endereco`
  MODIFY `id_endereco` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `equipamentos`
--
ALTER TABLE `equipamentos`
  MODIFY `id_equipamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de tabela `manutencoes`
--
ALTER TABLE `manutencoes`
  MODIFY `id_manutencao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `provedor`
--
ALTER TABLE `provedor`
  MODIFY `id_provedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `veiculos`
--
ALTER TABLE `veiculos`
  MODIFY `id_veiculo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `equipamentos`
--
ALTER TABLE `equipamentos`
  ADD CONSTRAINT `fk_equipamento_cidade` FOREIGN KEY (`id_cidade`) REFERENCES `cidades` (`id_cidade`),
  ADD CONSTRAINT `fk_equipamento_endereco` FOREIGN KEY (`id_endereco`) REFERENCES `endereco` (`id_endereco`);

--
-- Restrições para tabelas `manutencoes`
--
ALTER TABLE `manutencoes`
  ADD CONSTRAINT `fk_manutencao_cidade` FOREIGN KEY (`id_cidade`) REFERENCES `cidades` (`id_cidade`),
  ADD CONSTRAINT `manutencoes_ibfk_1` FOREIGN KEY (`id_equipamento`) REFERENCES `equipamentos` (`id_equipamento`),
  ADD CONSTRAINT `manutencoes_ibfk_3` FOREIGN KEY (`id_provedor`) REFERENCES `provedor` (`id_provedor`);

--
-- Restrições para tabelas `manutencoes_tecnicos`
--
ALTER TABLE `manutencoes_tecnicos`
  ADD CONSTRAINT `manutencoes_tecnicos_ibfk_1` FOREIGN KEY (`id_manutencao`) REFERENCES `manutencoes` (`id_manutencao`) ON DELETE CASCADE,
  ADD CONSTRAINT `manutencoes_tecnicos_ibfk_2` FOREIGN KEY (`id_tecnico`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `manutencoes_tecnicos_ibfk_3` FOREIGN KEY (`id_veiculo`) REFERENCES `veiculos` (`id_veiculo`);

--
-- Restrições para tabelas `tecnicos`
--
ALTER TABLE `tecnicos`
  ADD CONSTRAINT `tecnicos_ibfk_1` FOREIGN KEY (`id_tecnico`) REFERENCES `usuario` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
