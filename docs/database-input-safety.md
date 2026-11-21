# Sikker behandling av feltinnhold

Tekst som `sudo rm -rf /` er data, ikke en kommando. Den skal ikke kjøres,
og trenger ikke ødelegges eller fjernes fra et legitimt tekstfelt.

## Felles databasevern

Den delte MySQL-forbindelsen bruker `AuditedPDO`. Konstruktøren håndhever
ekte prepared statements, deaktiverer flere SQL-setninger i samme kall
og deaktiverer `LOAD DATA LOCAL INFILE`. Dette gjelder også når en
tilkobling opprettes med andre innstillinger.

Dette vernet alene stopper ikke all SQL-injeksjon. Alle verdier fra skjemaer,
URL-er, sesjoner og tidligere databaseinnhold må fortsatt bindes som parametere.
Tabellnavn, kolonner og sorteringsretning kan ikke bindes som verdier og må
velges fra faste tillatte valg. Ikke sett vilkårlige feltnavn fra en forespørsel
direkte inn i SQL.

## Ikke bruk en generell tekstvask som sikkerhetsgrense

- Valider tall, datoer, lengder og andre krav ut fra feltets formål.
- Ikke bruk `addslashes`, fjerning av SQL-ord eller søk etter `sudo` som vern.
- Ikke send feltinnhold til shell, `eval` eller dynamisk inkludering av kode.
- Ved HTML-visning må tekst escapes. Nyhetsinnhold med tillatt HTML trenger
  en egen tillatelsesbasert HTML-behandling. SQL-parameterbinding stopper ikke XSS.
- Begrens databasebrukerens rettigheter på webhotellet. Klientinnstillingene
  over erstatter ikke serverrettigheter og hindrer ikke enhver serverstyrt
  filoperasjon dersom en bruker allerede har slike rettigheter.

## Verifikasjon

`bash tests/rental_unavailable_mysql_isolated_test.sh --database-security`
oppretter en midlertidig, socket-basert testdatabase uten prosjektets
databasekonfigurasjon. Testene bekrefter at kommando- og SQL-lignende strenger
lagres uendret, at parametere ikke endrer SQL-strukturen, og at flerspørringer
og lokal filinnlesing blokkeres. Audit-testen kjøres også som regresjonskontroll.

Dette er ikke en erklæring om at alle endepunkter i prosjektet er
sikkerhetsrevidert, eller at rå HTML nå kan vises uten trygg behandling.
