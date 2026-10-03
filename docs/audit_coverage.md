# Dekningskart for bedriftsaudit

## Kartlegging før implementering

Den eksisterende `audit_logs`-tabellen brukes videre. Innsjekk av arrangementer har en lokal INSERT; innloggingshistorikk er separat. Innloggede ansatte får egne `tenant_admins`-kontoer knyttet til `tenant_staff`; aktøren skal derfor slås opp med faktisk konto-ID og bedrift, ikke med bedriftens første administrator.

Endringsflater som må omfattes:

| Område | Endringsflater | Ressurser |
|---|---|---|
| Utleie | opprette/redigere/slette objekt og kontrakt; forespørselsendring, beslutning, betaling, avlysning og avstenginger | objekter, kategoridata, forespørsler, kontrakter |
| Medlemmer | opprette/redigere/slette/avslutte/fornye medlem; planer, felt og betaling | medlem, plan, kundekobling, regnskap |
| Arrangement | opprette/redigere/slette arrangement; påmelding, pris, innsjekk, no-show, betaling og økonomiposter | arrangement, påmelding, innsjekk, regnskap |
| Økonomi | inntekter/utgifter, transaksjoner, fakturaer, bankkontoer og betalingsinnstillinger | eksisterende økonomitabeller og unified ledger |
| Tilgang | ansatte, administratorer og modulgranter | ansatt, konto, modulgrant |
| Nyheter | opprette/redigere/slette, publisering, prioritet og pinning | firmanyhet |
| Innstillinger/e-post | bedriftsdata, automatiske e-postvalg, maler, kølegging og SMTP-resultat | bedrift, innstillinger, mal, kø og utsendelseslogg |

## Krav til felles mekanisme

- Serverautorisert identitet, bedriftsavgrensning og begrensede navnesnapshots.
- Bare eksplisitt tillatte før-/etterfelt; ingen passord, SMTP-verdier, tokens, session-ID, tilgangskoder eller meldingsbody.
- Audit-INSERT og databaseendring i samme transaksjon; et eksternt SMTP-resultat må ikke sendes på nytt fordi etterfølgende logging feiler.
- Faktisk endring, no-op, avvisning, feil og rollback er forskjellige utfall.
- Systemsteg og opprinnelig administrator er separate identiteter i korrelasjonen.
- Gammel innsjekkaudit beholdes, men skal ikke dobbelregistreres.
- Innloggingshistorikk blir ikke blandet med den nye endringsoversikten.

## Implementert mekanisme og innganger

- `config/database.php` bruker `AuditedPDO` for den eksisterende MySQL-forbindelsen. Eksisterende SQL og transaksjonsgrenser beholdes. `prepare/execute`, `exec` og muterende `query` for de deklarerte portalressursene instrumenteres; vanlige SELECT-er, innloggingshistorikk og innboksmerker er ikke auditaktiviteter.
- `includes/audit_resources.php` beskriver ressurser, modul og tillatte felt: `rental_*`, `membership_*`, `events_*`, `economy_*`, `tenant_website_*`, unified ledger, ansatte/kontoer/granter, kundekoblinger, nyheter/produktkoblinger og e-postkonfigurasjon/maler/kø. Arrangementets enkeltstående `event_membership_plans` og `event_registration_fields` er også dekket.
- Ressurser uten egen `company_id` får bedrift fra en eksisterende forelder, eksempelvis objekt, arrangement eller administrator. Dette er ikke en klientoppgitt identitet.
- `api/update_rental_schedule.php` har eksplisitte beslutningshooks med korrekt avslag/no-op/feil, faktisk malhash ved rendering, kø-ID og korrelasjon. Både beslutningen og køloggen inngår i samme transaksjon.
- `api/perform_checkin.php` bruker den felles aktøroppløsningen og erstatter bare fremtidige audit-INSERT-er. Historiske rader beholdes; eventets betaling og innsjekk beskrives uten kundens navn, e-post eller session-ID i den nye auditposten. Raden låses før endring.
- Den faktiske påmeldingstabellen `event_registrations` er registrert med godkjennings-/avslagsstatus og tidspunkt. `api/delete_event_registration.php` sletter innsjekk og påmelding med to eksplisitte, bedriftsavgrensede SQL-kall i samme transaksjon; begge journalføres.
- Vipps-statuskallene bruker enkelt-tabell UPDATE med bedriftsavgrensning gjennom arrangementet. `api/capture_vipps_payment.php` låser betalingen, validerer aktør/auditlager og forbereder status-UPDATE før ekstern belastning. Registrert intent og lokal betalingsstatus lagres sammen; endelig audit etter vellykket belastning kan ikke rulle status tilbake til pending. En stabil nøkkel for faktisk bedrift/innsjekk/ordre hindrer at et uavklart nytt forsøk belaster samme betaling på nytt.
- `includes/rental_checkin_email.php` registrerer automatisk maloppretting og innsjekk-kølegging som systemhendelser, uten adgangskode eller meldingsbody.
- `includes/EmailService.php` viderefører hash av den faktisk brukte malversjonen til køaudit når den sentrale forbindelsen brukes.
- `api/process_email_queue.php` journalfører SMTP-aksept/feil med systemet som utfører og referanse til registrert initiativ. SMTP-aksept er ikke bekreftet levering til mottaker. Statuslagring og etterfølgende audit er adskilt etter den irreversible eksterne utsendelsen. Loggerfeil kan ikke legge en akseptert melding tilbake i kø.
- `pages/company_audit.php` og `api/get_audit_logs.php` er en separat, skrivebeskyttet oversikt. Aktive kontoer med databasebekreftet rolle `admin`, `owner` eller `super_admin` kan lese sin aktive bedrifts historie. Ansatte med andre roller får ikke denne tilgangen. Bedrifts-ID kommer fra serverøkten, og også detaljoppslag og filterverdier er bedriftsavgrenset.

