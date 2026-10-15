# Nullstilling av Sales-testdata

Dette er separat kommandolinjevedlikehold for en bedrift som **bare har
testdata**, før reell kassedrift. Det er ikke en funksjon i Sales-grensesnittet
eller en tillatt måte å rette reelle salg på. Ingen nullstilling er automatisk
kjørt ved installasjon, oppstart, publisering eller oppgavefullføring.

## Omfang og konsekvenser

- Sletter valgt bedrifts Sales-ordre/-linjer, kurver/-linjer, Sales-rader i
  `unified_ledger`, kasseperioder, journal, kvitteringer og X-/Z-dokumenter.
- Sletter bare valgt bedrifts kølagte e-poster med `module_source='sales'`.
  Allerede sendte e-poster kan ikke trekkes tilbake.
- Beholder produkter, bilder, strekkoder, priser, dynamiske skjemafelt,
  betalingsoppsett, firma, brukere og andre modulers ledger.
- Beholder kassepunktenes identiteter/navn/koder, men setter dem til utkast og
  nullstiller testtellere og journalhoder. De må aktiveres og åpnes igjen.
  Nummereringen starter derfor på nytt: **aldri bruk dette etter reell drift**.
- Tilbakefører lagerantall fra støttede betalte testsalg. Allerede kansellerte
  salg og fullstendig returnerte kontantsalg har allerede tilbakeført lageret.
  Manuelle lagerjusteringer beholdes; lageret settes ikke vilkårlig til null.
- Beholder `audit_logs` og e-postlogger som spor etter vedlikehold/testaktivitet.
  De kan inneholde referanser til testhandler som er fjernet.
- Gjør ingen eksterne betalingskall eller refusjoner.

Ventende/uavklarte betalinger, foreldreløs ledger, eldre ordre uten kjent
kontrollmetadata, ukjente Sales-tabeller/triggere og eksterne fremmednøkler
stopper forhåndskontrollen. Ikke fjern sperrene for å tvinge gjennom kjøring.

## Før du kjører

1. Bekreft at riktig database og bedrift er valgt, og at ingen data er reelle.
2. Ta en **full, verifisert databasebackup** med webhotellets databaseverktøy.
   Den ekstra JSON-sikkerhetskopien nedenfor erstatter ikke full databasebackup.
3. Stopp **hele appens databasetrafikk**, inkludert eksterne prosjekter som
   bruker samme database, cron, e-postkø og betalingsavstemming. Et vedlikeholdsskilt
   i nettleseren alene stopper ikke bakgrunnsjobber. Hold andre kasser offline.
4. Bruk SSH/terminal på målserveren. Scriptet kan ikke åpnes i nettleser/phpMyAdmin.
   Hvis webhotellet ikke gir CLI-/TRIGGER-tilgang, må dette kjøres av databaseadministrator.
5. Opprett en privat mappe utenfor **alle** dokumentrøtter og prosjektmapper.
   Mappen skal eies av vedlikeholdsbrukeren med rettigheter `0700`.

## Bruk

Bytt `UUID` med valgt bedrifts `company_id`. Forhåndskontroll skriver bare antall,
berørte produkt-ID-er/lagerantall, databasenavn og fingerprint — ikke kundedata.

```bash
php scripts/reset_sales_test_data.php --company=UUID
```

Les kontrollrapporten, og bruk den returnerte fingerprinten som `FINGERPRINT`.
Ingen endringer skjer uten alle flaggene:

```bash
php scripts/reset_sales_test_data.php \
  --company=UUID \
  --apply \
  --expected=FINGERPRINT \
  --confirm="RESET TEST SALES UUID" \
  --offline-confirmed \
  --full-backup-confirmed \
  --backup-dir=/absolutt/privat/backupmappe
```

CLI bruker prosjektets eksisterende databaseforbindelse. Sjekk databasenavnet
i kontrollrapporten. Det endres ikke til en Replit-database.

## Sikkerhet og feil

MySQL/MariaDB har ingen transaksjonell `DROP TRIGGER`. De tre slettesperrene
må tas bort midlertidig i **hele databasen**, selv om alle slettinger er
avgrenset til én bedrift. Derfor kreves full nedetid og ekstern databasebackup.
Ingen permanente unntak/backdoors legges inn i triggerne.

Verktøyet lagrer en privat mappe med `snapshot.json` (eksakte rader,
tabellstruktur, triggerdefinisjoner, lagerplan), `restore-delete-guards.sql`
og `status.json` før sperrer fjernes. Filer har rettigheter `0600`. Backup
inneholder personopplysninger fra testene og må aldri legges i Git, lastes opp
til chat/Library eller lagres under en webrot.

Selve dataendringen er én InnoDB-transaksjon. Rader kontrolleres på nytt etter
backup og DDL-grensen. Ved feil rulles data tilbake; slettesperrene
gjenopprettes i oppryddingen. Ved hard prosess-/serverstans er denne oppryddingen
ikke garantert. Hold derfor appen offline til kontrollene under er bestått.

- Kontroller `status.json`: `phase` skal være `committed`, `missing_guards` tom.
  Ved `ready-to-commit` eller manglende status er utfallet uavklart; undersøk
  databasen før ny kjøring. **Ikke anta at en avbrutt kommando er reversert.**
- Ved manglende slettesperrer: gjenopprett originalene med
  `restore-delete-guards.sql` på samme database mens **alt fortsatt er offline**.
  Denne filen gjenoppretter bare sperrene, ikke slettede data.
- Ved behov for gjenoppretting av data: bruk den fullstendige databasebackupen,
  sammen med den private snapshoten, under databaseadministrators kontroll.
  Ikke importer rå journalrader gjennom aktive journaltriggere eller forsøk
  å konstruere gamle kvitteringer fra dagens produktdata.
- Kjør forhåndskontroll igjen. Historikktabellene skal være tomme for valgt
  firma; produkter og kassepunkter finnes fortsatt. Alle tolv kassetriggere
  skal være tilbake. Først da kan app og jobber startes igjen.

Verktøyet er midlertidig støtte for testing, ikke dokumentasjon på at Salesman
oppfyller kassasystemregelverket. Ikke distribuer dette som en ordinær
sluttbrukerfunksjon i en produktdeklarert kasseløsning.
