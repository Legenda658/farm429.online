-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Май 13 2025 г., 20:15
-- Версия сервера: 8.0.25-15
-- Версия PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `u3073667_Farmsite`
--

-- --------------------------------------------------------

--
-- Структура таблицы `alarm_thresholds`
--

CREATE TABLE `alarm_thresholds` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `parameter_type` enum('temperature','humidity_air','humidity_soil','co2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_limit` decimal(8,2) NOT NULL,
  `max_limit` decimal(8,2) NOT NULL,
  `target_value` decimal(8,2) DEFAULT NULL COMMENT 'Целевое значение (если необходимо)',
  `tolerance` decimal(5,2) DEFAULT '1.00' COMMENT 'Допустимое отклонение',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `device_states`
--

CREATE TABLE `device_states` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `lamp_state` tinyint(1) DEFAULT '0',
  `curtains_state` tinyint(1) DEFAULT '0',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `event_log`
--

CREATE TABLE `event_log` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `farm_status`
--

CREATE TABLE `farm_status` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `temperature` float DEFAULT NULL,
  `humidity` float DEFAULT NULL,
  `light_level` float DEFAULT NULL,
  `curtains_level` decimal(4,2) DEFAULT NULL,
  `co2_level` float DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `photo_analysis` varchar(255) DEFAULT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `favorite_modes`
--

CREATE TABLE `favorite_modes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `preset_mode_id` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `lighting_schedule`
--

CREATE TABLE `lighting_schedule` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `required_hours` decimal(4,2) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_exception` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `text` text NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `analysis_image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `planting_events`
--

CREATE TABLE `planting_events` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` varchar(20) NOT NULL,
  `plant_name` varchar(100) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `planting_reminders`
--

CREATE TABLE `planting_reminders` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `reminder_date` date NOT NULL,
  `reminder_time` time DEFAULT NULL,
  `is_shown` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `preset_modes`
--

CREATE TABLE `preset_modes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `temperature` decimal(4,1) NOT NULL,
  `tolerance` decimal(2,1) NOT NULL DEFAULT '1.0',
  `humidity` int NOT NULL,
  `humidity_tolerance` decimal(2,1) NOT NULL DEFAULT '1.0',
  `light_hours` decimal(3,1) NOT NULL,
  `light_start` time NOT NULL,
  `light_end` time NOT NULL,
  `is_shared` tinyint(1) NOT NULL DEFAULT '0',
  `share_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Триггеры `preset_modes`
--
DELIMITER $$
CREATE TRIGGER `generate_share_code` BEFORE UPDATE ON `preset_modes` FOR EACH ROW BEGIN
  IF NEW.is_shared = 1 AND (OLD.is_shared = 0 OR OLD.is_shared IS NULL OR OLD.share_code IS NULL) THEN
    -- Генерируем случайный код из букв и цифр
    SET @chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    SET @code = '';
    SET @i = 0;
    WHILE @i < 8 DO
      SET @code = CONCAT(@code, SUBSTRING(@chars, FLOOR(1 + RAND() * 36), 1));
      SET @i = @i + 1;
    END WHILE;
    SET NEW.share_code = @code;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `schedule`
--

CREATE TABLE `schedule` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `curtains_schedule` tinyint(1) DEFAULT '0',
  `lighting_schedule` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `time` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `sensor_data`
--

CREATE TABLE `sensor_data` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `humidity` decimal(5,2) DEFAULT NULL,
  `co2` int NOT NULL COMMENT 'Уровень CO2',
  `soil_moisture` decimal(5,2) DEFAULT NULL,
  `light_level` decimal(8,2) DEFAULT NULL,
  `pressure` decimal(7,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Создано в',
  `curtains_state` tinyint(1) DEFAULT '0' COMMENT 'состояние занавесок (1 — открыты, 0 — закрыты)',
  `lamp_state` tinyint(1) DEFAULT '0' COMMENT 'состояние лампы (1 — включено, 0 — выключено)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `telegram_verifications`
--

CREATE TABLE `telegram_verifications` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `telegram_username` varchar(255) NOT NULL,
  `telegram_chat_id` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar` varchar(255) DEFAULT NULL,
  `telegram_username` varchar(255) DEFAULT NULL,
  `telegram_chat_id` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `api_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Триггеры `users`
--
DELIMITER $$
CREATE TRIGGER `after_user_register` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    CALL create_user_schedule(NEW.id);
END
$$
DELIMITER ;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `alarm_thresholds`
--
ALTER TABLE `alarm_thresholds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_parameter_unique` (`user_id`,`parameter_type`);

--
-- Индексы таблицы `device_states`
--
ALTER TABLE `device_states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `event_log`
--
ALTER TABLE `event_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_type` (`event_type`);

--
-- Индексы таблицы `farm_status`
--
ALTER TABLE `farm_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Индексы таблицы `favorite_modes`
--
ALTER TABLE `favorite_modes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_preset_unique` (`user_id`,`preset_mode_id`),
  ADD KEY `preset_mode_id` (`preset_mode_id`);

--
-- Индексы таблицы `lighting_schedule`
--
ALTER TABLE `lighting_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `planting_events`
--
ALTER TABLE `planting_events`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `planting_reminders`
--
ALTER TABLE `planting_reminders`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `preset_modes`
--
ALTER TABLE `preset_modes`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`time`);

--
-- Индексы таблицы `sensor_data`
--
ALTER TABLE `sensor_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`);

--
-- Индексы таблицы `telegram_verifications`
--
ALTER TABLE `telegram_verifications`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_idx` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `alarm_thresholds`
--
ALTER TABLE `alarm_thresholds`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `event_log`
--
ALTER TABLE `event_log`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `lighting_schedule`
--
ALTER TABLE `lighting_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `planting_events`
--
ALTER TABLE `planting_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `planting_reminders`
--
ALTER TABLE `planting_reminders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `preset_modes`
--
ALTER TABLE `preset_modes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `telegram_verifications`
--
ALTER TABLE `telegram_verifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
