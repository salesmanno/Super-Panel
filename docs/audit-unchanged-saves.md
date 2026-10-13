# Audit write coverage and unchanged saves

## Policy
Unchanged administrative saves create no new audit row. `tenantAuditChange`
returns an empty string when no new audit ID exists. `tenantAuditRecord` remains
the explicit event writer: a successful event with empty snapshots is still
recorded; only explicit `noop` outcomes are suppressed. Callers must not treat
an empty ID as a recorded event or fabricate one for correlation.

Before/after comparisons use locked database snapshots, before audit values are
truncated or redacted. Decimal/boolean normalization is field-specific. JSON
object key order and whitespace are insignificant; list order, missing fields,
null, empty strings, leading zeros in codes, and text changes remain meaningful.
Private JSON comparisons stay transient; private values/hashes are never placed
in the journal. Changes are reported by field name only.

## All production audit writers
`includes/tenant_audit.php` is the sole INSERT writer for `audit_logs`.
The following are the complete callers in the existing PHP source:

| Path | Meaning and handling |
| --- | --- |
| `includes/AuditedPDO.php` | Automatic INSERT/REPLACE/UPDATE/DELETE for the resources in `audit_resources.php`; unchanged updates/upserts and zero-match writes skipped. Private-only updates/upserts remain recorded. Rollback records only actual mutations. |
| `includes/entity_form_fields.php` | Explicit field insert/update/delete. Locked equality check prevents unchanged updates; field options, rules, defaults and sort order checked before redaction. |
| `includes/entity_form_migration.php` | Actual field migration insertion, retained. No new migration performed. |
| `includes/rental_reservation_codes.php` | Actual reservation-code assignment and physical venue-code change, retained; already-matching codes do not write. |
| `includes/rental_checkin_email.php` | Template bootstrap and new email queue event, retained with correlation. |
| `api/update_rental_schedule.php` | Snapshot-based changes retained. Explicit unchanged/repeated decision `noop` skipped; denied attempts and queued-mail events retained. Actual decisions retain the initiating audit ID for email linkage. |
| `api/perform_checkin.php` | Actual check-in changes and denied check-in attempts retained. Already checked-in attempts are denied events, not ordinary unchanged saves. |
| `api/capture_vipps_payment.php` | Capture request and resulting payment changes retained; no provider call or retry policy changed. |
| `api/process_email_queue.php` | SMTP accepted/failed events retained, including events without a conventional field diff. |
| `includes/tenant_audit.php` | Failed/denied/rolled-back explicit records retained. |

`api/get_audit_logs.php` and email provenance lookups are read-only.
Module SQL does not insert directly into `audit_logs`. Login/session history uses
its existing separate logging mechanism and is not changed.

## Avoiding false delete/insert changes
`api/save_event_membership_pricing.php` reconciles existing locked price rows
through `includes/event_membership_prices.php`: unchanged prices keep their rows
and creation timestamps, changes update one row, and only removed/new choices
delete/insert. Invalid/foreign choices abort without deleting existing prices.
Shared form definitions already reconcile individually and retain revision checks.

## Verification
`tests/audit_comparison_test.php` covers value semantics and comparison before
truncation. `tests/tenant_audit_mysql_isolated_test.php` exercises automatic
audit across modules, explicit comparison, private-only changes, JSON,
no-match/unchanged writes, rollback, real membership-price HTTP saves,
foreign-company access and fail-closed audit. Existing form, rental-decision,
payment and email suites cover their deliberate event writers.
