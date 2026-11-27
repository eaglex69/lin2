-- ============================================================================
--  Miniage v2 — та же схема для SQLite (режим превью без MySQL).
--  Типы упрощены, ENUM -> TEXT + CHECK, внешние ключи включены в Database.php.
-- ============================================================================

CREATE TABLE IF NOT EXISTS accounts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    nick         TEXT NOT NULL UNIQUE,
    pass_hash    TEXT NOT NULL,
    email        TEXT NOT NULL DEFAULT '',
    active_char  INTEGER NULL,
    role         TEXT NOT NULL DEFAULT 'player' CHECK (role IN ('player','gm','admin')),
    ip           TEXT NOT NULL DEFAULT '',
    created_at   TEXT NOT NULL DEFAULT (datetime('now')),
    last_login   TEXT NULL
);

CREATE TABLE IF NOT EXISTS characters (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id   INTEGER NOT NULL REFERENCES accounts (id) ON DELETE CASCADE,
    name         TEXT NOT NULL UNIQUE,
    race         TEXT NOT NULL DEFAULT 'human',
    class        TEXT NOT NULL DEFAULT 'fighter',
    gender       TEXT NOT NULL DEFAULT 'm',
    level        INTEGER NOT NULL DEFAULT 1,
    exp          INTEGER NOT NULL DEFAULT 0,
    hp           INTEGER NOT NULL DEFAULT 100,
    hp_max       INTEGER NOT NULL DEFAULT 100,
    mp           INTEGER NOT NULL DEFAULT 50,
    mp_max       INTEGER NOT NULL DEFAULT 50,
    p_atk        INTEGER NOT NULL DEFAULT 20,
    m_atk        INTEGER NOT NULL DEFAULT 10,
    p_def        INTEGER NOT NULL DEFAULT 10,
    m_def        INTEGER NOT NULL DEFAULT 8,
    crit         INTEGER NOT NULL DEFAULT 10,
    adena        INTEGER NOT NULL DEFAULT 500,
    x            INTEGER NOT NULL DEFAULT 0,
    y            INTEGER NOT NULL DEFAULT 0,
    city         TEXT NOT NULL DEFAULT 'Giran',
    wins         INTEGER NOT NULL DEFAULT 0,
    losses       INTEGER NOT NULL DEFAULT 0,
    pvp          INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT NOT NULL DEFAULT (datetime('now')),
    last_seen    TEXT NULL
);
CREATE INDEX IF NOT EXISTS idx_characters_account ON characters (account_id);

CREATE TABLE IF NOT EXISTS items (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    code         TEXT NOT NULL UNIQUE,
    name         TEXT NOT NULL,
    kind         TEXT NOT NULL DEFAULT 'weapon',
    slot         TEXT NOT NULL DEFAULT 'none',
    p_atk        INTEGER NOT NULL DEFAULT 0,
    m_atk        INTEGER NOT NULL DEFAULT 0,
    p_def        INTEGER NOT NULL DEFAULT 0,
    m_def        INTEGER NOT NULL DEFAULT 0,
    hp_bonus     INTEGER NOT NULL DEFAULT 0,
    mp_bonus     INTEGER NOT NULL DEFAULT 0,
    price        INTEGER NOT NULL DEFAULT 0,
    level_req    INTEGER NOT NULL DEFAULT 1,
    stackable    INTEGER NOT NULL DEFAULT 0,
    icon         TEXT NOT NULL DEFAULT 'sword'
);

