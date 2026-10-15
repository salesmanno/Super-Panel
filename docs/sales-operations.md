# Sales – drift og betalingsoppsett

## Lagring og kontant

### Eksport og manuell databaseklargjøring

`sql/sales_checkout_setup.sql` følger prosjektet ved eksport. Velg målbasen
og kjør filen manuelt hvis den ikke allerede er oppdatert. Skriptet oppretter
bare de to avtalte ordretabellene dersom de mangler, supplerer nødvendige kolonner
og fjerner bare den gamle unike kurvprodukt-indeksen. Det sletter ingen rader.
Det kan kjøres flere ganger uten dupliserte kolonner eller indekser. De eksisterende
basistabellene `sales_products`, `sales_cart` og `sales_cart_items` må være importert.
Du som allerede har kjørt endringene, trenger ikke nye tabeller for kontant/veksel.
Skriptet er **ikke** kjørt mot appens virkelige database av agenten.

- Ingen nye kassetabeller opprettes av applikasjonen, heller ikke ved HTTP-kall.
- Ordre og uforanderlige varelinjer ligger i `sales_orders` og
  `sales_orders_items`. Betalingsføringen ligger i eksisterende `unified_ledger`.
- Kontant er alltid tilgjengelig og forhåndsvalgt **bare i Sales**.
- Seddelknapper angir mottatt total, ikke et beløp som legges til tidligere input.
  Kassen viser veksel umiddelbart og krever mottatt beløp minst lik totalsummen.
- Mottatt beløp og veksel bevares som heltallsøre i ledgerens
  `metadata.sales.received_minor` og `metadata.sales.change_minor`.
- Nye transaksjoner via felles `UnifiedLedgerService` får
  `metadata.tenant_admin_id` fra en verifisert administrator i samme bedrift.
  Verdien kan ikke velges i klientens metadata. Sales lagrer dessuten samme
  administrator i `sales_orders.created_by`, og kvitterings-API-et returnerer ID-en.
  Automatiske oppdateringer beholder initiativtakeren og har egen systemaktør i
  auditloggen. Historiske rader uten aktør får ikke en oppdiktet tilordning.
- Kassapunkt, åpningsbeholdning, kontantbevegelser og fysisk opptelling ved
  X-/Z-oppgjør bruker de avtalte kassetabellene. Se
  [kasseintegrasjonen](sales-register-integration.md) for oppsett og avgrensninger;
  fungerende betaling eller journal alene dokumenterer ikke full regelverksetterlevelse.
- En fullført ordre gjenfinnes etter mistet svar eller omlasting frem til
  «Neste kunde» starter en ny kurv. Gjentatt bekreftelse bruker ikke ny ordre.

## Varefelt og lager

- Produktets feltdefinisjoner ligger i `dynamic_form_fields`,
  `entity_type='sales_product'`, `entity_id=produktets id`, med bedriftsscope.
- Svar og notater følger kurvlinjens ID og fryses på ordrelinjen. Samme vare med
  forskjellige svar/notater er forskjellige linjer; lagerkontrollen summerer dem.
- `stock_quantity` betyr **tilgjengelig beholdning**. Ventende betaling reserverer
  ved å trekke tilgjengelig antall i samme transaksjon som ordren og ledgeren.
  Ikke registrer fysisk totalbeholdning som tilgjengelig antall uten å hensynta
  varene som allerede er reservert.
- Verifisert capture trekker ikke lager igjen. Verifisert terminal avbrytelse
  frigjør nøyaktig én gang og gjenåpner opprinnelig kurv. Ukjent status og lokal
  tidsfrist frigjør aldri reserverte varer.
- Genererte koder er interne Code128-B, ikke GS1/EAN. Gyldige eksisterende koder
  beholdes. Kopiering, utskrift og skanning skal ikke endre koden.
- Produktkatalogens kobling og kanalflagg vedlikeholdes transaksjonelt.
- Kiosken selger ikke mer enn tilgjengelig lager. Det misvisende valget «Tillat
  salg uten lager» er fjernet. Eldre `is_backorder`-verdier bevares, men overstyrer
  ikke lagerkontrollen i katalog, kurv eller betaling.

## Valgfri e-postkvittering

Kvitteringsvinduet tilbyr «E-postkvittering» bare med aktivt oppsett. Utsending
krever `email_configs.automail_sales_order = 1`, aktiv bedriftsmal med nøyaktig
`template_key = 'sales_receipt'`, og et fullført/betalt salg. Serveren kontrollerer
flagget på nytt når brukeren sender, ikke bare når vinduet åpnes. Utskrift er
uavhengig av automaileren. Det sendes ikke automatisk e-post til alle kjøp.

Mottaker oppgis i kvitteringsvinduet. Meldingen legges i eksisterende `email_queue`
gjennom `EmailService`, med `module_source=sales` og `source_id=ordre-ID`.
Vanlig køprosessering står for faktisk levering. Gjentatt sendeforsøk med samme
referanse gir samme kømelding. En uttrykkelig ny handling kan sende på nytt.

