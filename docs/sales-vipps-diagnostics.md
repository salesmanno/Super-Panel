# Sales: Vipps-feil og kompakte varekort

## Betalingsflyten

Kassen sender `WALLET`, `PUSH_MESSAGE`, `CUSTOMER_PRESENT`, telefonnummer og
beløp i NOK/øre etter at kassereren trykker «Send {betalingsmetodens navn}!».
Telefonnummerinntasting alene sender ingenting. Push krever godkjenning av
salgsstedet hos Vipps; webhook-secret er ikke det som utløser telefonvarslet.
Det legges ikke til `redirectUrl`, nettleserbetaling eller en ny betalingsmotor.
Betalingsnøkler hentes fortsatt fra bedriftens eksisterende
`economy_configuration_data` via valgt `economy_payment_gateways`.

Et vellykket opprettelsessvar eller `AUTHORIZED` er ikke bevis på fullført salg.
Ordren, ledgeren og det frosne kassedokumentet fullføres først etter et
autoritativt oppslag som bekrefter korrekt referanse, NOK og hele capture-beløpet.
Eksisterende 80-sekundersfrist, idempotens og gjenoppretting beholdes.
Uavklart betaling beholder referanse og varereservasjon; det startes ikke
en ny ordre og lageret frigjøres ikke på grunnlag av en feil eller lokal tidsfrist.

### Avbrutt betalingsforsøk beholder handelen

Bekreftet avvisning, kundeavbrytelse, kassereravbrytelse og utløp åpner den
opprinnelige kurven igjen. Varer, antall, merknader og dynamiske svar beholdes.
Bare betalingsforsøket blir avbrutt i historikken; det er ingen betalt handel
og ingen salgskvittering før en ny betaling faktisk lykkes.

Kassen henter først den gjenåpnede kurven og låser deretter opp betalingsvalg
og varekontroller samlet. Hvis denne innlastingen feiler, beholdes låsen og
«Hent handlekurven igjen» prøver bare å lese kurven, aldri å sende betaling.
Kontant og et nytt, eksplisitt sendt Vipps-forsøk bruker den samme kurven.
Det gamle forsøket og journalen slettes ikke. Et gjentatt statusvarsel for
det gamle forsøket skal ikke frigjøre flere varer eller åpne en senere
allerede betalt kurv.

## Diagnostikk

Kassen viser fast, norsk veiledning om feilen skjedde ved tokenhenting,
betalingsopprettelse, statuskontroll, capture eller avbryting. HTTP-status,
test-/produksjonsmiljø og en tilfeldig feilreferanse følger meldingen.

Bare fem tillatte diagnosefelt lagres i eksisterende
`unified_ledger.metadata.sales.payment_diagnostics` og vises på ordre-API-et:
`error_code`, `operation`, `provider_http_status`, `environment`,
`support_reference`. Ingen nye tabeller eller kolonner kreves.
Rå provider-tekst, telefonnumre, nøkler, headers og token lagres ikke som
diagnostikk. Providertekst og dokumenterte ErrorCode-verdier brukes kun til å
velge en fast feilkategori.

Samme feil beholder samme feilreferanse ved polling. En feil fra
betalingsopprettelsen beholdes gjennom etterfølgende mislykkede oppslag til
et gyldig oppslag avklarer betalingsstatusen. Et avvist opprettelsesforsøk
frigjør bare varene når et etterfølgende oppslag bekrefter fravær av betalingen.
Bekreftet capture fjerner feilmeldingen og utsteder ikke nytt kassedokument
ved gjentatt callback eller oppslag.

## Lokal verifisering

`php tests/sales_vipps_unit_test.php` tester transportklassifisering med
syntetiske svar og uten nettverk. `bash tests/sales_register_isolated_test.sh`
tester kassejournal, dokumenter og faktisk nettleser-UI. Med
`SALES_REGISTER_ONLY=0` kjøres også hele Sales-livssyklusen og Vipps-keypad via
den virkelige kontrolleren mot en midlertidig database og en falsk Vipps-transport.
Ingen av disse testene bruker prosjektets database, ekte nøkler eller Vipps.

Når bare nettleserkode eller CSS er endret etter beståtte backendtester, kan
`SALES_REGISTER_ONLY=browser bash tests/sales_register_isolated_test.sh` kjøre
de virkelige kontroller-/nettlesertestene uten å gjenta backend-livssyklusen.

Varekort kontrolleres ved 320, 390, 768 og 1280 px, normalt og i fullskjerm,
med faktiske produkter, bilde, bildefeil, langt navn og stort NOK-beløp.
Bildene er 1:1; dette betyr ikke at katalogen er begrenset til én kolonne.

En virkelig push til telefon og oppgjør på målserveren er **ikke verifisert**
av disse testene og krever en separat godkjent ekstern prøve.

## Offisiell referanse

- https://developer.vippsmobilepay.com/docs/APIs/epayment-api/api-guide/operations/create/
- https://developer.vippsmobilepay.com/docs/APIs/epayment-api/api-guide/errors
- https://developer.vippsmobilepay.com/docs/knowledge-base/errors
