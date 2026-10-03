# Synlig tabellkode i utleiebekreftelse

## Hva som er bekreftet

Skjermbildet viser en ellers formattert HTML-melding med én tabellåpning som tekst. Det er derfor ikke bevis på at hele meldingen ble sendt som ren tekst. Avbildet start ser ut som `< table …`, men original MIME-melding er ikke tilgjengelig.

En lesende kontroll mot prosjektets konfigurerte, eksterne MySQL-database fant to nylige utleiebekreftelser fra samme bedrift og samme mal, kølagt med noen minutters mellomrom. Én inneholdt perioden fra skjermbildet. Begge købodyene hadde gyldige tabellåpninger, uten mellomrom etter `<` eller entity-kodet tabellmarkup. Body-hashene i utsendelsesloggen var identiske med de respektive købodyene. Det ble ikke funnet flere aktive bekreftelsesmaler for samme bedrift/nøkkel ved kontrolltidspunktet.

Begge købodyene samsvarer også med den nåværende lagrede malens statiske innhold og markup når eksplisitte dynamiske taggverdier holdes utenfor sammenligningen. Det ble ikke funnet noen forskjell i statisk malinnhold som forklarer skjermbildet. Dette beviser ikke at malen aldri ble redigert mellom utsendelsene; en eventuell midlertidig endring og tilbakeføring kan ikke fastslås fra dagens mal og købodyer alene.

Begge bodyene hadde 310 LF-linjeskift og ingen CRLF. Den lengste HTML-linjen var 271 byte. SMTP-klienten sendte denne HTML-en direkte, uten Content-Transfer-Encoding og uten normalisering av linjeskift. Det er en konkret transportfeil: SMTP bruker CRLF som linjeskille, har en grense på 998 innholdsbyte per linje, og ukodet MIME-innhold er som utgangspunkt 7-bit. Norske UTF-8-tegn og LF-only HTML ble ikke transportert på en definert, robust måte.

Kontrollen leste bare nødvendige melding-/malfelter og sammenlignet dem i minnet. Ingen historiske meldinger, maler eller køoppføringer ble endret, og ingen melding ble sendt på nytt. Dette var en kontroll mot den eksterne databasen appen er konfigurert til å bruke, ikke en sammenligning mellom Replits separate utviklings- og produksjonsdatabaser.

## Retting og avgrensning

- SMTP-kroppen sendes som base64 med 76-tegns ASCII-linjer og CRLF. Dekodet HTML er byte-for-byte identisk med bodyen som ble sendt inn til transporten, også når den opprinnelige HTML-en bruker LF.
- Lange UTF-8-emner og avsendernavn foldes til gyldige MIME encoded words uten å dele UTF-8-tegn.
- Utleiebekreftelsens body-renderer avviser kjente feilmønstre for tabellåpninger med mellomrom etter `<` og entity-kodede presentasjonstabellåpninger. Den retter ikke lagret mal skjult. Feilen returneres til administrator, og godkjennings-/avslagstransaksjonen rulles tilbake uten kølegging.
- Dynamiske body-verdier behandles som tekst-/attributtverdier og HTML-escapes én gang ved erstatningsgrensen. Malmarkup beholdes, ukjente tags beholdes, og betingelser evalueres med de opprinnelige variablene.
- Emner sendes fortsatt som tekst. Automatisk innsjekk, betalingsregler og e-postleverandør er ikke endret.

Transportfeilen er dokumentert og rettet. At den var den konkrete årsaken til endringen i akkurat mottakerens melding, er fortsatt en hypotese: originalmeldingen fra mottakeren og opplysning om klient/mellomliggende servere mangler. Den lokale reproduksjonen bekrefter at ugyldig `< table …` vises som tekst inne i en ellers HTML-renderet melding, men beviser ikke hvem som introduserte mellomrommet.

## Verifikasjon

```sh
php tests/smtp_html_transport_test.php
bash tests/rental_unavailable_mysql_isolated_test.sh --email
php tests/rental_confirmation_email_test.php
php tests/email_template_integrity_test.php
php tests/rental_checkin_email_test.php
php tests/rental_regression_test.php
```

Den isolerte MySQL-testen kjører faktisk beslutnings-API fra en midlertidig kopi, med separate testøkter og en socket-only database. Den kontrollerer vellykket godkjenning til egen adresse, to meldinger fra samme bedrift, en annen bedriftsmal, avslag, faktisk købody, rollback ved ugyldig mal og den SMTP-meldingen som den virkelige klienten sender til en lokal fangstserver. Ingen autentiseringsbypass legges i appen.

Transporttesten kontrollerer CRLF, linjegrenser, lange emner, norske tegn, punktumlinjer, tom body, rå/enkelt-/dobbeltkodet feilmarkup og byte-eksakt MIME-dekoding. Ingen test bruker appens database som fixture eller sender ekte e-post.

Verken faktisk mottakerklient eller originalmeldingen hos mottakeren er verifisert. Den innloggede administratorflaten er ikke visuelt kontrollert; feilmeldingen fra beslutnings-API-et er verifisert.