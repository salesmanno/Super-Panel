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
- «Ny» på Hjem åpner eksisterende nyhetseditor.
- «Ny» på Tjenester viser tilgjengelige tjenestetyper og åpner de eksisterende
  opprettelsesskjemaene. Felt, validering, betaling og lagring gjøres fortsatt
  av disse modulene; de erstattes ikke med den forenklede mockupens data.
- Nyheter, arrangement og utleie åpnes som større dialogflater, og opprettelse
  av medlemskapsplan bruker en utvidet modal. De opprinnelige skjemaelementene
  beholdes på sin plass, slik at eksisterende lagringshendelser fortsatt virker.
- Arrangement opprettes uten medlemspriser eller medlemsrestriksjoner i
  veiviseren. Disse settes etterpå under Fordeler.
- «Se på» på et medlemskapskort åpner medlemslisten avgrenset til akkurat
  kildeplanen. Filteret beholdes også dersom planvalgene lastes på nytt.
- Tilbake-knappen bevarer modulenes DOM og utkast og henter oppdatert innhold.

## Avgrensning

Dette er en innlogget forhåndsvisningsinngang, ikke en erstatning for kundesiden.
De eksisterende administrasjonsmodulene brukes fortsatt som arbeidsflate ved
opprettelse og administrasjon. Om oss gir tilgang til disse modulene.
Mockupens detaljerte interne Om oss-paneler er ikke flyttet over som nye
lagringsskjemaer.

Canvas-demoen er fortsatt lokal og skriver ikke til databasen. Dens
opprettelsesvalg illustrerer arbeidsflyten, mens portalen bruker de komplette
eksisterende skjemaene.

## Eksport

Ta med `pages/company_dashboard.php`, `includes/portal_view_mode.php`,
`includes/portal_website_preview.php`, `includes/portal_website_preview_data.php`,
`assets/portal-website-preview.css`, `assets/portal-website-preview.js` og
`api/get_portal_website_preview.php`, samt deres eksisterende avhengigheter.
Mockup-sandkassen kreves ikke på PHP-webhotellet.
