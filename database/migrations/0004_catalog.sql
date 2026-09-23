-- 0004 — Catalogo: territori, bisogni, categorie, sedi, servizi, lingue parlate, contatti, orari, dizionario di ricerca.
-- Vault: "41 - Struttura del database" §3, §4, §5, §6; "62 - Ricerca per bisogno"; "65 - Catalogo servizi".

CREATE TABLE IF NOT EXISTS territories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id       INT UNSIGNED NULL,
    type            ENUM('region','province','municipality','district','area','locality') NOT NULL,
    istat_code      VARCHAR(10) NULL,
    name            VARCHAR(150) NOT NULL,
    is_project_area TINYINT(1) NOT NULL DEFAULT 0,
    centroid_lat    DECIMAL(9,6) NULL,
    centroid_lng    DECIMAL(9,6) NULL,
    UNIQUE KEY uq_territories_istat (istat_code),
    UNIQUE KEY uq_territories_name (type, name),
    CONSTRAINT fk_territories_parent FOREIGN KEY (parent_id) REFERENCES territories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS needs (
    id          SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(50) NOT NULL,
    parent_id   SMALLINT UNSIGNED NULL,
    icon        VARCHAR(50) NOT NULL,
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_needs_code (code),
    CONSTRAINT fk_needs_parent FOREIGN KEY (parent_id) REFERENCES needs (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS need_translations (
    need_id       SMALLINT UNSIGNED NOT NULL,
    locale        VARCHAR(12) NOT NULL,
    label         VARCHAR(120) NOT NULL,
    description   VARCHAR(500) NULL,
    status        ENUM('draft','machine','to_review','approved','outdated') NOT NULL DEFAULT 'draft',
    source_hash   CHAR(64) NULL,
    translated_by INT UNSIGNED NULL,
    reviewed_by   INT UNSIGNED NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (need_id, locale),
    CONSTRAINT fk_need_tr_need FOREIGN KEY (need_id) REFERENCES needs (id) ON DELETE CASCADE,
    CONSTRAINT fk_need_tr_locale FOREIGN KEY (locale) REFERENCES locales (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS categories (
    id          SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   SMALLINT UNSIGNED NULL,
    code        VARCHAR(60) NOT NULL,
    icon        VARCHAR(50) NULL,
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_categories_code (code),
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS category_translations (
    category_id   SMALLINT UNSIGNED NOT NULL,
    locale        VARCHAR(12) NOT NULL,
    name          VARCHAR(120) NOT NULL,
    description   VARCHAR(500) NULL,
    status        ENUM('draft','machine','to_review','approved','outdated') NOT NULL DEFAULT 'draft',
    source_hash   CHAR(64) NULL,
    translated_by INT UNSIGNED NULL,
    reviewed_by   INT UNSIGNED NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (category_id, locale),
    CONSTRAINT fk_category_tr_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE,
    CONSTRAINT fk_category_tr_locale FOREIGN KEY (locale) REFERENCES locales (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS need_category (
    need_id     SMALLINT UNSIGNED NOT NULL,
    category_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (need_id, category_id),
    CONSTRAINT fk_need_category_need FOREIGN KEY (need_id) REFERENCES needs (id) ON DELETE CASCADE,
    CONSTRAINT fk_need_category_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Lingue parlate dalle organizzazioni
CREATE TABLE IF NOT EXISTS organization_languages (
    organization_id INT UNSIGNED NOT NULL,
    language_code   VARCHAR(12) NOT NULL,
    PRIMARY KEY (organization_id, language_code),
    CONSTRAINT fk_org_languages_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
    CONSTRAINT fk_org_languages_language FOREIGN KEY (language_code) REFERENCES languages (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS organization_territories (
    organization_id INT UNSIGNED NOT NULL,
    territory_id    INT UNSIGNED NOT NULL,
    PRIMARY KEY (organization_id, territory_id),
    CONSTRAINT fk_org_territories_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
    CONSTRAINT fk_org_territories_territory FOREIGN KEY (territory_id) REFERENCES territories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS sites (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id   INT UNSIGNED NOT NULL,
    name              VARCHAR(190) NULL,
    address_line      VARCHAR(255) NOT NULL,
    postal_code       VARCHAR(10) NULL,
    territory_id      INT UNSIGNED NOT NULL,
    lat               DECIMAL(9,6) NULL,
    lng               DECIMAL(9,6) NULL,
    geo_source        ENUM('manual','geocoder','import') NULL,
    geo_checked       TINYINT(1) NOT NULL DEFAULT 0,
    is_public_place   TINYINT(1) NOT NULL DEFAULT 1,
    step_free_access  ENUM('yes','partial','no','unknown') NOT NULL DEFAULT 'unknown',
    accessible_toilet ENUM('yes','no','unknown') NOT NULL DEFAULT 'unknown',
    verified_at       DATETIME NULL,
    verified_by       INT UNSIGNED NULL,
    next_review_at    DATE NULL,
    publication_status ENUM('draft','in_review','approved','published','rejected','archived') NOT NULL DEFAULT 'draft',
    published_at      DATETIME NULL,
    archived_at       DATETIME NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by        INT UNSIGNED NULL,
    updated_at        DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by        INT UNSIGNED NULL,
    KEY ix_sites_org (organization_id),
    KEY ix_sites_geo (lat, lng),
    CONSTRAINT fk_sites_org FOREIGN KEY (organization_id) REFERENCES organizations (id),
    CONSTRAINT fk_sites_territory FOREIGN KEY (territory_id) REFERENCES territories (id),
    CONSTRAINT fk_sites_verified_by FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_sites_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_sites_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS site_translations (
    site_id             INT UNSIGNED NOT NULL,
    locale              VARCHAR(12) NOT NULL,
    directions          TEXT NULL,
    accessibility_notes TEXT NULL,
    hours_notes         TEXT NULL,
    status              ENUM('draft','machine','to_review','approved','outdated') NOT NULL DEFAULT 'draft',
    source_hash         CHAR(64) NULL,
    translated_by       INT UNSIGNED NULL,
    reviewed_by         INT UNSIGNED NULL,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (site_id, locale),
    CONSTRAINT fk_site_tr_site FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE CASCADE,
    CONSTRAINT fk_site_tr_locale FOREIGN KEY (locale) REFERENCES locales (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS services (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id      INT UNSIGNED NOT NULL,
    primary_category_id  SMALLINT UNSIGNED NOT NULL,
    source_locale        VARCHAR(12) NOT NULL DEFAULT 'it',
    access_modes         SET('in_person','phone','online','email','home_visit') NOT NULL DEFAULT 'in_person',
    booking              ENUM('not_needed','recommended','required') NOT NULL DEFAULT 'not_needed',
    booking_url          VARCHAR(500) NULL,
    cost_type            ENUM('free','paid','partly_free','unknown') NOT NULL DEFAULT 'unknown',
    mediation            ENUM('available','on_request','not_available','unknown') NOT NULL DEFAULT 'unknown',
    online_url           VARCHAR(500) NULL,
    valid_from           DATE NULL,
    valid_to             DATE NULL,
    review_interval_days SMALLINT UNSIGNED NULL,
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
    KEY ix_services_pub (publication_status, organization_id),
    KEY ix_services_review (next_review_at),
    CONSTRAINT fk_services_org FOREIGN KEY (organization_id) REFERENCES organizations (id),
    CONSTRAINT fk_services_category FOREIGN KEY (primary_category_id) REFERENCES categories (id),
    CONSTRAINT fk_services_source_locale FOREIGN KEY (source_locale) REFERENCES locales (code),
    CONSTRAINT fk_services_verified_by FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_services_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_services_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS service_translations (
    service_id      INT UNSIGNED NOT NULL,
    locale          VARCHAR(12) NOT NULL,
    slug            VARCHAR(190) NOT NULL,
    name            VARCHAR(255) NOT NULL,
    summary         VARCHAR(500) NULL,
    description     TEXT NULL,
    target_audience TEXT NULL,
    requirements    TEXT NULL,
    documents       TEXT NULL,
    access_info     TEXT NULL,
    booking_info    TEXT NULL,
    cost_info       TEXT NULL,
    notes           TEXT NULL,
    keywords        VARCHAR(1000) NULL,
    status          ENUM('draft','machine','to_review','approved','outdated') NOT NULL DEFAULT 'draft',
    source_hash     CHAR(64) NULL,
    translated_by   INT UNSIGNED NULL,
    reviewed_by     INT UNSIGNED NULL,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (service_id, locale),
    KEY ix_service_tr_locale (locale, status),
    CONSTRAINT fk_service_tr_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
    CONSTRAINT fk_service_tr_locale FOREIGN KEY (locale) REFERENCES locales (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS service_categories (
    service_id  INT UNSIGNED NOT NULL,
    category_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (service_id, category_id),
    CONSTRAINT fk_service_categories_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
    CONSTRAINT fk_service_categories_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS service_needs (
    service_id INT UNSIGNED NOT NULL,
    need_id    SMALLINT UNSIGNED NOT NULL,
    relevance  TINYINT UNSIGNED NOT NULL DEFAULT 5,
    PRIMARY KEY (service_id, need_id),
    CONSTRAINT fk_service_needs_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
    CONSTRAINT fk_service_needs_need FOREIGN KEY (need_id) REFERENCES needs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS service_sites (
    service_id INT UNSIGNED NOT NULL,
    site_id    INT UNSIGNED NOT NULL,
    is_main    TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (service_id, site_id),
    CONSTRAINT fk_service_sites_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
    CONSTRAINT fk_service_sites_site FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Competenza territoriale: nessuna riga = nessuna restrizione
CREATE TABLE IF NOT EXISTS service_territories (
    service_id   INT UNSIGNED NOT NULL,
    territory_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (service_id, territory_id),
    CONSTRAINT fk_service_territories_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
    CONSTRAINT fk_service_territories_territory FOREIGN KEY (territory_id) REFERENCES territories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS service_languages (
    service_id    INT UNSIGNED NOT NULL,
    language_code VARCHAR(12) NOT NULL,
    mode          ENUM('staff','mediator','written_material','on_request') NOT NULL,
    PRIMARY KEY (service_id, language_code, mode),
    KEY ix_service_languages_code (language_code),
    CONSTRAINT fk_service_languages_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
    CONSTRAINT fk_service_languages_language FOREIGN KEY (language_code) REFERENCES languages (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Contatti con visibilità per singolo recapito (D-013)
CREATE TABLE IF NOT EXISTS contact_points (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_type  ENUM('organization','site','service','mediator') NOT NULL,
    owner_id    INT UNSIGNED NOT NULL,
    kind        ENUM('phone','mobile','whatsapp','email','pec','website','social','other') NOT NULL,
    value       VARCHAR(500) NOT NULL,
    label_key   VARCHAR(60) NULL,
    visibility  ENUM('public','operators','admin') NOT NULL DEFAULT 'admin',
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by  INT UNSIGNED NULL,
    updated_at  DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by  INT UNSIGNED NULL,
    KEY ix_contact_points_owner (owner_type, owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Orari strutturati; weekday ISO-8601 (1 = lunedì)
CREATE TABLE IF NOT EXISTS opening_hours (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_type     ENUM('site','service','mediator') NOT NULL,
    owner_id       INT UNSIGNED NOT NULL,
    weekday        TINYINT UNSIGNED NOT NULL,
    opens_at       TIME NOT NULL,
    closes_at      TIME NOT NULL,
    by_appointment TINYINT(1) NOT NULL DEFAULT 0,
    valid_from     DATE NULL,
    valid_to       DATE NULL,
    KEY ix_opening_hours_owner (owner_type, owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Dizionario di ricerca: parole dell'utente → bisogni, categorie, servizi (D-012)
CREATE TABLE IF NOT EXISTS search_terms (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    locale      VARCHAR(12) NOT NULL,
    term        VARCHAR(190) NOT NULL,
    target_type ENUM('need','category','service','organization_type') NOT NULL,
    target_id   INT UNSIGNED NOT NULL,
    weight      TINYINT UNSIGNED NOT NULL DEFAULT 5,
    created_by  INT UNSIGNED NULL,
    UNIQUE KEY uq_search_terms (locale, term, target_type, target_id),
    KEY ix_search_terms_lookup (locale, term)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
