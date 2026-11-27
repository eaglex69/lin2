# Miniage — браузерная MMORPG

Игра переписана с legacy PHP 5 (230 разрозненных `*.php` на `mysql_*` и WAP-вёрстке)
на современный стек: **PHP 8.1 + MySQL 8 + AJAX + Canvas**.

Весь код — в каталоге [`v2/`](./v2). Там же подробная документация:
**[v2/README.md](./v2/README.md)**.

## Что внутри

| Слой | Технологии |
|---|---|
| Бэкенд | PHP 8.1, `strict_types`, PSR-4, фронт-контроллер, PDO + prepared statements |
| БД | MySQL 8 (utf8mb4, InnoDB, внешние ключи), SQLite как fallback |
| API | JSON `/api/*`, CSRF, HTTP-статусы, `password_hash` вместо MD5 |
| Фронтенд | Canvas 2D (карта, монстры, эффекты), fetch/AJAX, без фреймворков |
| UI | тёмная «lineage»-тема, адаптив, звук через WebAudio |

## Быстрый старт

```bash
# production
mysql -u root -p < v2/db/schema.mysql.sql
cd v2 && cp .env.example .env      # DB_HOST/DB_NAME/DB_USER/DB_PASS
# DocumentRoot -> v2/public   (Apache .htaccess уже настроен)
```

```bash
# локально без MySQL
cd v2
DB_DRIVER=sqlite php -S 0.0.0.0:8080 -t public
```

```bash
# превью там, где нет PHP вообще (PHP 8.1 в WebAssembly + SQLite)
cd v2/preview && npm install && npm start   # http://localhost:3000
```

Демо-вход: **demo / demo** (также `admin / admin`).

## Скриншоты интерфейса

```
┌────────────────────────────────────────────────────────────┐
│ miniage ru   [HP ████████] [MP ██████] [EXP ██]   30 000 000│
│                                                            │
│  🗺   ·   Зелёный слизень [1]      ·        ·    Дрейк [20] │
│  ⚔        ·            Полевая крыса [2]                   │
│  🛒   ·   ·        ⚔ персонаж                    ·   ·     │
│  🎒        ·   ·          ·      ·                         │
│  💬                        [ Зелёный слизень [1]  ход 2 ]  │
│  🏆                        [Атаковать] [Отступить]         │
└────────────────────────────────────────────────────────────┘
```

## Структура репозитория

```
v2/          ← актуальная версия (PHP 8.1 + MySQL + AJAX + Canvas)
shema.sql    ← архивная схема legacy-версии (144 таблицы), для истории
theme/       ← старые изображения (не используются)
images/      ← старые изображения (не используются)
```

Legacy-файлы удалены, но доступны в истории git:

```bash
git log --all --oneline -- '*.php'    # история старых страниц
git show <commit>:gorod.php           # посмотреть конкретный старый файл
```
