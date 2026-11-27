-- ============================================================================
--  Miniage v2 — схема БД для MySQL 8.0+ (utf8mb4, InnoDB)
--  Применение:  mysql -u root -p < v2/db/schema.mysql.sql
--  Оптимизированный минимум из legacy shema.sql (144 таблицы): только то,
--  что реально используется игровым ядром, API и Canvas-клиентом.
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+03:00';

CREATE TABLE IF NOT EXISTS accounts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nick         VARCHAR(32)  NOT NULL,
    pass_hash    CHAR(255)    NOT NULL,               -- password_hash(ARGON2ID)
    email        VARCHAR(190) NOT NULL DEFAULT '',
    active_char  INT UNSIGNED NULL,                   -- выбранный персонаж
    role         ENUM('player','gm','admin') NOT NULL DEFAULT 'player',
    ip           VARCHAR(45)  NOT NULL DEFAULT '',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login   DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_accounts_nick (nick),
    KEY idx_accounts_active (active_char)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS characters (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id   INT UNSIGNED NOT NULL,
    name         VARCHAR(32)  NOT NULL,
    race         ENUM('human','elf','darkelf','orc','dwarf','kamael') NOT NULL DEFAULT 'human',
    class        ENUM('fighter','mage','archer','healer') NOT NULL DEFAULT 'fighter',
    gender       ENUM('m','f') NOT NULL DEFAULT 'm',
    level        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    exp          BIGINT UNSIGNED   NOT NULL DEFAULT 0,
    hp           INT UNSIGNED NOT NULL DEFAULT 100,
    hp_max       INT UNSIGNED NOT NULL DEFAULT 100,
    mp           INT UNSIGNED NOT NULL DEFAULT 50,
    mp_max       INT UNSIGNED NOT NULL DEFAULT 50,
    p_atk        INT UNSIGNED NOT NULL DEFAULT 20,
    m_atk        INT UNSIGNED NOT NULL DEFAULT 10,
    p_def        INT UNSIGNED NOT NULL DEFAULT 10,
    m_def        INT UNSIGNED NOT NULL DEFAULT 8,
    crit         TINYINT UNSIGNED NOT NULL DEFAULT 10,  -- %
    adena        BIGINT UNSIGNED NOT NULL DEFAULT 30000000,
    x            SMALLINT NOT NULL DEFAULT 0,           -- координаты на Canvas-карте
    y            SMALLINT NOT NULL DEFAULT 0,
    city         VARCHAR(48) NOT NULL DEFAULT 'Giran',
    wins         INT UNSIGNED NOT NULL DEFAULT 0,
    losses       INT UNSIGNED NOT NULL DEFAULT 0,
    pvp          INT UNSIGNED NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen    DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_characters_name (name),
    KEY idx_characters_account (account_id),
    CONSTRAINT fk_characters_account FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         VARCHAR(48) NOT NULL,
    name         VARCHAR(96) NOT NULL,
    kind         ENUM('weapon','armor','potion','material','quest') NOT NULL DEFAULT 'weapon',
    slot         ENUM('none','weapon','armor','helmet','boots','ring') NOT NULL DEFAULT 'none',
    p_atk        INT NOT NULL DEFAULT 0,
    m_atk        INT NOT NULL DEFAULT 0,
    p_def        INT NOT NULL DEFAULT 0,
    m_def        INT NOT NULL DEFAULT 0,
    hp_bonus     INT NOT NULL DEFAULT 0,
    mp_bonus     INT NOT NULL DEFAULT 0,
    price        INT UNSIGNED NOT NULL DEFAULT 0,
    level_req    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    stackable    TINYINT(1) NOT NULL DEFAULT 0,
    icon         VARCHAR(48) NOT NULL DEFAULT 'sword',
    PRIMARY KEY (id),
    UNIQUE KEY uq_items_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    char_id      INT UNSIGNED NOT NULL,
    item_id      INT UNSIGNED NOT NULL,
    qty          INT UNSIGNED NOT NULL DEFAULT 1,
    equipped     TINYINT(1) NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inventory_char (char_id),
    KEY idx_inventory_item (item_id),
    CONSTRAINT fk_inventory_char FOREIGN KEY (char_id) REFERENCES characters (id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobs (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         VARCHAR(48) NOT NULL,
    name         VARCHAR(96) NOT NULL,
    zone         VARCHAR(48) NOT NULL,
    level        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    hp           INT UNSIGNED NOT NULL DEFAULT 50,
    p_atk        INT UNSIGNED NOT NULL DEFAULT 10,
    p_def        INT UNSIGNED NOT NULL DEFAULT 5,
    exp_reward   INT UNSIGNED NOT NULL DEFAULT 10,
    adena_reward INT UNSIGNED NOT NULL DEFAULT 100,
    sprite       VARCHAR(48) NOT NULL DEFAULT 'slime',
    color        CHAR(6) NOT NULL DEFAULT '6ab04c',
    is_boss      TINYINT(1) NOT NULL DEFAULT 0,
    respawn_sec  INT UNSIGNED NOT NULL DEFAULT 20,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mobs_code (code),
    KEY idx_mobs_zone (zone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS battles (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    char_id      INT UNSIGNED NOT NULL,
    mob_id       INT UNSIGNED NOT NULL,
    mob_hp       INT UNSIGNED NOT NULL,
    turn         INT UNSIGNED NOT NULL DEFAULT 1,
    log_json     MEDIUMTEXT NOT NULL,
    state        ENUM('active','won','lost','fled') NOT NULL DEFAULT 'active',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_battles_char_state (char_id, state),
    CONSTRAINT fk_battles_char FOREIGN KEY (char_id) REFERENCES characters (id) ON DELETE CASCADE,
    CONSTRAINT fk_battles_mob FOREIGN KEY (mob_id) REFERENCES mobs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    channel      ENUM('global','trade','clan','system') NOT NULL DEFAULT 'global',
    char_id      INT UNSIGNED NULL,
    author       VARCHAR(32) NOT NULL,
    text         VARCHAR(300) NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chat_channel_id (channel, id),
    CONSTRAINT fk_chat_char FOREIGN KEY (char_id) REFERENCES characters (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title        VARCHAR(160) NOT NULL,
    body         TEXT NOT NULL,
    author       VARCHAR(32) NOT NULL DEFAULT 'Admin',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
    id           CHAR(64) NOT NULL,
    account_id   INT UNSIGNED NULL,
    data_json    TEXT NOT NULL,
    last_used    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sessions_account (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Счётчик неудачных попыток входа: защита от подбора пароля.
CREATE TABLE IF NOT EXISTS login_attempts (
    nick           VARCHAR(64) NOT NULL,
    cnt            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    first_ts       INT UNSIGNED NOT NULL,
    blocked_until  DATETIME NULL,
    PRIMARY KEY (nick),
    KEY idx_login_attempts_blocked (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS online (
    char_id      INT UNSIGNED NOT NULL,
    last_ts      INT UNSIGNED NOT NULL,
    PRIMARY KEY (char_id),
    CONSTRAINT fk_online_char FOREIGN KEY (char_id) REFERENCES characters (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS level_exp (
    level        TINYINT UNSIGNED NOT NULL,
    exp_total    BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Демо-наполнение -----------------------------------------------------------
INSERT IGNORE INTO level_exp (level, exp_total) VALUES
(1,0),(2,67),(3,297),(4,805),(5,1716),(6,3154),(7,5112),(8,7700),(9,11000),(10,15200),
(11,20500),(12,27000),(13,34800),(14,44000),(15,55000),(16,67800),(17,83000),(18,101000),
(19,122000),(20,147000),(21,176000),(22,210000),(23,249000),(24,294000),(25,346000),
(26,405000),(27,472000),(28,548000),(29,634000),(30,731000),(31,840000),(32,962000),
(33,1098000),(34,1249000),(35,1417000),(36,1603000),(37,1808000),(38,2034000),
(39,2282000),(40,2554000),(41,2852000),(42,3178000),(43,3534000),(44,3922000),
(45,4345000),(46,4806000),(47,5308000),(48,5854000),(49,6448000),(50,7093000);

INSERT IGNORE INTO news (id, title, body) VALUES
(1, 'Miniage переписан на PHP 8.1', 'Игра полностью переехала на PHP 8.1 + MySQL + AJAX + Canvas. Прежние страницы *.php удалены, интерфейс теперь рисуется на canvas и общается с сервером через JSON API.'),
(2, 'Новые окрестности', 'Добавлены зоны: Forest of Mirrors, Death Pass, Dragon Valley. В Canvas-режиме по монстрам можно кликать мышью.');
