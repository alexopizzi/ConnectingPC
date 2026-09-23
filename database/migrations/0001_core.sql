-- 0001 — Tabelle di sistema: impostazioni, lingue, stringhe dell'interfaccia, audit log, rate limiting.
-- Vault: "41 - Struttura del database" §1 e §9. Date in UTC.

CREATE TABLE IF NOT EXISTS settings (
    `key`       VARCHAR(100) NOT NULL PRIMARY KEY,
    value       JSON NOT NULL,
    updated_at  DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by  INT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Lingue dell'interfaccia e dei contenuti (D-007, D-023)
CREATE TABLE IF NOT EXISTS locales (
    code          VARCHAR(12) NOT NULL PRIMARY KEY,
    native_name   VARCHAR(60) NOT NULL,
    direction     ENUM('ltr','rtl') NOT NULL DEFAULT 'ltr',
    is_enabled    TINYINT(1) NOT NULL DEFAULT 0,
    is_public     TINYINT(1) NOT NULL DEFAULT 0,
    fallback_code VARCHAR(12) NULL,
    sort_order    SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Lingue parlate (filtri "chi parla la mia lingua"); nomi forniti da ICU
CREATE TABLE IF NOT EXISTS languages (
    code        VARCHAR(12) NOT NULL PRIMARY KEY,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    sort_order  SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Stringhe dell'interfaccia (fonte: italiano)
CREATE TABLE IF NOT EXISTS ui_strings (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(190) NOT NULL,
    context     VARCHAR(255) NULL,
    source_text TEXT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ui_strings_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS ui_string_translations (
    ui_string_id  INT UNSIGNED NOT NULL,
    locale        VARCHAR(12) NOT NULL,
    text          TEXT NOT NULL,
    status        ENUM('draft','machine','to_review','approved','outdated') NOT NULL DEFAULT 'draft',
    source_hash   CHAR(64) NULL,
    translated_by INT UNSIGNED NULL,
    reviewed_by   INT UNSIGNED NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ui_string_id, locale),
    KEY ix_ui_translations_locale (locale, status),
    CONSTRAINT fk_ui_translations_string FOREIGN KEY (ui_string_id) REFERENCES ui_strings (id) ON DELETE CASCADE,
    CONSTRAINT fk_ui_translations_locale FOREIGN KEY (locale) REFERENCES locales (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Audit log append-only (D-011)
CREATE TABLE IF NOT EXISTS audit_log (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    occurred_at       DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    request_id        CHAR(16) NULL,
    user_id           INT UNSIGNED NULL,
    organization_id   INT UNSIGNED NULL,
    action            VARCHAR(64) NOT NULL,
    entity_type       VARCHAR(40) NULL,
    entity_id         INT UNSIGNED NULL,
    changes           JSON NULL,
    approved_by       INT UNSIGNED NULL,
    change_request_id INT UNSIGNED NULL,
    ip_hash           CHAR(64) NULL,
    KEY ix_audit_entity (entity_type, entity_id, occurred_at),
    KEY ix_audit_user (user_id, occurred_at),
    KEY ix_audit_org (organization_id, occurred_at),
    KEY ix_audit_action (action, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Rate limiting a finestra fissa (login, reset password, API pubbliche)
CREATE TABLE IF NOT EXISTS rate_limits (
    bucket        VARCHAR(191) NOT NULL PRIMARY KEY,
    hits          INT UNSIGNED NOT NULL,
    window_start  DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