CREATE TABLE IF NOT EXISTS inventory (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    char_id      INTEGER NOT NULL REFERENCES characters (id) ON DELETE CASCADE,
    item_id      INTEGER NOT NULL REFERENCES items (id) ON DELETE CASCADE,
    qty          INTEGER NOT NULL DEFAULT 1,
    equipped     INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_inventory_char ON inventory (char_id);

CREATE TABLE IF NOT EXISTS mobs (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    code         TEXT NOT NULL UNIQUE,
    name         TEXT NOT NULL,
    zone         TEXT NOT NULL,
    level        INTEGER NOT NULL DEFAULT 1,
    hp           INTEGER NOT NULL DEFAULT 50,
    p_atk        INTEGER NOT NULL DEFAULT 10,
    p_def        INTEGER NOT NULL DEFAULT 5,
    exp_reward   INTEGER NOT NULL DEFAULT 10,
    adena_reward INTEGER NOT NULL DEFAULT 100,
    sprite       TEXT NOT NULL DEFAULT 'slime',
    color        TEXT NOT NULL DEFAULT '6ab04c',
    is_boss      INTEGER NOT NULL DEFAULT 0,
    respawn_sec  INTEGER NOT NULL DEFAULT 20
);
CREATE INDEX IF NOT EXISTS idx_mobs_zone ON mobs (zone);

CREATE TABLE IF NOT EXISTS battles (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    char_id      INTEGER NOT NULL REFERENCES characters (id) ON DELETE CASCADE,
    mob_id       INTEGER NOT NULL REFERENCES mobs (id) ON DELETE CASCADE,
    mob_hp       INTEGER NOT NULL,
    turn         INTEGER NOT NULL DEFAULT 1,
    log_json     TEXT NOT NULL,
    state        TEXT NOT NULL DEFAULT 'active',
    won_at       TEXT NULL,
    created_at   TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at   TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_battles_char_state ON battles (char_id, state);
-- мировой респавн ищет последнюю победу над мобом любого игрока
CREATE INDEX IF NOT EXISTS idx_battles_mob_won ON battles (mob_id, state, won_at);

CREATE TABLE IF NOT EXISTS chat_messages (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    channel      TEXT NOT NULL DEFAULT 'global',
    char_id      INTEGER NULL REFERENCES characters (id) ON DELETE SET NULL,
    author       TEXT NOT NULL,
    text         TEXT NOT NULL,
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_chat_channel_id ON chat_messages (channel, id);

CREATE TABLE IF NOT EXISTS news (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    title        TEXT NOT NULL,
    body         TEXT NOT NULL,
    author       TEXT NOT NULL DEFAULT 'Admin',
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS sessions (
    id           TEXT PRIMARY KEY,
    account_id   INTEGER NULL,
    data_json    TEXT NOT NULL,
    last_used    TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Счётчик неудачных попыток входа: защита от подбора пароля.
CREATE TABLE IF NOT EXISTS login_attempts (
    nick           TEXT PRIMARY KEY,
    cnt            INTEGER NOT NULL DEFAULT 0,
    first_ts       INTEGER NOT NULL,
    blocked_until  TEXT NULL
);

CREATE TABLE IF NOT EXISTS online (
    char_id      INTEGER PRIMARY KEY REFERENCES characters (id) ON DELETE CASCADE,
    last_ts      INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS level_exp (
    level        INTEGER PRIMARY KEY,
    exp_total    INTEGER NOT NULL
);

INSERT OR IGNORE INTO level_exp (level, exp_total) VALUES
(1,0),(2,67),(3,297),(4,805),(5,1716),(6,3154),(7,5112),(8,7700),(9,11000),(10,15200),
(11,20500),(12,27000),(13,34800),(14,44000),(15,55000),(16,67800),(17,83000),(18,101000),
(19,122000),(20,147000),(21,176000),(22,210000),(23,249000),(24,294000),(25,346000),
(26,405000),(27,472000),(28,548000),(29,634000),(30,731000),(31,840000),(32,962000),
(33,1098000),(34,1249000),(35,1417000),(36,1603000),(37,1808000),(38,2034000),
(39,2282000),(40,2554000),(41,2852000),(42,3178000),(43,3534000),(44,3922000),
(45,4345000),(46,4806000),(47,5308000),(48,5854000),(49,6448000),(50,7093000);

INSERT OR IGNORE INTO news (id, title, body) VALUES
(1, 'Miniage переписан на PHP 8.1', 'Игра полностью переехала на PHP 8.1 + MySQL + AJAX + Canvas. Прежние страницы *.php удалены, интерфейс теперь рисуется на canvas и общается с сервером через JSON API.'),
(2, 'Новые окрестности', 'Добавлены зоны: Forest of Mirrors, Death Pass, Dragon Valley. В Canvas-режиме по монстрам можно кликать мышью.');
