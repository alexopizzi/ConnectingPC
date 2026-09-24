-- 0005 — Comunità, paesi, ambiti di mediazione, mediatori (vault "41" §3, §7; "63 - Mediatori"; "64 - Associazioni e comunità").

CREATE TABLE IF NOT EXISTS communities (
    id         SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(60) NOT NULL,
    kind       ENUM('national','linguistic','cultural','religious','regional','intercultural','other') NOT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_communities_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS community_translations (
    community_id  SMALLINT UNSIGNED NOT NULL,
    locale        VARCHAR(12) NOT NULL,
    name          VARCHAR(120) NOT NULL,
    description   VARCHAR(500) NULL,
    status        ENUM('draft','machine','to_review','approved','outdated') NOT NULL DEFAULT 'draft',
    source_hash   CHAR(64) NULL,
    translated_by INT UNSIGNED NULL,
    reviewed_by   INT UNSIGNED NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (community_id, locale),
    CONSTRAINT fk_community_tr_community FOREIGN KEY (community_id) REFERENCES communities (id) ON DELETE CASCADE,
    CONSTRAINT fk_community_tr_locale FOREIGN KEY (locale) REFERENCES locales (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Paesi ISO 3166-1 alpha-2 (nomi forniti da ICU)
CREATE TABLE IF NOT EXISTS community_countries (
    community_id SMALLINT UNSIGNED NOT NULL,
    country_code CHAR(2) NOT NULL,
    PRIMARY KEY (community_id, country_code),
    CONSTRAINT fk_community_countries_community FOREIGN KEY (community_id) REFERENCES communities (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS organization_communities (
    organization_id INT UNSIGNED NOT NULL,
    community_id    SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (organization_id, community_id),
    CONSTRAINT fk_org_communities_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
    CONSTRAINT fk_org_communities_community FOREIGN KEY (community_id) REFERENCES communities (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS organization_countries (
    organization_id INT UNSIGNED NOT NULL,
    country_code    CHAR(2) NOT NULL,
    PRIMARY KEY (organization_id, country_code),
    CONSTRAINT fk_org_countries_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS mediation_domains (
    id         SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(50) NOT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_mediation_domains_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS mediation_domain_translations (
    mediation_domain_id SMALLINT UNSIGNED NOT NULL,
    locale        VARCHAR(12) NOT NULL,
    name          VARCHAR(120) NOT NULL,
    status        ENUM('draft','machine','to_review','approved','outdated') NOT NULL DEFAULT 'draft',
    source_hash   CHAR(64) NULL,
    translated_by INT UNSIGNED NULL,
    reviewed_by   INT UNSIGNED NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (mediation_domain_id, locale),
    CONSTRAINT fk_med_domain_tr_domain FOREIGN KEY (mediation_domain_id) REFERENCES mediation_domains (id) ON DELETE CASCADE,
    CONSTRAINT fk_med_domain_tr_locale FOREIGN KEY (locale) REFERENCES locales (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Mediatori: dati di persone fisiche, visibilità restrittiva per impostazione predefinita (D-013)
CREATE TABLE IF NOT EXISTS mediators (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name           VARCHAR(80) NOT NULL,
    last_name            VARCHAR(80) NOT NULL,
    public_display_name  VARCHAR(120) NULL,
    organization_id      INT UNSIGNED NULL,
    mediation_types      SET('linguistic','cultural','intercultural') NOT NULL,
    availability         ENUM('available','limited','unavailable','unknown') NOT NULL DEFAULT 'unknown',
    profile_visibility   ENUM('public','operators','admin') NOT NULL DEFAULT 'admin',
    public_consent_at    DATETIME NULL,
    consent_reference    VARCHAR(255) NULL,
    verification_status  ENUM('unverified','pending','verified','rejected') NOT NULL DEFAULT 'unverified',
    qualifications_admin TEXT NULL,
    admin_notes          TEXT NULL,
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
    KEY ix_mediators_visibility (publication_status, profile_visibility),
    CONSTRAINT fk_mediators_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE SET NULL,
    CONSTRAINT fk_mediators_verified_by FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_mediators_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_mediators_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS mediator_translations (
    mediator_id        INT UNSIGNED NOT NULL,
    locale             VARCHAR(12) NOT NULL,
    bio                TEXT NULL,
    competences        TEXT NULL,
    availability_notes TEXT NULL,
    status             ENUM('draft','machine','to_review','approved','outdated') NOT NULL DEFAULT 'draft',
    source_hash        CHAR(64) NULL,
    translated_by      INT UNSIGNED NULL,
    reviewed_by        INT UNSIGNED NULL,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (mediator_id, locale),
    CONSTRAINT fk_mediator_tr_mediator FOREIGN KEY (mediator_id) REFERENCES mediators (id) ON DELETE CASCADE,
    CONSTRAINT fk_mediator_tr_locale FOREIGN KEY (locale) REFERENCES locales (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS mediator_languages (
    mediator_id   INT UNSIGNED NOT NULL,
    language_code VARCHAR(12) NOT NULL,
    proficiency   ENUM('native','c2','c1','b2','b1') NOT NULL,
    PRIMARY KEY (mediator_id, language_code),
    KEY ix_mediator_languages_code (language_code),
    CONSTRAINT fk_mediator_languages_mediator FOREIGN KEY (mediator_id) REFERENCES mediators (id) ON DELETE CASCADE,
    CONSTRAINT fk_mediator_languages_language FOREIGN KEY (language_code) REFERENCES languages (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS mediator_domains (
    mediator_id         INT UNSIGNED NOT NULL,
    mediation_domain_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (mediator_id, mediation_domain_id),
    CONSTRAINT fk_mediator_domains_mediator FOREIGN KEY (mediator_id) REFERENCES mediators (id) ON DELETE CASCADE,
    CONSTRAINT fk_mediator_domains_domain FOREIGN KEY (mediation_domain_id) REFERENCES mediation_domains (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS mediator_territories (
    mediator_id  INT UNSIGNED NOT NULL,
    territory_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (mediator_id, territory_id),
    CONSTRAINT fk_mediator_territories_mediator FOREIGN KEY (mediator_id) REFERENCES mediators (id) ON DELETE CASCADE,
    CONSTRAINT fk_mediator_territories_territory FOREIGN KEY (territory_id) REFERENCES territories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
