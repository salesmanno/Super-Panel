# Medlemsfordeler: simulator og neste lagringstrinn

## Det som gjelder nå
Fordeler-subfanen er en simulator. Den leser firmaprofil, produktkatalog,
aktive medlemsplaner og eksisterende arrangementspriser. Endringer i
simulatoren er midlertidige og skriver ikke til databasen.

Utleieobjektets `member_exclusive` leses i simulatoren når kolonnen finnes.
Dette alene aktiverer ikke medlemskontroll i det separate kundesideprosjektet.

## Foreslått felles kobling
Brukeren har opprettet tabellen som `membership_product_modifications`.
Referanseskjema finnes i `sql/membership_product_modifications.sql`; det skal
ikke kjøres på nytt. Appen og simulatoren oppretter ingen tabeller.
Eksisterende `event_membership_plans` beholdes.
Det er ingen automatisk migrering eller dobbeltskriving.

Hver rad knytter et `output_products.id` til én `membership_plans.id` i samme
bedrift. Flere planer betyr flere rader. Én aktiv rad er én selvstendig
rettighet/fordel, ikke et krav om at kunden skal ha alle planene samtidig.

- `access`: plan gir tilgang uten en særskilt pris; verdien er NULL.
- `fixed_price`: egen medlemspris; verdien er et ikke-negativt beløp.
- `percent_discount`: prosentavslag fra 0 til 100.
- `amount_discount`: ikke-negativt kroneavslag, aldri negativ sluttpris.
- `included`: ingen ekstra betaling; verdien er NULL.
- `unit`: pris per billett, vareenhet eller uttrykkelig definert utleieenhet.
- `booking_total`: fordel på beregnet leiesum, ikke depositum eller andre tillegg.
- `public_display`: om fordelen annonseres offentlig. Dette er ikke en
  tillatelseskontroll og må ikke fjerne rettigheten til et verifisert medlem.

`member_exclusive` beskriver objektets tilgangskrav; koblingene beskriver
hvilke planer som kvalifiserer. Medlemspris uten eksklusivitet er tillatt.
Ved eksklusivitet og ingen aktive plankoblinger skal ingen automatisk gis
tilgang. Alle nye lagringer må validere bedrift, objekt og plan på serveren.

## Før regler kan aktiveres
Adminlagring med modulrettigheter, CSRF og revisjonskontroll må innføres.
Kundesiden må verifisere gyldig medlemskap og bruke samme prisregler som
serveren som oppretter reservasjonen eller handelen. Rabattberegning,
flere kvalifiserende medlemsplaner, utleieperioder, avrunding og MVA må
ha én avtalt regel uten rabattstabling.

Før migrering må eksisterende arrangementspriser sammenlignes med ny
kobling. Inntil den overgangen er testet må eksisterende hendelser bruke
dagens autoritative arrangementspriser, ikke blande to priskilder.

SQL-en alene endrer ingen kjøpsflyt, gir ingen rabatter og håndhever ingen
medlemskrav. Ikke slett eller endre eksisterende pristabeller i mellomtiden.

## Katalogkoblinger
Simulatoren viser nå en egen administrativ fordelsoversikt på tilbudskortene:
arrangementenes eksisterende medlemspriser og aktive, bedriftsavgrensede regler
fra `membership_product_modifications` for andre tjenester. Medlemsplan, type
fordel, offentlig/privat prisvisning og eventuell beregning av bookingtotal vises.
Dette er indikatorer, ikke ny håndheving i kundens kjøpsflyt. Midlertidige
simulatorendringer merkes «Ikke lagret» og holdes adskilt fra lagrede regler.

Opprettelse og redigering av utleieobjekter sikrer nå en katalogkobling i samme
transaksjon. Sales reparerer også manglende kobling ved uendret lagring.
Koblingen valideres mot bedrift, kildetype og kilde-ID. Eksisterende skjuling
bevares ved reparasjon; nye kioskvarer blir ikke offentlige.
Eksplisitt skjuling eller visning på kilden oppdaterer katalogstatusen;
vanlige pris- og navneendringer gjør ikke skjulte katalogoppføringer synlige.

Eldre objekter og varer kan kontrolleres uten skriving:
`php scripts/repair_source_catalog_links.php --company-id=BEDRIFTS_ID`
Legg til `--apply` for å gjennomføre etterkoblingen for bare denne bedriften.
Hele etterkoblingen rulles tilbake ved feil. Dubletter rapporteres, men slettes
ikke, siden nyheter eller medlemsfordeler kan peke til dem.
