# Sales: kasseintegrasjon og overgang

## Hva som nå er koblet inn

Dette er applikasjonsintegrasjonen av de fire manuelt innførte tabellene,
ikke et nytt SQL-installasjonskrav.

| Tabell | Faktisk bruk i Sales |
| --- | --- |
| `sales_registers` | Opprette utkast, uttrykkelig aktivere, velge kassapunkt; varig identitet og fortløpende kontrollhoder. |
| `sales_register_periods` | Åpning med veksel, norsk driftsdato, åpen periode, fysisk opptelling, avvik og Z-avslutning. |
| `sales_register_journal` | Oppsett/aktivering/åpning, tildelte kurvmutasjoner, reelle produkt-/lagerendringer, klargjorte salg, avbrutt betaling, kontantbevegelser, dokumenter og utskriftsforsøk. |
| `sales_register_documents` | Frosne salgskvitteringer, full kontantretur, merket kopi og X-/Z-rapporter. |

Kassapunktet velges i **Kassepunkt** inne i Sales. Opprett utkast, aktiver
uttrykkelig og åpne periode før betaling. Identiteten er adskilt fra brukerens
kurv og PHP-innlogging. Nye kurver viderefører valgt kassapunkt for samme
administrator og bedrift. Ingen klient kan velge en annen bedrifts kassapunkt.

Alle registeroperasjoner skjer innen eksisterende Sales-bedriftslås, deretter
kassapunkt og periode. Ingen Vipps-nettverkskall skjer under databasetransaksjon.
Triggerne tildeler nummereringen; tjenesten leser faktisk lagrede verdier tilbake.
Journalens eksakte UTF-8-tekst, SRJ1-kjede og kontrollhoder verifiseres.

### Salg og betaling

- Kontantsalg skriver ordre, varelinjer, lager, ledger, journal og salgskvittering
  atomisk. Mottatt seddelbeløp og veksel lagres separat fra netto innbetaling.
- Vipps får samme kassapunkt-/periodebinding før første leverandørkall.
  Selger, varer, priser og MVA fryses ved klargjøringen. Bekreftet capture
  utsteder ett dokument gjennom samme avstemmingsvei for klient, callback og jobb.
- Lokal dokumentfeil etter ekstern capture gir uavklart lokal ordre, ikke nytt
  betalingsforsøk. Avstemming beholder original referanse og kan fullføres når
  lagringen virker igjen. Lageret frigjøres ikke som om betalingen var avbrutt.
- Det finnes ingen stille tilbakefall til uregistrert nytt salg når oppsett,
  journal eller dokumentlagring feiler.
- Eldre ordre/ventende betalinger uten opprinnelig binding avstemmes videre uten
  å etterkonstruere kassehistorikk. De vises som historiske, ikke som opprinnelige
  journalførte salg.

### Dokumenter, retur og e-post

- Salgskvitteringen er et lagret snapshot. Endring av firma, produkt eller MVA
  endrer ikke originalen. Selgerfelter fra tilgjengelig firmastruktur fryses;
  fullstendige virksomhets-/organisasjonsopplysninger må være konfigurert.
- On-screen visning er ikke ny dokumentutstedelse. Utskrift går først gjennom
  en serverregistrert intensjon. Ett originalforsøk og én merket kopi håndheves.
  Nettleseren kan ikke bekrefte at en fysisk skriver faktisk skrev ut.
- Full kontantretur har separat negativt dokument, begrunnelse, lagerføring og
  reverserende ledgerpost, uten endring av originaldokumentet. Samme retur kan
  ikke utføres to ganger. Delretur er ikke implementert.
- Vipps-refusjon utføres ikke av kontantreturknappen. Ingen reelle leverandør-
  refusjoner startes automatisk.
- E-post krever fortsatt `automail_sales_order=1` og aktiv `sales_receipt`.
  Dokumentutstedelse er uavhengig av automail. E-postens vare-/selgergrunnlag
  bruker det frosne dokumentet der dette finnes.

### Oppgjør

