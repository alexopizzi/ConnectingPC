-- 0002 — Utenti, ruoli, permessi, assegnazioni con ambito (D-008), token monouso.
-- Vault: "41 - Struttura del database" §2, "50 - Autenticazione", "51 - Ruoli e permessi".
-- I tentativi di login falliti sono gestiti da rate_limits (0001), non da colonne su users.

CREATE TABLE IF NOT EXISTS users (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email               VARCHAR(254) NOT NULL,
    password_hash       VARCHAR(255) NULL,
    display_name        VARCHAR(120) NOT NULL,
    preferred_locale    VARCHAR(12) NULL,
    status              ENUM('invited','active','suspended','disabled') NOT NULL DEFAULT 'invited',
    email_verified_at   DATETIME NULL,
    password_changed_at DATETIME NULL,
    last_login_at       DATETIME NULL,
    session_version     INT UNSIGNED NOT NULL DEFAULT 1,
    mfa_secret_enc      VARBINARY(255) NULL,
    mfa_enabled_at      DATETIME NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by          INT UNSIGNED NULL,
    updated_at          DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by          INT UNSIGNED NULL,
    UNIQUE KEY uq_users_email (email),
    KEY ix_users_status (status),
    CONSTRAINT fk_users_locale FOREIGN KEY (preferred_locale) REFERENCES locales (code) ON DELETE SET NULL,
    CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_users_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS roles (
    id             SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code           VARCHAR(50) NOT NULL,
    name           VARCHAR(100) NOT NULL,
    description    VARCHAR(255) NULL,
    allowed_scopes SET('global','organization','category','territory','locale') NOT NULL,
    is_system      TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_roles_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id          SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    UNIQUE KEY uq_permissions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id       SMALLINT UNSIGNED NOT NULL,
    permission_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Appartenenza + autorizzazione con ambito: scope_key = id dell'ambito ('' per global, codice per locale)
CREATE TABLE IF NOT EXISTS role_assignments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    role_id     SMALLINT UNSIGNED NOT NULL,
    scope_type  ENUM('global','organization','category','territory','locale') NOT NULL,
    scope_key   VARCHAR(20) NOT NULL DEFAULT '',
    granted_by  INT UNSIGNED NULL,
    granted_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at  DATETIME NULL,
    revoked_at  DATETIME NULL,
    revoked_by  INT UNSIGNED NULL,
    UNIQUE KEY uq_role_assignment (user_id, role_id, scope_type, scope_key),
    KEY ix_role_assignments_scope (scope_type, scope_key),
    CONSTRAINT fk_role_assignments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_role_assignments_role FOREIGN KEY (role_id) REFERENCES roles (id),
    CONSTRAINT fk_role_assignments_granted_by FOREIGN KEY (granted_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_role_assignments_revoked_by FOREIGN KEY (revoked_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Token monouso: nel database solo l'hash SHA-256; il token in chiaro esiste solo nel link inviato
CREATE TABLE IF NOT EXISTS auth_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    purpose     ENUM('invite','password_reset','email_change') NOT NULL,
    token_hash  CHAR(64) NOT NULL,
    payload     JSON NULL,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_auth_tokens_hash (token_hash),
    KEY ix_auth_tokens_user (user_id, purpose),
    CONSTRAINT fk_auth_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
