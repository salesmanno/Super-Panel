# Oppdatering til kundekontaktregister

Dette er en manuell databaseoppdatering. Applikasjonen oppretter ikke tabellen
automatisk. Ta sikkerhetskopi og velg riktig applikasjonsdatabase i phpMyAdmin.
Oppdateringen sletter verken kunder, kontaktverdier eller gamle kolonner.

## Rekkefølge på hver server

1. **Tabellen må finnes før ny PHP-kode og cron-jobber tas i bruk.** Kjør
   `db_migrations/tenant_customer_contacts_setup.sql`. Skriptet bruker
   `CREATE TABLE IF NOT EXISTS` og lar en eksisterende tabell stå urørt.
   Hvis tabellen allerede er opprettet med brukerens oppgitte struktur, er
   dette trinnet allerede utført. Kontroller med
   `SHOW CREATE TABLE tenant_customer_contacts`.
2. Kjør `db_migrations/tenant_customer_contacts_backfill.sql`. Det kopierer
   ikke-tomme profilkontakter uten å slå sammen kunder, overskrive bekreftelser
   eller erstatte en eksisterende hovedkontakt. Kjør skriptet én gang om gangen.
   Det er trygt å kjøre på nytt. Rapporter nederst viser duplikater og profiler
   som fortsatt mangler hovedkontakt; vurder disse manuelt.
3. Last opp den oppdaterte PHP-koden og inkluderingsfilene samlet. Kontroller
   medlemslisten, kontaktredigeringen og e-postmottakervalg før planlagte
   utsendinger aktiveres med den nye koden. Ikke bytt kode før trinn 1:
   reserveverdier fra gamle kolonner hjelper ikke hvis selve kontakttabellen mangler.
4. Hvis den gamle applikasjonen skrev nye profilkontakter mellom trinn 2 og 3,
   kjør overføringsskriptet på nytt og gjennomgå rapportene.

Oppsettskriptet gjengir den oppgitte tabellstrukturen. Ved nyopprettelse må
`tenant_customers.id` være en indeksert `CHAR(36)` med kompatibelt tegnsett og
sortering, og foreldretabellen må bruke InnoDB, slik at fremmednøkkelen kan opprettes.
Ikke endre en eksisterende kundetabell automatisk ved en kompatibilitetsfeil.

## Under overgangen

- Hovedkontakt i registeret brukes først; gammel profilkolonne brukes når en
  hovedkontakt mangler. Primærendringer i portalen speiles til gamle kolonner.
- Flere kunder kan ha samme e-post eller telefon. Ingen kundeidentiteter slås
  sammen; et tvetydig kontaktoppslag skal ikke velge en tilfeldig kunde.
- Tidligere registrerte mottakere i forespørsler, transaksjoner og e-postkøen
  beholdes. `verified_at` blir ikke et nytt vilkår for utsending.
- Ikke slett `tenant_customers.email` eller `tenant_customers.phone` før alle
  eksterne prosjekter er oppdatert og separat SQL-utrydding er godkjent.

## Isolert verifisering

`bash tests/customer_contacts_isolated_test.sh` oppretter en midlertidig
socket-basert MySQL-database, kjører det faktiske oppsettskriptet og overføringen,
og tester oppslag, opprettelse, redigering, sletting og mottakervalg.
Den bruker ikke prosjektets databaseinnstillinger.

`node tests/customer_contacts_chromium_test.js` tester den faktiske
kontaktredigeringskoden i en isolert nettleser med simulerte API-svar.
