-- Legg til refresh_polling kolonne i tenant_companies
-- Kjør dette på Domeneshop MySQL

ALTER TABLE `tenant_companies` 
ADD COLUMN `refresh_polling` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Sett til 1 for å trigge auto-refresh hos alle innloggede brukere';
