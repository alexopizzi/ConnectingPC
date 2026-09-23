-- 0003 — Organizzazioni e tipi di organizzazione (base per l'ambito dei permessi).
-- Vault: "41 - Struttura del database" §3-4, "52 - Workflow organizzazioni e abilitazione" (assi di stato, D-009),
-- "53 - Workflow di pubblicazione" (publication_policy, D-022). Logo e tabelle collegate arrivano con v0.4.0.

CREATE TABLE IF NOT EXISTS organization_types (
    id             SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code           VARCHAR(50) NOT NULL,
    is_public_body TINYINT(1) NOT NULL DEFAULT 0,
    sort_order     SMALLINT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_organization_types_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS organization_type_translations (
    organization_type_id SMALLINT UNSIGNED NOT NULL,
    locale        VARCHAR(12) NOT NULL,
    name          VARCHAR(120) NOT NULL,
    status        ENUM('draft','machine','to_review','approved','outdated') NOT NULL DEFAULT 'draft',
    source_hash   CHAR(64) NULL,
    translated_by INT UNSIGNED NULL,
    reviewed_by   INT UNSIGNED NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (organization_type_id, locale),
    CONSTRAINT fk_org_type_tr_type FOREIGN KEY (organization_type_id) REFERENCES organization_types (id) ON DELETE CASCADE,
    CONSTRAINT fk_org_type_tr_locale FOREIGN KEY (locale) REFERENCES locales (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS organizations (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(255) NOT NULL,
    short_name           VARCHAR(80) NULL,
    legal_name           VARCHAR(255) NULL,
    tax_code             VARCHAR(20) NULL,
    organization_type_id SMALLINT UNSIGNED NOT NULL,
    is_community_based   TINYINT(1) NOT NULL DEFAULT 0,
    source_locale        VARCHAR(12) NOT NULL DEFAULT 'it',
    logo_file_id         INT UNSIGNED NULL,
    website              VARCHAR(500) NULL,
    census_status        ENUM('to_census','censused') NOT NULL DEFAULT 'to_census',
    listing_status       ENUM('hidden','listed') NOT NULL DEFAULT 'hidden',
    verification_status  ENUM('unverified','pending','verified','rejected') NOT NULL DEFAULT 'unverified',
    access_status        ENUM('not_enabled','to_enable','enabled','suspended','disabled') NOT NULL DEFAULT 'not_enabled',
    portal_edit_enabled  TINYINT(1) NOT NULL DEFAULT 0,
    publication_policy   ENUM('review','direct') NULL,
    status_note          VARCHAR(500) NULL,
    verified_at          DATETIME NULL,
    verified_by          INT UNSIGNED NULL,
    next_review_at       DATE NULL,
    publication_status   ENUM('draft','in_review','approved','published','rejected','archived') NOT NULL DEFAULT 'draft',
    published_at         DATETIME NULL,
    archived_at          DATETIME NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by           INT UNSIGNED NULL,
    updated_at           DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by           INT UNSIGNED NULL,
    KEY ix_organizations_listing (listing_status, publication_status),
    KEY ix_organizations_access (access_status),
    KEY ix_organizations_name (name),
    CONSTRAINT fk_organizations_type FOREIGN KEY (organization_type_id) REFERENCES organization_types (id),
    CONSTRAINT fk_organizations_source_locale FOREIGN KEY (source_locale) REFERENCES locales (code),
    CONSTRAINT fk_organizations_verified_by FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_organizations_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_organizations_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS organization_translations (
    organization_id    INT UNSIGNED NOT NULL,
    locale             VARCHAR(12) NOT NULL,
    slug               VARCHAR(190) NOT NULL,
    description        TEXT NULL,
    activities         TEXT NULL,
    participation_info TEXT NULL,
    status             ENUM('draft','machine','to_review','approved','outdated') NOT NULL DEFAULT 'draft',
    source_hash        CHAR(64) NULL,
    translated_by      INT UNSIGNED NULL,
    reviewed_by        INT UNSIGNED NULL,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (organization_id, locale),
    CONSTRAINT fk_org_tr_organization FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
    CONSTRAINT fk_org_tr_locale FOREIGN KEY (locale) REFERENCES locales (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
