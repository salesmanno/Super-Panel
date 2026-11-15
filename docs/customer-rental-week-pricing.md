# Til kundeside-agenten: dagspriser og dagskoblinger for utleie

## Formål og utrulling

Adminprosjektet støtter nå et nytt, eksplisitt prisoppsett: syv enkeltpriser
mandag–søndag og inntil to koblede dagintervaller med samlet pris.
Dette dokumentet er integrasjonskontrakten, ikke en bekreftelse på at
kundesideprosjektet allerede er oppdatert.

1. Kjør den additive, manuelle SQL-en i `sql/rental_week_pricing.sql`.
   Den endrer ingen eksisterende priser, reservasjoner eller betalinger.
2. Oppdater kundesidens serverberegning og forhåndsvisning etter denne kontrakten.
3. Last opp adminendringene, og velg nytt prisoppsett eksplisitt per objekt.
   Ikke aktiver nye ukepriser på offentlige objekter før kundesiden støtter dem.
   Opprettelse i det nye adminskjemaet foreslår ukepriser; eksisterende objekter
   beholder sitt gamle prisvalg.
4. Test samme objekt og periode på begge sider, også med medlemsfordel.

Ingen runtime-kode skal opprette tabeller eller kolonner. Behold de gamle
kolonnene under overgangen. Ikke pris om eksisterende reservasjoner automatisk.

## Kilden er rental_objects, ikke output_products.price

`output_products` brukes fortsatt til katalogkobling. For en utleietjeneste skal
`product_type = 'rental'`, og `product_id` peke på `rental_objects.id` i samme
bedrift. Hent prisoppsettet fra kildeobjektet. En eventuell flat katalogpris,
`rental_price` eller tidligere pris på kortet er IKKE ukeprisens prisgrunnlag.

Kontroller tilgjengelighet, aktiv status og bedrift slik kundesiden ellers gjør.
Ikke eksponer lokalets tilgangskoder når prisdata hentes.

## Velg nøyaktig én prismodell

- `pricing_model = 'legacy'`: behold eksisterende beregning med grunnpris,
  ukedagsbryter, ukedagspriser og de gamle helgealternativene.
- `pricing_model = 'weekly'`: bruk bare `daily_prices` og koblingene i
  `rental_price_groups` for ordinær leiepris. Gamle priskolonner er inaktive.
- På en server som ennå mangler den nye kolonnen, er oppsettet fortsatt legacy.
- En ukjent modell eller et ugyldig weekly-oppsett skal gi en tydelig feil.
  IKKE fall tilbake til grunnpris eller sett totalen til null.

Det er viktig å beholde dagens felles helgeregel for legacy-objekter. Denne
endringen skal ikke i seg selv gi disse objektene en annen pris.

## Dataformat

`rental_objects.daily_prices` er et JSON-objekt med NØYAKTIG disse syv nøklene.
Beløp skrives som desimalstrenger med to desimaler; les også gyldige numeriske
JSON-beløp fra integrasjoner. Null, tom tekst og manglende beløp er ugyldig.
`0.00` er derimot en gyldig gratispris og må aldri trigge fallback.

```json
{
  "monday": "500.00",
  "tuesday": "500.00",
  "wednesday": "600.00",
  "thursday": "600.00",
  "friday": "800.00",
  "saturday": "1800.00",
  "sunday": "1600.00"
}
```

Koblingene leses slik, separat fra objektet:

```sql
SELECT slot, start_day, end_day, price
FROM rental_price_groups
WHERE company_id = :company_id
  AND rental_object_id = :rental_object_id
ORDER BY slot;
```

Bruk parametere fra det serververifiserte objektet og bedriften, ikke et
ubekreftet company_id fra nettleseren. Ikke les koblinger på tvers av bedrifter.

Eksempel på samlet, offentlig prisobjekt:

```json
{
  "pricing_model": "weekly",
  "daily_prices": {
    "monday": "500.00", "tuesday": "500.00", "wednesday": "600.00",
    "thursday": "600.00", "friday": "800.00",
    "saturday": "1800.00", "sunday": "1600.00"
  },
  "price_groups": [
    {"slot": 1, "start_day": 2, "end_day": 3, "price": "900.00"},
    {"slot": 2, "start_day": 6, "end_day": 7, "price": "3000.00"}
  ]
}
```

Ukedager: 1 = mandag, …, 7 = søndag. `slot` er 1 eller 2.
Det kan være null, én eller to koblinger. Ingen dag kan inngå i begge.
Intervallet går fremover inklusive fra- og til-dag: 2→4 dekker tirsdag, onsdag
og torsdag; 7→1 dekker søndag og den følgende mandagen. Fra-dag og til-dag
må ikke være ens, da en enkelt dag allerede har sin egen sats.

I API-respons er `price_groups: []` et gyldig, eksplisitt tomt oppsett.
Manglende `price_groups` for weekly skal ikke tolkes som en tom liste:
det kan bety at kundesiden har glemt å hente koblingene.

