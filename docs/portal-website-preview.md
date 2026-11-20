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
Mockup-sandkassen kreves ikke på PHP-webhotellet.
