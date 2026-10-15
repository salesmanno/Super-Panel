# Innlogging og sesjonslagring

## Hva rettelsen beskytter

En ny passordbekreftet innlogging erstatter tidligere økter. Før den nye
databasesesjonen committes og nettleseren sendes videre, kontrolleres både
sesjonsradens identifikator og at PHP kan skrive og lese tilbake den komplette
innloggingen. Dersom dette feiler, rulles forsøket tilbake. Det kreves ingen nye
tabeller eller manuell sletting av eksisterende sesjoner.

Midlertidig database-/serverfeil gir nå 503, og opptatt innloggingslås gir 409.
Disse feilene gir ikke tilgang til beskyttet innhold, men sletter heller ikke en
gyldig innlogging. Faktisk øktutløp, tapt PHP-økt og endret kontotilgang behandles
separat. Bakgrunnsstatus og identitetsoppfrisking forlenger ikke 30-minuttersfristen.

Gamle side-/API-svar rydder bare sin egen serverøkt. De sender ikke en
cookie-sletting som kan ødelegge en nyere innlogging i samme nettleser.

## Oppdatering på ekstern server

Oppdater følgende filer samlet, ikke bare lasteskjermen:

- `includes/company_session_support.php` (ny, påkrevd fil)
- `includes/company_login.php`
- `includes/session_manager.php`
- `auth/company_auth.php`
- `auth/logout.php`
- `api/company_session.php`
- `api/company_choice.php`
- `api/refresh_session.php`
- `pages/login_transition.php`
- `pages/company_login.php`
- `index.php`
- `assets/company_session.js`

Dette publiserer ikke automatisk til ekstern hosting. Dersom PHP bruker OPcache,
må webhotellets normale mekanisme for oppdatering/restart av PHP brukes ved behov.
Ikke opprett en offentlig OPcache-reset- eller diagnose-rute.

## Sikre loggkoder

Se etter `Company authentication; phase=...; reason=...; sqlstate=...`
i webhotellets private PHP-feillogg. Disse linjene har ikke brukernavn,
passord, cookie-/sesjonstoken, IP, full brukeragent, databasecredentials eller rå SQL.

- `php_session_unavailable`: PHP kunne ikke starte sesjonshåndtereren.
- `php_session_rotation_failed`: PHP kunne ikke rotere innloggingens identifikator.
- `php_session_not_persisted`: PHP kunne ikke skrive og lese tilbake innloggingen.
- `registry_write_mismatch`: Databasen beholdt ikke nøyaktig den nye identifikatoren;
  kontroller blant annet sesjonskolonnens kapasitet og SQL-modus.
- `missing_identity` / `missing_session_id`: PHP-data mangler etter navigasjon.
- `session_id_mismatch`: PHP-innloggingen tilhører ikke den aktuelle serverøkten.
- `registry_expired_or_revoked`: Sesjonsraden er utløpt, avsluttet eller mangler.
- `account_changed` / `company_changed` / `employee_changed`: Tilgang/kobling
  oppfyller ikke lenger innloggingskravene.
- `request_busy`: En annen forespørsel holder kontoens innloggingslås.
- `database_unavailable`: PDO-feil; bare SQLSTATE er logget, ikke driverens melding.
- `server_unavailable`: Annen uventet serverfeil.

Kontroller faktisk cookie-domene/path/HTTPS, PHP-versjon og sesjonshåndterer/
lagring på målserveren ved behov. Ikke dump miljøvariabler, `phpinfo()` eller
sesjonsfiler til en offentlig side. Ikke gi vide filrettigheter som en blind rettelse.

## Verifikasjon

Kjør `bash tests/company_login_isolated_test.sh` for autentisering alene i en
disponibel, socket-basert MySQL og med syntetiske kontoer. Den leser aldri
prosjektets virkelige databasekonfigurasjon og tester ingen forretningsmoduler.
Testen kjører også Chromium gjennom faktisk skjema-POST, cookie og lasteskjerm.

Klient-/mobilkontroll: `node tests/company-session-client.test.js` og
`node tests/company_login_chromium_test.js`.

En vellykket lokal test verifiserer ikke automatisk en ekte konto på den eksterne
serveren. For målserveren må filoppdatering og en ordinær innlogging bekreftes,
og ved fortsatt feil må den sikre årsakskoden fra det relevante tidspunktet brukes.
