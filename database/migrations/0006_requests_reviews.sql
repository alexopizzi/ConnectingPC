-- 0006 — Registro delle richieste in ingresso (RF-37, vault "52") ed esiti della revisione (RF-25, RF-35, vault "53").

-- Richieste ricevute dai gestori (censimento, aggiornamenti, account, abilitazione…), registrate in admin.
-- Contengono dati personali di chi scrive: conservazione limitata (retention_until), vault "71".
CREATE TABLE IF NOT EXISTS inbound_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type            ENUM('census','update','account','enablement','data_error','other') NOT NULL,
    channel         ENUM('email','phone','in_person','web_form') NOT NULL,
    organization_id INT UNSIGNED NULL,
    requester_name  VARCHAR(150) NULL,
    requester_email VARCHAR(254) NULL,
    requester_phone VARCHAR(40) NULL,
    message         TEXT NULL,
    status          ENUM('new','in_progress','done','rejected') NOT NULL DEFAULT 'new',
    assigned_to     INT UNSIGNED NULL,
    resolution_note TEXT NULL,
    retention_until DATE NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by      INT UNSIGNED NULL,
    updated_at      DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by      INT UNSIGNED NULL,
    KEY ix_inbound_status (status, created_at),
    CONSTRAINT fk_inbound_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE SET NULL,
    CONSTRAINT fk_inbound_assigned FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_inbound_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_inbound_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Esiti della revisione dei contenuti "in revisione": visibili all'organizzazione con la nota del revisore
CREATE TABLE IF NOT EXISTS review_decisions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type     ENUM('organization','site','service','mediator') NOT NULL,
    entity_id       INT UNSIGNED NOT NULL,
    organization_id INT UNSIGNED NULL,
    decision        ENUM('approved','rejected') NOT NULL,
    note            VARCHAR(1000) NULL,
    decided_by      INT UNSIGNED NULL,
    decided_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_review_entity (entity_type, entity_id, decided_at),
    KEY ix_review_org (organization_id, decided_at),
    CONSTRAINT fk_review_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
    CONSTRAINT fk_review_user FOREIGN KEY (decided_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