## Beregning – samme regel som eksisterende felles helgepris

Datoene er kalenderdager, og både fra- og til-dato telles med, inklusive
returdagen. Bruk kalenderaritmetikk i Europe/Oslo på serveren og UTC-datoer
uten klokkeslett i nettleserberegningen; unngå at sommertid endrer dagtallet.

En kobling belastes ÉN gang per berørt forekomst, også når leien bare berører
en del av intervallet. Den samlede koblingsprisen erstatter dagsprisene
for de berørte dagene. Den er ikke en ekstra rabatt eller et gebyr.
Ikke velg automatisk den billigste av koblingspris og enkeltpriser.

For hver dato D i leieperioden:

1. Finn datoens ISO-ukedag W, mandag=1 … søndag=7.
2. For hver kobling med start S og slutt E:
   - `offset = (W - S + 7) % 7`
   - `length = (E - S + 7) % 7 + 1`
   - Datoen rammer koblingen hvis `offset < length`.
3. Ved treff: finn `anchor_date = D minus offset kalenderdager`.
   Bruk kombinasjonen `(slot, anchor_date)` som nøkkel for forekomsten.
   Legg til koblingsprisen bare første gang denne nøkkelen forekommer.
   IKKE legg til en dagspris for D.
4. Uten treff: legg til enkeltprisen fra `daily_prices` for W.

Bruk forankret startdato, ikke bare ukenummer, slik at en søndag–mandag-kobling
bare belastes én gang på tvers av et ukeskifte. Ved to forskjellige forekomster
skal prisen belastes igjen.

### Kontroleksempler

Med prisobjektet ovenfor:

| Periode | Ordinær leiesum |
|---|---:|
| Mandag 16.11.2026 alene | 500,00 kr |
| Tirsdag 17.11.2026 alene | 900,00 kr |
| Tirsdag–onsdag 17.–18.11.2026 | 900,00 kr |
| Lørdag–søndag 21.–22.11.2026 | 3 000,00 kr |
| Søndag 22.11.2026 alene | 3 000,00 kr |
| Mandag–søndag 16.–22.11.2026 | 5 800,00 kr |
| To hele uker 16.–29.11.2026 | 11 600,00 kr |

**Merk prisen for tirsdag alene og søndag alene.** Dette følger samme
berørt-forekomst-regel som den tidligere felles helgeprisen; det er ikke en
pakkerabatt som krever at alle dager leies.

## Medlemsfordeler, tilvalg og lagring

1. Beregn først ordinær leiepris for datoene.
2. Finn gyldige medlemsfordeler fra `membership_product_modifications` via
   utleieobjektets `output_products`-kobling og kundens serververifiserte,
   aktive/betalte medlemskap.
3. Bruk den beste gyldige fordelen; ikke kombiner flere medlemsplaner.
   Prosentavslag gjelder ordinær leiesum. `amount_discount` med
   `price_basis = 'rental_day'` bruker antall faktiske kalenderdager i leien,
   IKKE antall koblinger eller prislinjer. Pris kan ikke bli negativ.
4. Legg til eventuelt renhold og avtalte prisjusteringer etter eksisterende
   regler. Depositum holdes adskilt og inngår ikke i medlemsrabatten.
5. Serveren beregner prisen på nytt ved innsending. Stol aldri på en total,
   en medlemsplan eller en rabatt sendt fra nettleseren.
6. Lagre den beregnede prisen på den nye forespørselen som før. Senere endringer
   i objektets priser skal ikke endre eksisterende reservasjoner uten en
   eksplisitt ny beregning.

Medlemsrestriksjon og medlemsverifisering er fortsatt kildeobjektets egne
adgangsinnstillinger; en dagskobling endrer ikke disse.

## Referenceimplementering i adminprojektet

- `includes/rental_week_pricing.php`: validering, lesing og ren beregning.
  `rentalWeekQuote()` gir `base_cents`, `rental_days` og `lines`.
- `includes/rental_schedule_editing.php`: `rentalPeriodPriceCents()` velger
  ny eller gammel beregning ud fra `pricing_model`.
- `assets/rental_request_pricing.js`: samme nettleserberegning,
  `RentalRequestPricing.calculateRentalQuote()`. Returnerer blandt andet
  `baseCents`, `priceLines`, `rentalDays` og `totalCents`.
- `tests/rental_week_pricing_test.php` og
  `tests/rental_week_pricing_node_test.js`: eksempler og sammenligning av
  server/nettleser, inklusive årsskifte, sommertid og søndag–mandag.

PHP-filene har prosjektavhengigheter; ikke kopier bare én fil og anta at
alle hjelpefunksjoner automatisk finnes i det andre prosjektet. Gjenbruk
beregningsdelen med valideringen, eller implementer kontrakten og kjør de samme testene.

Alle pengeberegninger gjøres i heltallsøre og validerte beløp med høyst to
desimaler. Støttet enkeltbeløp er 0–99 999 999,99 kr. Ny ukeprisberegning
avviser perioder lengre enn 3660 kalenderdager.
