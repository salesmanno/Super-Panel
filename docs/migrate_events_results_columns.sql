-- ============================================
-- Events Results Column Enhancement
-- Adds comprehensive income/expense tracking
-- ============================================

ALTER TABLE events_results
-- Revenue: Ticket sales breakdown by payment method
ADD COLUMN revenue_tickets_cash DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Billettinntekter - Kontant (betalt)',
ADD COLUMN revenue_tickets_card DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Billettinntekter - Kort (betalt)',
ADD COLUMN revenue_tickets_vipps DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Billettinntekter - Vipps (betalt)',
ADD COLUMN revenue_tickets_invoice DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Billettinntekter - Faktura (betalt)',
ADD COLUMN revenue_merchandise DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Salg av varer/merchandise',
ADD COLUMN revenue_donations DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Donasjoner',
-- Expenses: Cost breakdown
ADD COLUMN expense_staff DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Personalkostnader',
ADD COLUMN expense_venue DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Venueleie og lokalekostand',
ADD COLUMN expense_equipment DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Utleie av utstyr',
ADD COLUMN expense_materials DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Materialer og forbruk',
ADD COLUMN expense_other DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Andre utgifter',
-- Attendance & Status
ADD COLUMN no_shows INT(11) DEFAULT 0 COMMENT 'Antall som ikke møtte opp',
ADD COLUMN attendance_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Oppmøte % (0-100)',
ADD COLUMN average_age INT(11) DEFAULT 0 COMMENT 'Gjennomsnittlig alder',
ADD COLUMN status VARCHAR(50) DEFAULT 'planning' COMMENT 'Status: planning, confirmed, in_progress, completed, cancelled',
ADD COLUMN notes LONGTEXT DEFAULT NULL COMMENT 'Notater om arrangementet',
ADD COLUMN organizer_notes LONGTEXT DEFAULT NULL COMMENT 'Interne notater',
ADD COLUMN finalized_at DATETIME DEFAULT NULL COMMENT 'Når resultatene ble avsluttet';
