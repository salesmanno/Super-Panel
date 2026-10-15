# Manuell databasegrunnmur for flyttbare Salesman-kassapunkter

## Omfang

Brukeren har godkjent fire nye kassetabeller og valgt **flyttbare kassapunkter**. Brukeren kjører SQL manuelt.

Kjørbar fil: `sql/sales_register_setup.sql`.

Dette er kun datagrunnlaget. Filen kobler ikke kassen til tabellene og aktiverer ingen ny salgsflyt. Den endrer ikke eksisterende ordre, betalinger, e-postoppsett eller produkter. Ingen historiske salg importeres.

SQL alene gjør **ikke** Salesman til et ferdig regelverkstilpasset eller produkterklært kassasystem. Se `sales_cash_register_requirements.md` for resten av arbeidet.

## Før import

1. Ta sikkerhetskopi og prøv først i en separat testdatabase.
2. Velg riktig database i phpMyAdmin/importverktøyet.
3. Kontroller databaseversjon med `SELECT VERSION();`.
4. Kontroller at de fire foreslåtte tabellnavnene ikke allerede er i bruk.
5. Importbrukeren trenger rettigheter til å opprette tabeller, fremmednøkler og triggere. Denne filen gir ingen nye brukerrettigheter.
6. Importer hele filen med støtte for `DELIMITER`. Ikke bruk «fortsett ved feil» eller `--force`.

SQL bruker InnoDB, JSON-funksjoner, indekserte genererte kolonner og triggere med `SIGNAL`. Den unngår blant annet `FOR SHARE`, versjonsavhengig `CREATE TRIGGER IF NOT EXISTS` og `CHECK`-vilkår som eldre MySQL kan ignorere.

MySQL 8.0.36 er den isolerte testmotoren. MySQL 5.7.8+/MariaDB 10.2.7+ er syntaktisk utgangspunkt, **ikke en påstand om gjennomført test på disse motorene**. Kontroller faktisk målserver før import.

### Importen er engangs

Filen bruker bevisst `CREATE TABLE`, ikke `CREATE TABLE IF NOT EXISTS`: en eksisterende, ukjent struktur skal gi feil, ikke stille gjenbrukes.

MySQL/MariaDB DDL utfører implisitte commits. Hele filen er derfor **ikke atomisk**. Ved feil kan noen tabeller/triggere være opprettet. Stopp, ta vare på den nøyaktige feilmeldingen og undersøk før videre kjøring. Ikke slett tabeller eller dropp beskyttelser for å få importen gjennom.

Etter vellykket import skal slutten av filen vise fire tabellnavn og `trigger_count = 12`. Tabellene inneholder fortsatt ingen kassapunkter, perioder eller salg. Aktivering skal skje senere gjennom implementert og testet oppsett.

## Datamodell

- `sales_registers`: én varig identitet per kassapunkt og bedrift. Nye registreringer har `operation_mode='mobile'` og `status='draft'`. Identifikator er ikke en PHP-sesjon eller en bruker.
- `sales_register_periods`: oppgjørsperiode med uforanderlig åpning, inngående veksel, opptelling og avslutning. En unik generert nøkkel tillater bare én åpen/avsluttende periode per kassapunkt.
- `sales_register_journal`: fortløpende journalnummer, bedrift/kassapunkt/periode, operatør, ordreferanse, retry-nøkkel, nøyaktig JSON-tekst og kjedet SHA-256-kontrollsum.
- `sales_register_documents`: frosne dokumenter knyttet til én journalhendelse og samme bedrift/kassapunkt/periode. Egen nummerering for ordinære salgs-/returkvitteringer, trening og Z-rapporter; intern dokumentsekvens for alle dokumenter.

Penger lagres i hele øre (`*_minor`, `BIGINT`), aldri flyttall. Eksisterende ordre-/ledgerbeløp beholdes uendret; fremtidig integrasjon må konvertere kontrollert. Returens `total_minor` er negativ eller null. Kontantdifferanse beregnes som opptalt minus forventet.

Tidsstempler i journal, dokumenter og periodeåpning/-lukking settes av databasetriggere med `UTC_TIMESTAMP(6)`. `business_date` er en separat norsk driftsdato som tjenesten må bestemme korrekt. Brukerflaten skal fortsatt vise norsk dato og 24-timers klokke.

Ingen fremmednøkler opprettes mot gamle tabeller med ukjent tegnsett/kollasjon. `order_id` og operatør-ID er derfor referanser som den kommende tjenesten må kontrollere mot riktig bedrift. Alle fremmednøkler mellom de nye tabellene inkluderer bedriftsavgrensningen.

## Integritetsregler

Tolv triggere gjør følgende:

