# Arrangementsdashboard

Ny første subfane inne i eksisterende Events-modul. Alle tidligere subfaner,
lenker og visninger beholdes. Ingen ny app, databasekolonner eller migreringer.

## Integrasjon

- `assets/events-dashboard.js` og `.css` eier de nye kortene, oversiktene,
  filtrene og det innebygde feltpanelet. Stiler avgrenses til Events.
- `loadEvents()` sender `events-dashboard-source`; både gammel liste og nytt
  dashboard bruker denne datakilden. Skjulte og avsluttede arrangementer finnes
  via filtrene. Automatisk nettleserutfylling filtrerer ikke listen.
- Påmeldings-/innsjekkingsoversikter flytter eksisterende DOM midlertidig inn i
  oversiktsmodalen, ikke kopier. De gamle subfanene gjenopprettes ved lukking
  eller navigasjon. Påmeldingsfilter bruker arrangement-ID, ikke navn eller dato.
- Godkjenning/avslag bruker de eksisterende endepunktene og deres automailer-,
  e-post- og betalingsregler. Dashboardet sender ingen egen e-post og legger
  ikke nye ledger-poster oppå disse handlingene.
- `counts_loaded` og `membership_prices_loaded` skiller vellykket lasting fra
  feil. Manglende telling/prisoppsett må ikke tolkes som null/ingen medlemspriser.
- Grunnopplysninger gjenbruker redigeringsmodalen. Valgfrie `membership_prices`
  i `update_event.php` avstemmes i samme transaksjon som grunnopplysningene,
  krever Membership og kontrolleres mot firmaets medlemsplaner. Manglende felt
  bevarer eksisterende medlemspriser; et eksplisitt tomt oppsett krever tilgang.
  Utelatte priser for inaktive eller tidligere manglende planer bevares selv ved
  tomt aktivt prisutvalg og vises som historiske, ikke-redigerbare priser.
  Fjerning av et aktivt prisvalg fungerer fortsatt.
- `set_event_visibility.php` kontrollerer CSRF, Events-tilgang og låst
  firma-/arrangements-/katalogkobling. «Skjul» bevarer historikk. «Vis» gjenoppretter
  aktivt arrangement og offentlig katalogsynlighet. Permanent sletting bruker
  eksisterende historikkvern, ikke den nye synlighetsfunksjonen.
- Grunnopplysninger og synlighetsendring henter bare sikkerhetskode, slik at
  ugyldig skjemafeltoppsett ikke blokkerer uavhengige handlinger.
- Feltpanelet bruker `dynamic_form_fields`, revisjonskontroll, presets,
  avkryssinger og `sort_order`. Bare ett felt åpnes om gangen. Feil beholder
  utkast; vellykket lagring lukker panelet. Navigasjon/feltbytte beskyttes når
  panelet er opptatt eller har ulagrede endringer.

## Verifikasjon

- `node tests/events_dashboard_chromium_test.js`: faktisk PHP-modulmarkup og
  Bootstrap, isolerte API-svar, 320/390/1280 px, Membership-avgrensning,
  medlemsprislagring, feltlagring/rekkefølge, feilbevaring, modaloverganger,
  ID-baserte påmeldingsfiltre og bevarte gamle visninger.
- `node tests/events_wizard_chromium_test.js`: eksisterende opprettelse,
  redigering, datoer, betalingsvalg og Membership-avgrensning.
- `bash tests/rental_unavailable_mysql_isolated_test.sh --forms`: ekte HTTP
  mot isolert MySQL, inkludert nye kontroller i
  `tests/events_dashboard_fixture_checks.php` for priser, tilbakeføring,
  synlighet, tilgang, CSRF, lastingsfeil og eksisterende beskyttet sletting.
- `--event-payment-audit` og `--active-payments` i samme harness kontrollerer
  eksisterende betalingsflyter; Vipps-transporten er stubbet, ingen ekte belastning.
- `node tests/rental_dashboard_chromium_test.js` dekker tilbakefall i den delte
  feltredigeringen uten endringer i Rental-modulen.

Den ekte innloggede flaten er ikke verifisert fra Replit. Nettlesertestene bruker
rendret modulmarkup i midlertidige fixtures, ikke en autentiseringsbypass i appen.
Forbindelsen til ekstern MySQL var avvist under den opprinnelige undersøkelsen;
senere serverlogger rapporterer tilkobling. Det bekrefter ikke den innloggede
UI-flyten. Arbeidets database-/skrivetester bruker bare isolerte fixtures.