X gir et frosset løpende rapportgrunnlag uten fysisk kontantopptelling eller
avslutning. Z krever opptelling og begrunnelse ved avvik. Forventet kontant er
inngående veksel + netto kontantsalg/returer + innskudd − uttak. Vipps telles ikke
som kontant. Rapportene har egne nummer, betalings-/MVA-fordeling, hendelsestellere
og grand totals fra dokumentene.

Z avvises ved uavklart betaling eller ufullført tilknyttet kurv på kassapunktet,
også andre operatørers. En utstedt Z kan ikke gjenåpnes for nye salg. Numre fortsetter
i neste periode. Retry returnerer samme rapport, ikke en ny avslutning.

## Oppdatering på ekstern server

Last opp de oppdaterte Sales-filene og **begge nye hjelpefilene**:

- `includes/SalesRegisterStore.php` (ny)
- `includes/SalesRegisterActions.php` (ny)
- `includes/SalesService.php`
- `includes/SalesCart.php`
- `includes/SalesOrders.php`
- `includes/SalesProducts.php`
- `includes/SalesReceipts.php`
- `api/sales.php`
- `modules/sales_content.php`
- `assets/sales-admin.js`
- `assets/sales-admin.css`

Ingen DDL kjøres av koden. Ikke importer grunnoppsettet på nytt i en database der
det allerede er innført. SQL-kilden er samkjørt med varianten med vanlig nullable
`z_period_id`, satt av dokumenttriggeren, ikke den serveravviste genererte varianten.
Ved feil om manglende struktur/triggere skal faktisk oppsett undersøkes, ikke
tabeller/triggere droppes automatisk.

Integritetskontrollen bruker `SHOW TRIGGERS`. På MySQL kan synlighet av disse
metadataene kreve `TRIGGER`-rettighet, som også gir endringsmuligheter. Kontroller
derfor vertsserverens faktiske rettighetsmodell; dette er ikke løst ved å anta
at en begrenset runtimebruker ser alle triggere. Koden stopper trygt ved manglende
synlighet. En separat kontroll-/attestasjonsordning kan være nødvendig før
produksjonsdrift med strengt adskilte databasebrukere.

## Verifikasjon

Sales-only, disponibel MySQL med reelle triggere og faktisk PHP-kontroller:

```sh
bash tests/sales_register_isolated_test.sh
SALES_REGISTER_ONLY=0 bash tests/sales_register_isolated_test.sh
```

Den første dekker register-/periodeoppsett, kontant/veksel, kopi/utskriftsintensjon,
retur, frosne snapshots, X/Z, sperring av uavklarte salg, tellerintegritet,
bedriftsisolering og lokal dokumentfeil etter bekreftet capture. Den bruker faktisk
Chromium for registeroppsett og kontantsalg på mobil og desktop.

Den andre kjører den eksisterende Sales-regresjonen med journalført fixture:
produkter, skjemafelt, MVA, ledger, vedvarende kurv, samtidighet, betalingsstatus,
timeout og Vipps-klientflyt. Leverandørtransport er isolert/falsk i testene.
Ingen produksjonsdata, reelle Vipps-belastninger eller andre forretningsmodultester
brukes. Målserverens innloggede UI og fysisk utstyr er ikke verifisert her.

## Dette er ikke en full regelverkserklæring

En fungerende integrasjon er ikke en produkterklæring eller dokumentasjon på alle
krav i kassasystemlova/forskrifta. Full SAF-T Cash Register med påkrevd signering,
utstyr/utlevering, ekstern sikring/oppbevaring/restore og komplett kravmatrise
med faglig gjennomgang gjenstår. Rapportgrunnlaget må vurderes mot samtlige
pålagte rapportfelt; tilgjengelige hendelsestellere alene er ikke full
forskriftsmessig dokumentasjon. Se `sales_cash_register_requirements.md`.

Primærkilder kontrollert ved integrasjonen:

- https://lovdata.no/dokument/SF/forskrift/2015-12-18-1616
- https://www.skatteetaten.no/en/business-and-organisation/start-and-run/best-practices-accounting-and-cash-register-systems/cash-register-systems/faq-concerning-new-cash-register-systems