- Fordeler periode-, journal- og dokumentnummer under lås på kassapunktet.
- Oppdaterer nummerhoder i samme transaksjon som innsettingen. Rollback ruller også hodene tilbake.
- Validerer JSON-objekter og beregner journal-/dokumentkontrollsummer fra lagret UTF-8-tekst.
- Avviser vanlig `UPDATE`/`DELETE` på journal og dokumenter, også «ingen endring»-oppdateringer.
- Bevarer åpningsopplysninger og avviser endring av en avsluttet periode eller sletting av perioder.
- Krever Z-dokument fra riktig periode, avsluttende operatør og opptelling for å avslutte en periode.
- Tillater bare én Z-rapport per periode og én kopikvittering per original innen bedriften, også ved bruk av et annet kassapunkt.

Nummer- og hashfeltene på innsetting settes av triggere; klienten skal ikke styre dem. Ved dokumentproduksjon må tjenesten først låse kassapunktet og avlese de neste numrene, bygge korrekt dokument-/journalinnhold og skrive begge i **samme korte transaksjon**. Låserekkefølgen er kassapunkt, periode, journal, dokument. Ikke vent på Vipps eller utskrift mens denne transaksjonen holdes åpen.

En unik retry-nøkkel hindrer dobbelt innsetting, men er ikke en full idempotens-API alene. Tjenesten må ved retry slå opp eksisterende hendelse, verifisere at innhold/ordre stemmer og returnere samme dokument. Ikke bruk `REPLACE`, `INSERT IGNORE` eller `ON DUPLICATE KEY UPDATE` for journal/dokumenter.

`signature`, `signature_algorithm` og `signing_key_id` er kun reserverte dokumentfelt. De inneholder ingen privat nøkkel, og SQL-filen foretar ingen signering eller signaturkontroll. De må enten være fraværende sammen eller utfylte sammen. Eventuell signatur må være ferdig før dokumentet lagres uforanderlig.

## Hashformat SRJ1

Dette er en intern integritetskjede, **ikke** SAF-Ts digitale signaturformat.

`payload` er `LONGTEXT`, ikke MySQLs normaliserende JSON-lagring. Teksten må være et gyldig JSON-objekt. Bevar eksakte UTF-8-bytes, inkludert mellomrom, ved eksport/verifikasjon.

1. `payload_sha256 = lowercase_hex(SHA256(payload_bytes))`.
2. Kjedematerialet starter med ASCII `SRJ1`.
3. Legg deretter til disse feltene, hvert kodet som ASCII byteantall, kolon, så feltets UTF-8-/ASCII-bytes:
   - company_id
   - register_id
   - id
   - sequence_number i desimal uten innledende nuller
   - period_id, tom streng ved NULL
   - actor_id, tom streng ved NULL
   - order_id, tom streng ved NULL
   - event_type
   - idempotency_key
   - system_version
   - recorded_at_utc som `YYYY-MM-DDTHH:MM:SS.ffffff`, uten tidssoneendring
   - previous_hash; 64 ASCII nuller for første hendelse
   - payload_sha256
4. `entry_hash = lowercase_hex(SHA256(kjedematerialet))`.

Registrenes `last_*`-felt er mutable kontrollhoder, ikke selve journalen. Tjenesten og en uavhengig kontroll må sammenholde dem med hele journal-/dokumentsekvensen.

## Begrensninger og rettigheter før aktivering

- Triggere beskytter normal DML, ikke en databaseadministrator som kan droppe triggere/tabeller eller endre lagringsfiler.
- Runtimebrukeren skal ha begrensede rettigheter; ikke DDL/TRIGGER, ikke direkte oppdatering av teller-/hashhoder. Installerte triggere kjører med importørens definer-rettigheter, som må vurderes ved flytting og restore.
- Tilgangsstyring, beskyttelse av kassapunktidentitet, rolle-/bedriftskontroll, låserekkefølge og fullført journal-/dokumentskriving må implementeres i applikasjonen.
- Status `draft`/`active` er ennå ikke en tilgangssperre implementert av SQL. Ingen kassapunkt aktiveres av importen.
- Tjenesten må sperre Z-avslutning ved uavsluttede salg eller uavklarte betalinger. Datagrunnlaget kjenner ikke eksisterende kurvers kassapunkttilknytning.
- Tjenesten må verifisere at JSON-snapshotets nummer, summer, MVA og selger-/vareopplysninger stemmer med dokumentkolonnene og journalhendelsen. JSON-validering alene gjør ikke innholdet riktig.
- Endring av kassapunktoppsett og alle øvrige relevante hendelser må journalføres gjennom kommende tjeneste. SQL oppdager ikke automatisk all aktivitet i eksisterende moduler.
- Behandling av utskrift, papirkvittering/elektronisk utlevering og maskinvarekrav må avklares konkret også for mobile enheter. «Mobil» gir ikke i seg selv et generelt unntak.
- Ekstern sikring/ankring, sikkerhetskopi, restore-prosedyre, oppbevaring, SAF-T-eksport/signatur, rapportberegning, dokumentmerking og produkterklæring gjenstår.

## Isolert verifikasjon

Kjør `bash tests/rental_unavailable_mysql_isolated_test.sh --sales-register-schema`.

Testen starter en midlertidig MySQL med bare lokal Unix-socket. Den bruker ikke prosjektets databasekonfigurasjon eller produksjonsdata, og fjerner testdatabasen ved avslutning.
