-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 04/08/2025 às 01:11
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
-- Estrutura para tabela `manutencoes_tecnicos`
--

CREATE TABLE `manutencoes_tecnicos` (
  `id_manutencao` int(11) NOT NULL,
  `id_tecnico` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `manutencoes_tecnicos`
--
ALTER TABLE `manutencoes_tecnicos`
  ADD PRIMARY KEY (`id_manutencao`,`id_tecnico`),
  ADD KEY `id_tecnico` (`id_tecnico`);

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `manutencoes_tecnicos`
--
ALTER TABLE `manutencoes_tecnicos`
  ADD CONSTRAINT `manutencoes_tecnicos_ibfk_1` FOREIGN KEY (`id_manutencao`) REFERENCES `manutencoes` (`id_manutencao`) ON DELETE CASCADE,
  ADD CONSTRAINT `manutencoes_tecnicos_ibfk_2` FOREIGN KEY (`id_tecnico`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
