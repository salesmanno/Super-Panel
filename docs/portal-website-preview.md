# Valgfri ny portalvisning

Den nye visningen aktiveres per bruker. Ingen brukere flyttes automatisk.
Kjør manuelt i riktig database og erstatt `BRUKERNAVN`:

```sql
UPDATE `salesmanno04`.`tenant_admins`
SET `view_mode` = 'wpreview'
WHERE `username` = 'BRUKERNAVN';
```

Last portalen på nytt. For å bruke den gamle visningen igjen:

```sql
UPDATE `salesmanno04`.`tenant_admins`
SET `view_mode` = 'legacy'
WHERE `username` = 'BRUKERNAVN';
```

Bare nøyaktig `wpreview` velger den nye visningen. Ukjente verdier og eldre
installasjoner uten kolonnen beholder legacy. Oppdateringen kjører ingen DDL.

## Hva som er koblet til faktiske data

- Hjem henter synlige, publiserte og ikke utløpte firmanyheter.
- Tjenester leser offentlige og synlige `output_products` med kildeinformasjon.
  Både brukerens modultilgang og bedriftens aktive moduler må tillate modulen.
- Firmaets logo og headerinnstillinger leses fra eksisterende nettsideoppsett.
- «Ny» på Hjem åpner artikkelredigeringen i et eget wpreview-vindu.
- «Rediger» og trykk på et nyhetskort åpner riktig sak direkte i samme
  vinduslayout. Henting, publisering og lagring bruker den faktiske nyhetseditoren.
- «Ny» på Tjenester viser tilgjengelige tjenestetyper og åpner de eksisterende
  opprettelsesskjemaene. Felt, validering, betaling og lagring gjøres fortsatt
  av disse modulene; de erstattes ikke med den forenklede mockupens data.
- Nyheter, arrangement og utleie åpnes som større dialogflater, og opprettelse
  av medlemskapsplan bruker en utvidet modal. De opprinnelige skjemaelementene
  flyttes midlertidig inn i vinduet og tilbake ved lukking. Det opprettes ikke
  parallelle kopier av skjemaene eller nye lagringsløp.
- Arrangement opprettes uten medlemspriser eller medlemsrestriksjoner i
  veiviseren. Disse settes etterpå under Fordeler.
- «Se på» på et medlemskapskort åpner medlemslisten avgrenset til akkurat
  kildeplanen. Filteret beholdes også dersom planvalgene lastes på nytt.
- Lukking bevarer modulenes DOM og henter oppdatert sideinnhold. Ulagrede
  nyhetsendringer krever bekreftelse før vinduet lukkes.
- «Se på» på et arrangement åpner den eksisterende, komplette deltaker- og
  innsjekkingsmodalen. Redigering retter seg direkte mot kildeobjektet.
- Kontrolløya bruker separate handlinger: Se på, Rediger, Fordeler og
  Skjemafelt. De skal aldri falle tilbake til samme generelle modulvisning.
  Skjemafelt åpner eksisterende `dynamic_form_fields` for arrangement,
  utleieobjekt, salgsprodukt eller medlemskapsplan i egen modal med
  riktig `entity_type` og kilde-ID. Feltlagring bruker samme tilgangskontroll,
  revisjonskontroll og audit som de eksisterende redigeringene.
- Fordeler åpner den valgte tjenestens fordelsmodal direkte, ikke en ny
  katalog. Redigering av utleie venter på at objektlisten er hentet.
- Logg ut ligger i brukerknappen øverst til høyre i headeren, ikke i den
  hvite sidenavigasjonen. Knappen bruker innlogget navn og initialer.
- Om oss samler Bedriften, Ansatte, Firmabrukere, Om oss-tekst, Webside,
  E-post, Økonomi og tilgjengelige administrasjonsområder. På mobil brukes
  kompakte, linjebrytende faner i stedet for en stor sidekolonne eller en
  bred, rullbar meny. Øvrige valg ligger under «Flere verktøy».
  Bedriftsinnstillinger krever ikke et ekstra passord; vanlig innlogging,
  aktiv bedrift og supervisor eller delegert Ditt nettsted-tilgang gjelder
  fortsatt, også ved lagring og bildeopplasting. Firmabrukere vises bare for
  supervisor, og modulområder følger brukerens delegerte tilgang.
