-- Optional, one-time upgrade for the original "innsjekk utleie" template.
-- This migration is deliberately separate from the sending path: stored template
-- content is never rewritten while an email is being queued.
UPDATE email_templates
SET body_template = REPLACE(
    REPLACE(
        REPLACE(
            body_template,
            '<strong>Britt Inger Gusdal</strong>',
            '<strong>{{contact_person}}</strong>'
        ),
        'href="tel:"',
        'href="tel:{{contact_phone}}"'
    ),
    '>48145008</a>',
    '>{{contact_phone}}</a>'
),
updated_at = NOW()
WHERE template_key = 'rental_checkin'
  AND body_template LIKE '%<strong>Britt Inger Gusdal</strong>%'
  AND body_template LIKE '%>48145008</a>%';