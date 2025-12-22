-- ERSTATTET: Bruk tenant_polling_tracker tabellen i stedet
-- Brukeren har kjørt CREATE TABLE for den nye tabellen.

-- For å trigge refresh fra eksternt system:
-- UPDATE tenant_polling_tracker 
-- SET items_processed_last_poll = [antall], 
--     last_polled_at = NOW(), 
--     updated_at = NOW() 
-- WHERE company_id = [company_id] AND polling_source = 'email_imap';
