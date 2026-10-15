-- MANUAL FOUNDATION ONLY: Salesman mobile cash registers.
-- Run ONCE in the intended database, after taking a backup.
-- Creates exactly four new tables and twelve integrity triggers.
-- Does not alter existing Sales tables, import history, or enable fiscal checkout.
-- Requires InnoDB, JSON functions, generated-column indexes and TRIGGER privilege.
-- Uses MySQL 5.7.8+/MariaDB 10.2.7+ syntax; tested engine is documented separately.
-- Do not use --force / ignore errors. DDL implicitly commits: an import can be partial.
-- Do not drop existing tables/triggers to retry. Investigate any collision/error first.
-- UTC timestamps are assigned by triggers, independent of the connection time zone.
-- The SHA-256 chain below is NOT a SAF-T digital signature or proof of compliance.

SET NAMES utf8mb4;

CREATE TABLE sales_registers (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    company_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    register_code VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(100) NOT NULL,
    operation_mode ENUM('mobile','fixed') NOT NULL DEFAULT 'mobile',
    status ENUM('draft','active','retired') NOT NULL DEFAULT 'draft',
    system_identifier VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    system_model VARCHAR(80) NOT NULL,
    system_version VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    hardware_profile JSON DEFAULT NULL,
    last_period_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_journal_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_document_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_receipt_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_training_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_z_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_journal_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    created_at_utc DATETIME(6) NOT NULL,
    activated_at_utc DATETIME(6) DEFAULT NULL,
    retired_at_utc DATETIME(6) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sr_company_id (company_id,id),
    UNIQUE KEY uq_sr_company_code (company_id,register_code),
    UNIQUE KEY uq_sr_system (system_identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE sales_register_periods (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    company_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    register_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    period_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    business_date DATE NOT NULL,
    status ENUM('open','closing','closed') NOT NULL DEFAULT 'open',
    active_slot TINYINT GENERATED ALWAYS AS
        (CASE WHEN status = 'closed' THEN NULL ELSE 1 END) STORED,
    currency CHAR(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'NOK',
    opening_float_minor BIGINT NOT NULL DEFAULT 0,
    expected_cash_minor BIGINT DEFAULT NULL,
    counted_cash_minor BIGINT DEFAULT NULL,
    cash_difference_minor BIGINT GENERATED ALWAYS AS
        (counted_cash_minor - expected_cash_minor) STORED,
    opened_by CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    closed_by CHAR(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    opened_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    closed_at_utc DATETIME(6) DEFAULT NULL,
    closing_z_document_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_srp_scope (company_id,register_id,id),
    UNIQUE KEY uq_srp_number (company_id,register_id,period_number),
    UNIQUE KEY uq_srp_active (company_id,register_id,active_slot),
    KEY idx_srp_date (company_id,business_date),
    CONSTRAINT fk_srp_register FOREIGN KEY (company_id,register_id)
        REFERENCES sales_registers (company_id,id)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE sales_register_journal (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    company_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    register_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    period_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    sequence_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    event_type VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    idempotency_key VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    actor_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    order_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    system_version VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
    recorded_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    payload LONGTEXT NOT NULL COMMENT 'Exact UTF-8 JSON object bytes; never reserialize for hash verification',
    payload_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
    previous_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    entry_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY uq_srj_number (company_id,register_id,sequence_number),
    UNIQUE KEY uq_srj_retry (company_id,register_id,idempotency_key),
    UNIQUE KEY uq_srj_scope (company_id,register_id,period_id,id),
    KEY idx_srj_order (company_id,order_id),
    KEY idx_srj_time (company_id,register_id,recorded_at_utc),
    CONSTRAINT fk_srj_register FOREIGN KEY (company_id,register_id)
        REFERENCES sales_registers (company_id,id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_srj_period FOREIGN KEY (company_id,register_id,period_id)
        REFERENCES sales_register_periods (company_id,register_id,id)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE sales_register_documents (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    company_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    register_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    period_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    journal_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    document_type ENUM('sale','return','copy','provisional','training',
        'x_report','z_report','delivery','terminal') NOT NULL,
    sequence_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    receipt_number BIGINT UNSIGNED DEFAULT NULL,
    training_number BIGINT UNSIGNED DEFAULT NULL,
    z_number BIGINT UNSIGNED DEFAULT NULL,
    z_period_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    order_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    source_document_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    copy_of_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL
        COMMENT 'Assigned by trigger for copies only; unique per company',
    currency CHAR(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'NOK',
    total_minor BIGINT NOT NULL,
    issued_at_utc DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    payload LONGTEXT NOT NULL COMMENT 'Frozen UTF-8 JSON document snapshot, including lines and tax breakdown',
    payload_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
    signing_key_id VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    signature_algorithm VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    signature TEXT CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_srd_company_id (company_id,id),
    UNIQUE KEY uq_srd_scope (company_id,register_id,period_id,id),
    UNIQUE KEY uq_srd_number (company_id,register_id,sequence_number),
    UNIQUE KEY uq_srd_receipt (company_id,register_id,receipt_number),
    UNIQUE KEY uq_srd_training (company_id,register_id,training_number),
    UNIQUE KEY uq_srd_z (company_id,register_id,z_number),
    UNIQUE KEY uq_srd_z_period (company_id,register_id,z_period_id),
    UNIQUE KEY uq_srd_one_copy (company_id,copy_of_id),
    UNIQUE KEY uq_srd_journal (company_id,journal_id),
    KEY idx_srd_order (company_id,order_id),
    KEY idx_srd_source (company_id,source_document_id),
    CONSTRAINT fk_srd_journal FOREIGN KEY (company_id,register_id,period_id,journal_id)
        REFERENCES sales_register_journal (company_id,register_id,period_id,id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_srd_source FOREIGN KEY (company_id,source_document_id)
        REFERENCES sales_register_documents (company_id,id)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

ALTER TABLE sales_register_periods
    ADD CONSTRAINT fk_srp_z_document
    FOREIGN KEY (company_id,register_id,id,closing_z_document_id)
    REFERENCES sales_register_documents (company_id,register_id,period_id,id)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

DELIMITER $$

CREATE TRIGGER srp_before_insert BEFORE INSERT ON sales_register_periods
FOR EACH ROW
BEGIN
    DECLARE v_last BIGINT UNSIGNED DEFAULT NULL;
    SELECT last_period_number INTO v_last FROM sales_registers
        WHERE company_id = NEW.company_id AND id = NEW.register_id FOR UPDATE;
    IF v_last IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unknown register/company';
    END IF;
    IF NEW.status <> 'open' OR NEW.closed_by IS NOT NULL
        OR NEW.closed_at_utc IS NOT NULL OR NEW.closing_z_document_id IS NOT NULL
        OR NEW.expected_cash_minor IS NOT NULL OR NEW.counted_cash_minor IS NOT NULL
        OR NEW.opening_float_minor < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid new register period';
    END IF;
    SET NEW.period_number = v_last + 1;
    SET NEW.opened_at_utc = UTC_TIMESTAMP(6);
END$$

CREATE TRIGGER srp_after_insert AFTER INSERT ON sales_register_periods
FOR EACH ROW
BEGIN
    UPDATE sales_registers SET last_period_number = NEW.period_number
        WHERE company_id = NEW.company_id AND id = NEW.register_id;
END$$

CREATE TRIGGER srp_before_update BEFORE UPDATE ON sales_register_periods
FOR EACH ROW
BEGIN
    DECLARE v_z_type VARCHAR(20) DEFAULT NULL;
    IF OLD.status = 'closed' OR NOT (NEW.id <=> OLD.id)
        OR NOT (NEW.company_id <=> OLD.company_id)
        OR NOT (NEW.register_id <=> OLD.register_id)
        OR NOT (NEW.period_number <=> OLD.period_number)
        OR NOT (NEW.business_date <=> OLD.business_date)
        OR NOT (NEW.currency <=> OLD.currency)
        OR NOT (NEW.opening_float_minor <=> OLD.opening_float_minor)
        OR NOT (NEW.opened_by <=> OLD.opened_by)
        OR NOT (NEW.opened_at_utc <=> OLD.opened_at_utc) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Period identity/opening/closed period is immutable';
    END IF;
    IF NEW.status = 'closed' THEN
        IF OLD.status <> 'closing' OR NEW.closed_by IS NULL
            OR NEW.closing_z_document_id IS NULL OR NEW.expected_cash_minor IS NULL
            OR NEW.counted_cash_minor IS NULL OR NEW.counted_cash_minor < 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Closing needs Z report, operator and cash count';
        END IF;
        SELECT document_type INTO v_z_type FROM sales_register_documents
            WHERE company_id = NEW.company_id AND register_id = NEW.register_id
                AND period_id = NEW.id AND id = NEW.closing_z_document_id;
        IF v_z_type IS NULL OR v_z_type <> 'z_report' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Closing document is not this period Z report';
        END IF;
        SET NEW.closed_at_utc = UTC_TIMESTAMP(6);
    ELSEIF NEW.closed_by IS NOT NULL OR NEW.closed_at_utc IS NOT NULL
        OR NEW.closing_z_document_id IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Open/closing period cannot contain closing identity';
    END IF;
END$$

CREATE TRIGGER srp_no_delete BEFORE DELETE ON sales_register_periods
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Register periods cannot be deleted'$$

CREATE TRIGGER srj_before_insert BEFORE INSERT ON sales_register_journal
FOR EACH ROW
BEGIN
    DECLARE v_last BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_hash CHAR(64) DEFAULT NULL;
    DECLARE v_version VARCHAR(80) DEFAULT NULL;
    DECLARE v_period_status VARCHAR(10) DEFAULT NULL;
    SELECT last_journal_number,last_journal_hash,system_version
        INTO v_last,v_hash,v_version FROM sales_registers
        WHERE company_id = NEW.company_id AND id = NEW.register_id FOR UPDATE;
    IF v_last IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unknown register/company';
    END IF;
    IF NEW.period_id IS NOT NULL THEN
        SELECT status INTO v_period_status FROM sales_register_periods
            WHERE company_id = NEW.company_id AND register_id = NEW.register_id
                AND id = NEW.period_id FOR UPDATE;
        IF v_period_status IS NULL OR v_period_status = 'closed' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unknown or closed register period';
        END IF;
    END IF;
    IF JSON_VALID(NEW.payload) <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Journal payload must be valid JSON';
    END IF;
    IF JSON_TYPE(NEW.payload) <> 'OBJECT'
        OR LENGTH(TRIM(NEW.event_type)) = 0 OR LENGTH(TRIM(NEW.idempotency_key)) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Journal needs an object, event type and retry key';
    END IF;
    SET NEW.sequence_number = v_last + 1;
    SET NEW.previous_hash = v_hash;
    SET NEW.system_version = v_version;
    SET NEW.recorded_at_utc = UTC_TIMESTAMP(6);
    SET NEW.payload_sha256 = SHA2(NEW.payload,256);
    -- SRJ1: byte-length-prefixed fields; see docs/sales_register_setup.md.
    SET NEW.entry_hash = SHA2(CONCAT('SRJ1',
        LENGTH(NEW.company_id),':',NEW.company_id,
        LENGTH(NEW.register_id),':',NEW.register_id,
        LENGTH(NEW.id),':',NEW.id,
        LENGTH(CAST(NEW.sequence_number AS CHAR)),':',CAST(NEW.sequence_number AS CHAR),
        LENGTH(COALESCE(NEW.period_id,'')),':',COALESCE(NEW.period_id,''),
        LENGTH(COALESCE(NEW.actor_id,'')),':',COALESCE(NEW.actor_id,''),
        LENGTH(COALESCE(NEW.order_id,'')),':',COALESCE(NEW.order_id,''),
        LENGTH(NEW.event_type),':',NEW.event_type,
        LENGTH(NEW.idempotency_key),':',NEW.idempotency_key,
        LENGTH(NEW.system_version),':',NEW.system_version,
        '26:',DATE_FORMAT(NEW.recorded_at_utc,'%Y-%m-%dT%H:%i:%s.%f'),
        '64:',COALESCE(NEW.previous_hash,REPEAT('0',64)),
        '64:',NEW.payload_sha256
    ),256);
END$$

CREATE TRIGGER srj_after_insert AFTER INSERT ON sales_register_journal
FOR EACH ROW
BEGIN
    UPDATE sales_registers SET last_journal_number = NEW.sequence_number,
        last_journal_hash = NEW.entry_hash
        WHERE company_id = NEW.company_id AND id = NEW.register_id;
END$$

CREATE TRIGGER srj_no_update BEFORE UPDATE ON sales_register_journal
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Register journal is append-only'$$

CREATE TRIGGER srj_no_delete BEFORE DELETE ON sales_register_journal
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Register journal is append-only'$$

CREATE TRIGGER srd_before_insert BEFORE INSERT ON sales_register_documents
FOR EACH ROW
BEGIN
    DECLARE v_doc BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_receipt BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_training BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_z BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_status VARCHAR(10) DEFAULT NULL;
    DECLARE v_currency CHAR(3) DEFAULT NULL;
    DECLARE v_source_type VARCHAR(20) DEFAULT NULL;
    DECLARE v_source_currency CHAR(3) DEFAULT NULL;
    DECLARE v_source_total BIGINT DEFAULT NULL;
    SELECT last_document_number,last_receipt_number,last_training_number,last_z_number
        INTO v_doc,v_receipt,v_training,v_z FROM sales_registers
        WHERE company_id = NEW.company_id AND id = NEW.register_id FOR UPDATE;
    IF v_doc IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unknown register/company';
    END IF;
    SELECT status,currency INTO v_status,v_currency FROM sales_register_periods
        WHERE company_id = NEW.company_id AND register_id = NEW.register_id
            AND id = NEW.period_id FOR UPDATE;
    IF v_status IS NULL OR NEW.currency <> v_currency
        OR (NEW.document_type = 'z_report' AND v_status <> 'closing')
        OR (NEW.document_type <> 'z_report' AND v_status <> 'open') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid document period state/currency';
    END IF;
    IF JSON_VALID(NEW.payload) <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Document payload must be valid JSON';
    END IF;
    IF JSON_TYPE(NEW.payload) <> 'OBJECT'
        OR (NEW.document_type = 'sale' AND NEW.total_minor < 0)
        OR (NEW.document_type = 'return' AND NEW.total_minor > 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid document object/amount sign';
    END IF;
    IF (NEW.signature IS NULL AND (NEW.signing_key_id IS NOT NULL OR NEW.signature_algorithm IS NOT NULL))
        OR (NEW.signature IS NOT NULL AND (NEW.signing_key_id IS NULL
            OR NEW.signature_algorithm IS NULL OR LENGTH(NEW.signature) = 0
            OR LENGTH(NEW.signing_key_id) = 0 OR LENGTH(NEW.signature_algorithm) = 0)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Signature metadata must be complete or absent';
    END IF;
    SET NEW.sequence_number = v_doc + 1;
    SET NEW.receipt_number = NULL;
    SET NEW.training_number = NULL;
    SET NEW.z_number = NULL;
    SET NEW.z_period_id = NULL;
    SET NEW.copy_of_id = NULL;
    IF NEW.document_type IN ('sale','return') THEN
        SET NEW.receipt_number = v_receipt + 1;
    ELSEIF NEW.document_type = 'training' THEN
        SET NEW.training_number = v_training + 1;
    ELSEIF NEW.document_type = 'z_report' THEN
        SET NEW.z_number = v_z + 1;
        SET NEW.z_period_id = NEW.period_id;
    ELSEIF NEW.document_type = 'copy' THEN
        IF NEW.source_document_id IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Copy needs an original document';
        END IF;
        SELECT document_type,currency,total_minor
            INTO v_source_type,v_source_currency,v_source_total
            FROM sales_register_documents
            WHERE company_id = NEW.company_id AND id = NEW.source_document_id;
        IF v_source_type IS NULL OR v_source_type NOT IN ('sale','return','delivery')
            OR NEW.currency <> v_source_currency OR NEW.total_minor <> v_source_total THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid original document for copy';
        END IF;
        SET NEW.copy_of_id = NEW.source_document_id;
    END IF;
    SET NEW.issued_at_utc = UTC_TIMESTAMP(6);
    SET NEW.payload_sha256 = SHA2(NEW.payload,256);
END$$

CREATE TRIGGER srd_after_insert AFTER INSERT ON sales_register_documents
FOR EACH ROW
BEGIN
    UPDATE sales_registers SET last_document_number = NEW.sequence_number,
        last_receipt_number = COALESCE(NEW.receipt_number,last_receipt_number),
        last_training_number = COALESCE(NEW.training_number,last_training_number),
        last_z_number = COALESCE(NEW.z_number,last_z_number)
        WHERE company_id = NEW.company_id AND id = NEW.register_id;
END$$

CREATE TRIGGER srd_no_update BEFORE UPDATE ON sales_register_documents
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Register documents are immutable'$$

CREATE TRIGGER srd_no_delete BEFORE DELETE ON sales_register_documents
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Register documents are immutable'$$

DELIMITER ;

-- Verification only; all four tables must exist and trigger_count must be 12.
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (
    'sales_registers','sales_register_periods','sales_register_journal','sales_register_documents')
ORDER BY TABLE_NAME;

SELECT COUNT(*) AS trigger_count FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME IN (
    'srp_before_insert','srp_after_insert','srp_before_update','srp_no_delete',
    'srj_before_insert','srj_after_insert','srj_no_update','srj_no_delete',
    'srd_before_insert','srd_after_insert','srd_no_update','srd_no_delete');