## Personvern og før-/etterverdier

Bare tillatte operasjonelle felter lagres. Når et øvrig felt endres, sammenlignes midlertidige databasereturnerte hashes og bare feltnavnet registreres som utelatt; verken verdi eller hash lagres i audit. E-postmalens innhold er unntaket: SHA-256 identifiserer malversjonen, men innholdet lagres ikke. Aktørnavn/rolle og bedriftsnavn er snapshots. Historiske identiteter blir ikke rekonstruert fra dagens personregister.

## Tekniske grenser og unntak

- Mekanismen gjelder prosjektets MySQL-forbindelse og deklarerte portalressurser; ingen database byttes ut og ingen produksjonsmigrering er kjørt.
- Vanlige enkelt-tabell INSERT/UPDATE/DELETE og bulk-VALUES er støttet. Join-mutasjoner, INSERT SELECT og mutasjoner med ORDER/LIMIT må ha eksplisitte hooks; de avvises før utføring, ikke gjennomføres uten audit.
- Én snapshotoperasjon er begrenset til 5 000 rader. Større masseoperasjoner må deles i autoriserte, journalførte bolker.
- Databasens egne CASCADE-endringer har ikke egne SQL-kall. Forelderens sletting journalføres, men ikke en separat aktørhendelse for hver implicit slettet underrad.
- Valideringsavslag før et endringskall har ingen generell SQL-hendelse. Utleiebeslutning og innsjekk har egne avslag/no-op-hendelser. Et avslag kan aldri fremstå som en vellykket endring.
- Ved utilgjengelig auditlager rulles databaseendringer tilbake. At audit selv ikke kan lagres varsles eksplisitt i driftsloggen; etter SMTP-aksept beholdes sendt/sending-status og automatisk ny sending forbys.
- Uidentifiserte HTTP-operasjoner får ikke en oppdiktet administrator. CLI-jobber vises som system; databaseverifiserte tenant-/SaaS-kontoer brukes når tilgjengelige. Ingen historisk tilbakefylling, sletting eller endret lagringstid er innført.
- Eksisterende indekser på `(company_id, performed_at)` og `(user_id, module)` er beholdt. Ingen nye indekser er lagt til uten godkjenning.

## Verifisering

```sh
bash tests/rental_unavailable_mysql_isolated_test.sh --audit-all
php tests/smtp_html_transport_test.php
php tests/rental_confirmation_email_test.php
php tests/email_template_integrity_test.php
php tests/rental_checkin_email_test.php
php tests/rental_regression_test.php
```

Den socket-only MySQL-testen verifiserer oppretting/redigering/no-op/sletting i de sentrale modulene, bulk og sammensatte arrangementsprisnøkler, reell ansatt som aktør, avgrensning/roller, private felt, rollback, bevaring av genererte INSERT-ID-er og at auditfeil ruller tilbake domenedata. Beslutnings-API-testen verifiserer faktisk kølegging, navn etter omdøping/sletting, tilgangssperrer og streng datoavgrensning. Den faktiske køprosessoren sender bare til en lokal SMTP-fangstserver og beholder `sent` uten retry når auditlageret fjernes etter SMTP-aksept.

`tests/event_payment_audit_mysql_isolated_test.php` inngår i `--audit-all` og bruker SQL fra de faktiske opprettings-/godkjennings-/avslagsendepunktene. Disponibel HTTP-server kjører de faktiske Vipps-kontrollerne med bare leverandørtransporten stubbet, og den faktiske påmeldingsslettingen. Testen verifiserer statusoppdatering, ekstern capture etterfulgt av lokal lagring, manglende auditlager før capture, final-auditfeil etter capture, ingen ny capture for allerede fanget/fremmed betaling, begge slettelogger og at joined DELETE ikke kan omgå mekanismen.

Ingen test skriver til appens konfigurerte database eller sender ekte e-post. Innlogget UI er ikke visuelt kontrollert med en ekte brukerkonto; API-tilgangen og frontendens PHP-/JavaScript-syntaks er kontrollert.