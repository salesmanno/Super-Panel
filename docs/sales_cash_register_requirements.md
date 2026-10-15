# Salesman som selvstendig norsk kassasystem

Status: Krav- og arkitekturutkast. Ikke en erklæring om etterlevelse eller en produkterklæring.

Brukeren har valgt å videreutvikle Salesman som eget kassasystem. Eksisterende funksjoner og testede betalinger dokumenterer ikke alene at kassasystemregelverket er oppfylt.

## 1. Avgrensning

- Behold Salesman-grensesnittet, produkter, dynamiske produktfelt og eksisterende betalingsintegrasjoner.
- Gjenbruk `sales_orders`, `sales_orders_items` og `unified_ledger`. Ikke opprett en ny parallell ordre- eller inntektsmodell.
- Skill brukerens vedvarende handlekurv fra et identifisert fysisk/mobilt kassapunkt. En innlogging er ikke et kassapunkt.
- Et kassapunkt tilhører én bokføringspliktig bedrift. Både bruker og kassapunkt må være autorisert for bedriften ved hver handling.
- E-postkvittering er fortsatt en valgfri kanal med eksisterende automail- og malregler. Det må ikke styre om selve salgsdokumentasjonen produseres.
- Ingen nye tabeller, endringer på produksjonsdata eller endring av betalingsflyt er gjennomført som del av dette utkastet.

## 2. Foreslått datagrunnlag – må avklares før innføring

Fire egne tabeller er foreslått. Dette er ikke kjørbar SQL og oppretter ingenting:

| Foreslått tabell | Ansvar | Hvorfor ikke bare bruke eksisterende data |
| --- | --- | --- |
| `sales_registers` | Varig identitet for kassapunkt, bedrift, systemversjon, teknisk oppsett og nummerering. | En administrator eller PHP-økt er ikke en varig kassapunktidentifikasjon. |
| `sales_register_periods` | Åpning, inngående veksel, status og avslutning av oppgjørsperioden. | En datofiltrert ordreliste dokumenterer ikke et avgrenset, avsluttet oppgjør. |
| `sales_register_journal` | Fortløpende, sikret registrering av kassens bruk og sammenhengende kontrollspor. | Regnskapsposter inneholder ikke alle kassahendelser; ordinær auditlogg er heller ikke i seg selv tilstrekkelig. |
| `sales_register_documents` | Frosne salgs-/retur-/kopikvitteringer og rapportgrunnlag med tilhørende nummerering og referanser. | Gjengivelse fra dagens produkter, firmaopplysninger eller e-postmaler kan ikke rekonstruere et uendret originaldokument. |

Endelig SQL må avstemmes mot faktisk MySQL-/MariaDB-versjon, lagringsstruktur og tilgjengelige databaseprivilegier. Brukeren skal få gjennomgå SQL og kjøre den manuelt. Ingen automatisk tabellopprettelse via HTTP.

## 3. Krav til implementeringen

### Journal og dokumentintegritet

- Lag en sporbar kravmatrise mot kassasystemlova, kassasystemforskrifta, relevante bokføringskrav og gjeldende teknisk SAF-T-spesifikasjon.
- Journalfør relevant kontinuerlig bruk: registreringer, korreksjoner, avbrutte salg, prisundersøkelser der funksjonen finnes, pris-/grunndataendringer, kontantbevegelser, skuffåpninger, dokument-/rapportproduksjon og systemendringer.
- Definer entydige startpunkter, nummerering, operatør, kassapunkt, firma og tidspunkt.
- Journal og ferdige dokumenter skal sikres mot endring og sletting. En enkel hashkolonne eller vanlig auditlogg er ikke dokumentasjon på at dette er løst.
- Dokumenter databaseprivilegier, sikkerhetskopiering, gjenoppretting, integritetskontroll, lagringstid og kontrollert eksport.
- Ikke fjern kundens historikk eller journal ved sletting/inaktivering av et produkt, en operatør eller et firma.

### Salg, betaling og nummerering

- Behold ordre-ID og eksisterende betalingsreferanser. Skill ordrenummer fra lovpålagt dokumentnummerering.
- Fastsett nummerserier per dokumenttype og kassapunkt etter regelverket. Ikke bruk tilfeldige UUID-ordrenummer som en ferdig kvitteringsnummerserie.
- Utsted et salgsdokument bare én gang per fullført salg. Nye statuskontroller eller gjentatte callbacks må ikke gi flere salgsdokumenter eller dobbelt inntektsføring.
- Ordre, regnskapsføring, journalregistrering og dokumentutstedelse må koordineres i korte database-transaksjoner.
- Ikke hold database-transaksjon eller PHP-sesjonslås under nettverkskall til Vipps.
- Ikke fremstill en avbrutt eller uavklart betaling som et fullført salg.
- Dersom ekstern betaling er bekreftet, men lokal utstedelse feiler, skal ordren avstemmes med samme referanse. Ikke be kunden betale på nytt.
- Når lovpålagt registrering ikke kan fullføres, skal en aktivert kasseløsning ikke stille falle tilbake til en eldre, ikke-journalført salgsflyt.

### Kvitteringer og rettelser

