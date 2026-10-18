# Betalte inntekter og betalingshistorikk

## Hvilke poster teller?

En hovedbokspost teller i betalte økonomisummer bare når begge vilkår gjelder:

- `status = 'active'`
- `payment_status = 'completed'`

Ventende, autoriserte, avviste, avbrutte, utløpte og ukjente betalinger er ikke inntekt. En historisk post med avsluttet/refundert ledger-status teller heller ikke, selv om betalingsstatus fortsatt er `completed`. Lesere endrer aldri disse statusene.

Økonomis standardliste viser gjennomførte poster. Filteret «Øvrig betalingshistorikk» viser det som ikke er medregnet, og «Alle» viser begge. Listens antall og sider følger filteret. Oversiktskortene fortsetter å vise alle gjennomførte betalinger for firmaet, uavhengig av valgt side og historikkfilter.

## Beløp og korreksjoner

`selling_price` er linjens samlede beløp, ikke stykkpris. Det skal ikke ganges med `quantity` ved summering. Dette samsvarer med dagens Sales-snapshots, medlemsbetalinger, tjenester og arrangementsføring.

- Positive beløp er inntekt med mindre raden uttrykkelig er en utgift.
- Negative beløp er en utbetaling/korreksjon med absolutt beløp som utgift.
- Utgiftsrader kan også ha positiv kostpris og null eller positiv salgspris. Disse følger samme beløpsmodell som eksisterende arrangementsregnskap.
- Sales-retur beholder opprinnelig fullført salg og lager en separat negativ, fullført post. De to postene gir korrekt nettoeffekt; en retur skal ikke også føre til at opprinnelig inntekt fjernes fra rapporten.
- Historiske forsøk beholder sitt opprinnelige beløp, men får ingen betalt inntekt, utgift eller fortjeneste i presentasjonen.

Det er ikke kjørt noen migrering, korrigering eller sletting av eksisterende hovedboksposter.

## Moduloversikter

- Økonomi: fullførte ledger-poster, med eksisterende estimat for Vipps-gebyrer i oversikt/inntektsvisning. Modulfordeling viser brutto før slike gebyrer.
- Medlemsinntekter: fullførte, aktive poster, uten dobbel multiplikasjon av antall.
- Kontingent- og månedsoversikter: betalinger knyttes til firma og stabil kunde-ID, ikke e-postadresse. Forventet kontingent og restanse beholdes som separate størrelser fra betalt beløp.
- Arrangementer: eksisterende ledger-filter og inntekts-/utgiftsmodell beholdes.
- Utleieresultater: eksisterende rapport basert på betalte kontrakter beholdes; den er ikke det samme som en oppstilling over alle Rental-ledger-poster.

Eksisterende Vipps-gebyrberegning er et estimat basert på dagens konfigurasjon, ikke historisk avstemming av leverandørens faktiske oppgjør. Inntekts-/modulvisninger må derfor sammenlignes på samme brutto-/nettogrunnlag.
