-- Idempotent profile-contact backfill.
-- Run against the database where tenant_customer_contacts already exists.
-- This does not merge customer records or remove legacy email/phone columns.

START TRANSACTION;

INSERT INTO tenant_customer_contacts
    (id, customer_id, contact_type, contact_value, normalized_value, is_primary, verified_at, created_at)
SELECT
    UUID(),
    tc.id,
    'email',
    TRIM(tc.email),
    LOWER(TRIM(tc.email)),
    CASE WHEN EXISTS (
        SELECT 1 FROM tenant_customer_contacts primary_contact
        WHERE primary_contact.customer_id = tc.id
          AND primary_contact.contact_type = 'email' AND primary_contact.is_primary = 1
    ) THEN 0 ELSE 1 END,
    NULL,
    NOW()
FROM tenant_customers tc
WHERE tc.email IS NOT NULL
  AND TRIM(tc.email) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM tenant_customer_contacts existing_contact
      WHERE existing_contact.customer_id = tc.id
        AND existing_contact.contact_type = 'email'
        AND LOWER(TRIM(existing_contact.normalized_value)) = LOWER(TRIM(tc.email))
  );

INSERT INTO tenant_customer_contacts
    (id, customer_id, contact_type, contact_value, normalized_value, is_primary, verified_at, created_at)
SELECT
    UUID(),
    tc.id,
    'phone',
    TRIM(tc.phone),
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(tc.phone), ' ', ''), CHAR(9), ''), '(', ''), ')', ''), '-', ''), '.', ''),
    CASE WHEN EXISTS (
        SELECT 1 FROM tenant_customer_contacts primary_contact
        WHERE primary_contact.customer_id = tc.id
          AND primary_contact.contact_type = 'phone' AND primary_contact.is_primary = 1
    ) THEN 0 ELSE 1 END,
    NULL,
    NOW()
FROM tenant_customers tc
WHERE tc.phone IS NOT NULL
  AND TRIM(tc.phone) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM tenant_customer_contacts existing_contact
      WHERE existing_contact.customer_id = tc.id
        AND existing_contact.contact_type = 'phone'
        AND LOWER(TRIM(existing_contact.normalized_value)) =
            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(tc.phone), ' ', ''), CHAR(9), ''), '(', ''), ')', ''), '-', ''), '.', '')
  );

COMMIT;

-- Review duplicate normalized values before deciding whether to clean manually.
SELECT customer_id, contact_type, normalized_value, COUNT(*) AS duplicate_count
FROM tenant_customer_contacts
GROUP BY customer_id, contact_type, normalized_value
HAVING COUNT(*) > 1
ORDER BY customer_id, contact_type, normalized_value;

-- Review customers that still have a legacy contact but no primary contact row.
SELECT tc.id AS customer_id, tc.company_id, 'email' AS contact_type, tc.email AS legacy_value
FROM tenant_customers tc
WHERE tc.email IS NOT NULL AND TRIM(tc.email) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM tenant_customer_contacts cc
      WHERE cc.customer_id = tc.id AND cc.contact_type = 'email' AND cc.is_primary = 1
  )
UNION ALL
SELECT tc.id, tc.company_id, 'phone', tc.phone
FROM tenant_customers tc
WHERE tc.phone IS NOT NULL AND TRIM(tc.phone) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM tenant_customer_contacts cc
      WHERE cc.customer_id = tc.id AND cc.contact_type = 'phone' AND cc.is_primary = 1
  )
ORDER BY company_id, customer_id, contact_type;