- Frys nødvendige selgeropplysninger, organisasjonsnummer, eventuell MVA-markering, varetekst, mengde, pris, MVA per sats, betalingsmiddel, dokumentnummer, kassapunkt og tidspunkt.
- Ta med transaksjons-ID ved integrert betalingsløsning etter kravene.
- Avklar originalutlevering, elektronisk kvittering, utskrift, kopimerking og tillatt antall kopier. En ubegrenset «Skriv ut»-knapp er ikke en ferdig løsning.
- Bevar opprinnelig salg ved retur eller korrigering. Utsted et separat, referert dokument; ikke overskriv den gamle kvitteringen.
- Skill leverandørens betalingsrefusjon fra den regnskaps- og dokumentmessige registreringen av returen.
- Hold trenings-/testregistreringer og deres dokumenter adskilt fra ordinære salg og rapporter.

### X, Z og dagsoppgjør

- Definer påkrevde summer og hendelsestellere fra journalførte registreringer, ikke bare dagens muterbare ordrestatus.
- X-rapport viser løpende status siden forrige Z-rapport uten å avslutte perioden.
- Z-rapport har egen fortløpende nummerering og må ikke inkludere registreringer fra tidligere Z-rapporter.
- Avslutning må avvise uavsluttede salg, uavklarte betalinger og andre forhold som regelverket krever avklart. Parkeringsfunksjon må vurderes mot dette før den innføres.
- Registrer inngående veksel, opptalt beholdning, kontantbevegelser og avvik med ansvarlig operatør.
- Bevar ferdig produsert rapport og beregningsgrunnlag. Ikke rekonstruer et gammelt dagsoppgjør fra dagens data.
- Fastsett driftsdato og visning i norsk tid med korrekt sommertid. Dokumenter representasjonen av tid i journal og eksport.

### Maskinvare og drift

- Avklar faste kassapunkter, mobile operatørenheter og kontantbruk før kasseskuff-/skriverintegrasjon velges.
- For faste kassapunkter med sedler/mynt må krav til integrert kasseskuff og blokkering ved åpen skuff håndteres der de gjelder.
- Avklar kvitteringsskriver, forbindelsesprotokoll, faktisk utskriftsflyt og hvordan feil oppdages. Nettleserens utskriftsdialog alene dokumenterer ikke korrekt integrasjon.
- Nettverksfeil må gi en eksplisitt, sikker tilstand. Ikke innfør en uavklart offline-salgsmodus.

### SAF-T og leverandøransvar

- Bruk gjeldende SAF-T Cash Register-spesifikasjon, kodelister, XSD og signaturkrav. Ikke forveksle dette med SAF-T Financial eller en vilkårlig XML-/CSV-eksport.
- Valider representative eksportfiler, inkludert returer, korreksjoner, flere MVA-satser og oppgjørsperioder.
- Avklar ansvarlig leverandør, systemidentifikasjon, modell/type/versjon og systembeskrivelse.
- Dokumenter versjonsendringer og vurder plikten til oppdatert produkterklæring.
- Produkterklæring er leverandørens ansvar og må ikke fremstilles som en forhåndsgodkjenning fra Skatteetaten.

## 4. Arbeidsrekkefølge og beslutningspunkter

1. Avklar kassapunkt-/maskinvaremodell og foreslått datagrunnlag.
2. Utarbeid gjennomgåbar, ikke-destruktiv manuell SQL og integritets-/tilgangsmodell. Verifiser på en isolert database før innføring.
3. Bygg kassapunkt, periodeåpning, journal og dokumentutstedelse sammen med eksisterende ordre-/betalingsflyt.
4. Bygg retur, korreksjoner, kopikvitteringer, rapporter og dagsoppgjør med nødvendig UI og rettigheter.
5. Implementer og valider SAF-T, driftssikring og faktisk maskinvareintegrasjon.
6. Fullfør kravmatrise, dokumentasjon, uavhengig faglig gjennomgang og produkterklæring før løsningen omtales som klar for ordinær regulert kassedrift.

Ingen av delene alene er en ferdig leveranse av et regelverkstilpasset kassasystem. Historiske salg uten nødvendig journal skal ikke tilbakefylles med oppdiktede journalhendelser eller fremstå som om de opprinnelig ble gjennomført i en erklært løsning. Overgangsdato og behandling av historiske data må dokumenteres.

## 5. Primærkilder

Kontrollert 15. oktober 2026. Gjeldende krav må verifiseres på nytt ved implementering og før lansering.

- Kassasystemlova: https://lovdata.no/dokument/NL/lov/2015-06-19-58
- Kassasystemforskrifta: https://lovdata.no/dokument/SF/forskrift/2015-12-18-1616
- Skatteetatens kassasystemside og tekniske dokumentasjonslenker: https://www.skatteetaten.no/nn/bedrift-og-organisasjon/starte-og-drive/rutiner-regnskap-og-kassasystem/kassasystem/
- Skatteetatens veiledning om kontantsalg, inkludert Vipps: https://www.skatteetaten.no/nn/bedrift-og-organisasjon/starte-og-drive/rutiner-regnskap-og-kassasystem/gode-rutiner-for-daglig-drift/inntekter
- Endring om utskrift ved integrert betalingsløsning: https://www.skatteetaten.no/nn/rettskjelder/type/kunngjoringer/utskrift-av-kvittering--endringer-i-kassasystemforskrifta-og-bokforingsforskriften/