Tilgjengelige tags er `{{company_name}}`, `{{order_id}}`, `{{order_number}}`,
`{{order_date}}`, `{{payment_method}}`, `{{payment_status}}`, `{{total_amount}}`,
`{{total}}`, `{{subtotal}}`, `{{tax_total}}`, `{{amount_paid}}`,
`{{received_amount}}`, `{{change_amount}}`, `{{tenant_admin_name}}` og varetabellen
`{{items_table}}` (aliaser `{{items}}`, `{{order_items}}`, `{{items_html}}`).
Varedata hentes fra de lagrede ordrelinjene. Kundetekst HTML-escapes, og hardkodet
maltekst omskrives ikke. Det brukes ingen fallback til `sale_receipt`, og ingen
maler opprettes eller erstattes automatisk.

## Vipps på ekstern PHP-server

Det er **ikke** kjørt reelle betalinger, refunds eller endringer av ekte kundedata
under implementering. Betalingstestene bruker en isolert database og leverandør.

Sales leser betalingsnøkler på serveren fra valgt bedrifts
`economy_configuration_data`, koblet via `economy_payment_gateways`.
Betalingsoppsettet vedlikeholdes i det separate konfigurasjonsprosjektet.
Det kreves ikke `VIPPS_CREDENTIALS_JSON` for Sales, og nøklene skal aldri
sendes til nettleseren eller legges inn i kildekoden. Web-PHP og cron-PHP må
bruke samme database og betalingskonfigurasjon.

Se [trygg Vipps-diagnostikk](sales-vipps-diagnostics.md) for de nye feilkategoriene,
feilreferansene og hva de isolerte testene faktisk verifiserer.

- Betalingsoppsettet må være aktivert i eksisterende Economy-oppsett og knyttet
  til riktig bedrift og konfigurasjon. `sales_orders.payment_gateway_id` peker på
  `economy_payment_gateways.id`, ikke konfigurasjonens ID.
- Manglende sikre nøkler deaktiverer Vipps i kassen; kontant fungerer fortsatt.
- Testkonfigurasjoner kan ikke brukes til ordinære salg. De vises deaktivert med
  forklaring, slik at testcapture ikke bokføres som ekte omsetning.
- Online/both krever tilgjengelig, konfigurert nettbetaling, både ved opprettelse
  og endring. Kontant oppfyller ikke dette kravet.
- Betaling bruker telefonnummer i samme panel, `PUSH_MESSAGE` og
  `CUSTOMER_PRESENT`. Referanse, merchant-tilknytning og idempotens er bundet til
  den varige ordren før første HTTP-kall.
- Autorisasjon og godkjent capture-forespørsel er ikke tilstrekkelig. Bare
  GET-verifisert full capture med riktig referanse, beløp og NOK blir betalt.

### Callback og avstemming

Registrer en HTTPS-webhook hos leverandøren til
  `/api/sales_vipps_webhook.php`, med hemmeligheten i bedriftens betalingskonfigurasjon.
Implementasjonen kontrollerer HMAC, body-hash, tidsstempel, host og sti.
Webhookens hendelsesstatus brukes ikke til bokføring; aktuell leverandørstatus
hentes på nytt. Forsinkede og gjentatte callbacks kan ikke reversere betalt ordre.

Sett i tillegg opp følgende CLI-jobb eksempelvis hvert minutt på målserveren:

```sh
php /full/sti/til/prosjektet/cron_sales_payments.php
```

Jobben behandler et begrenset antall ventende ordre, eldste kontroll først.
Den er ikke tilgjengelig via HTTP og starter ingen nye ordre. Det ble ikke
aktivert noen automatisk betalingsjobb eller registrert eksterne webhooks under
implementering. Leverandørtilkobling og endelig driftsprøve krever eget oppsett.

## Eldre API-er

Produktlagring og lagerjustering via eldre adresser sendes gjennom den nye
kontrolleren og krever de samme rettighetene, sikkerhetskoden og samtidighets-
kontrollene. Inkompatible gamle salgs-/kurv-/innkjøpsendepunkter returnerer
HTTP 410 i stedet for å skrive til fjernede tabeller eller omgå lager/betaling.
Eksisterende tabeller og historiske data er ikke slettet.

## Verifikasjon

```sh
bash tests/rental_unavailable_mysql_isolated_test.sh --sales
node tests/sales-admin-browser.test.js
```

Databasetesten er socket-isolert og bruker ikke appens databasekonfigurasjon.
Nettlesertesten bruker PHP-renderet modul og stubbede API-/betalingssvar ved
320, 390, 768, 1024 og 1280 px. Den erstatter ikke en egen driftsprøve med ekte
bedriftsinnlogging og konfigurert Vipps.