- Webside bruker samme grupper som mockupen: Profil og farger, Visning og
  navigasjon, Søkemotorer og deling. Øvrige eksisterende felter er beholdt
  under «Flere nettsideinnstillinger». Hele skjemaet sendes fortsatt til
  `api/update_webside_data.php`; urørte innstillinger fjernes ikke.
  `config_*` lagres i `tenant_website_config`, og `general_*` lagres i
  `tenant_website_general`. Firmanavn lagres i `tenant_companies.name` i
  samme transaksjon. Ingen nye tabeller eller kolonner kreves.
- Farger, font, logo, høyder, navigasjon og statisk/dynamisk header/footer
  vises i preview før lagring. Ulagrede endringer krever bekreftelse ved
  navigasjon bort. Lagringsfeil beholder verdiene og lar brukeren prøve igjen.
- Den overliggende administrasjonsheaderen åpnes ved en ekstra oppovergest
  fra toppen, eller via «Åpne kontrollrom» i brukerknappen. Vanlig scrolling
  åpner den ikke. Touch krever et nedtrekk som starter helt øverst.
  Panelet ligger over innholdet og forskyver eller låser ikke body.

## Avgrensning

Dette er en innlogget forhåndsvisningsinngang, ikke en erstatning for kundesiden.
De eksisterende modulenes felt og lagringsfunksjoner beholdes, men monteres i
avgrensede wpreview-vinduer eller innstillingsflaten i Om oss. Hele
legacy-navigasjonen skal aldri vises for å utføre en preview-handling.

Canvas-demoen er fortsatt lokal og skriver ikke til databasen. Dens
opprettelsesvalg illustrerer arbeidsflyten, mens portalen bruker de komplette
eksisterende skjemaene.

## Eksport

Ta med `pages/company_dashboard.php`, `includes/portal_view_mode.php`,
`includes/portal_website_preview.php`, `includes/portal_website_preview_data.php`,
`assets/portal-website-preview.css`, `assets/portal-website-preview.js`,
`assets/portal-preview-workspace.js`, `modules/announcements_content.php` og
`api/get_portal_website_preview.php`, samt deres eksisterende avhengigheter.
Ta også med `includes/portal_preview_account.php`, `includes/company_settings_access.php`,
`includes/ImageUpload.php` og oppdateringene til `api/load_ditt_nettsted_content.php`,
`api/update_company_data.php`, `api/update_webside_data.php` og `api/update_about_us.php`.
Den nye innstillingsvisningen krever også `assets/portal-preview-website.js`,
`assets/portal-preview-control-room.js`, `assets/portal-preview-settings.css` og
`includes/portal_preview_control_room.php`, samt oppdaterte
`includes/announcement_preview.php` og `includes/portal_website_preview_data.php`.
Mockup-sandkassen kreves ikke på PHP-webhotellet.

## Betalingsvalg fra kontrolløya

Øya viser maksimalt tre handlinger om gangen, med forrige-/neste-piler.
På Tjenester velges «Betalingsmetoder», deretter tjenestekortet. Vinduet
henter stående valg og lar administrator velge flere aktive, konfigurerte
metoder fra bedriftens betalingsoppsett. Det oppretter ikke nye betalingsoppsett.

Ta med disse filene ved eksport av denne endringen:

- `includes/portal_website_preview.php`
- `assets/portal-website-preview.js`
- `assets/portal-website-preview.css`
- `api/portal_service_payments.php`
- `includes/portal_service_payments.php`

API-et bruker den eksisterende `entity_form_csrf`-beskyttelsen, innlogget
administrator, bedriftsavgrensning og tjenestens modulrettighet. GET leser uten
å skrive. POST skriver en JSON-liste med kanoniske metodenavn (for eksempel
`["vipps","stripe"]`) til valgt rad i `events_eventdata.payment_methods_json`,
`membership_plans.payment_methods_json` eller `rental_objects.payment_methods_json`.
Tom liste betyr ingen valgte betalingsmetoder, ikke automatisk «alle».
Kundesidens betalingsvalg må lese denne listen og kontrollere at metodene
fortsatt er aktive ved betaling.

Bindende arrangementer tillater ikke kontant eller tomt betalingsutvalg.
Arrangementer uten påmelding har ingen betalingsvalg. Ved endrede
arrangementsvalg holdes også eksisterende standardbetalingsmetode og
betalingstidspunkt konsistente. Endringer og audit lagres i samme transaksjon;
uendret lagring skriver ikke audit. En versjonskontroll hindrer overskriving
av et betalingsoppsett som er endret etter at vinduet ble åpnet.

Ingen nye tabeller eller kolonner er nødvendige.
