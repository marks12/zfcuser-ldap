-- phpMyAdmin SQL Dump
-- version 4.1.0-beta2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 16, 2015 at 10:31 AM
-- Server version: 10.0.9-MariaDB-1~wheezy-log
-- PHP Version: 5.4.35-0+deb7u2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `inventpo`
--

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`id`, `parent_id`, `roleId`) VALUES(1, NULL, 'guest');
INSERT INTO `role` (`id`, `parent_id`, `roleId`) VALUES(2, NULL, 'user');
INSERT INTO `role` (`id`, `parent_id`, `roleId`) VALUES(3, NULL, 'InventPOAdmins');

--
-- Dumping data for table `users`
--

-- INSERT INTO `users` (`id`, `guid`, `username`, `email`, `displayName`, `password`) VALUES(1, 'f27099be5d8a86408524556f3bfc1ee0', 'mazura.va', 'mazura-va@energosib.ru', 'Мазура Вадим Анатольевич', '6c5bd6e46c87f65ca208954adbf6aff8');
-- INSERT INTO `users` (`id`, `guid`, `username`, `email`, `displayName`, `password`) VALUES(2, 'ed642aa51370824cb272e5ba8194bf32', 'tsarevnikov-vy', 'tsarevnikov-vy@energosib.ru', 'Царевников Владимир Юрьевич', '6c5bd6e46c87f65ca208954adbf6aff8');
-- INSERT INTO `users` (`id`, `guid`, `username`, `email`, `displayName`, `password`) VALUES(3, 'e223fca4f028f64387c6162eddfbd0e9', 'borodulin-ap', 'borodulin-ap@energosib.ru', 'Бородулин Андрей Павлович', '6c5bd6e46c87f65ca208954adbf6aff8');

--
-- Dumping data for table `user_role_linker`
--

--INSERT INTO `user_role_linker` (`user_id`, `role_id`) VALUES(1, 2);
--INSERT INTO `user_role_linker` (`user_id`, `role_id`) VALUES(2, 2);
--INSERT INTO `user_role_linker` (`user_id`, `role_id`) VALUES(2, 3);
--INSERT INTO `user_role_linker` (`user_id`, `role_id`) VALUES(3, 2);
