# Utleie: medlemspris mellom kundesiden og administrasjonen

## Hva som faktisk er koblet inn

Administrasjonens ventende forespørsler og eksplisitte prislagring bruker nå
`includes/rental_membership_pricing.php`. Modulen leser nye medlemsfordeler og
viser både beregnet pris, medlemsavslag og lagret pris. GET endrer ingen rader.
Datoendring, renhold og manuell prisjustering beregner medlemsavslaget før gebyrer.
Godkjenning krever at en avvikende medlemspris først lagres uttrykkelig.
Godkjente/historiske reservasjoner og betalinger blir ikke automatisk korrigert.

Dette endrer **ikke** det separate kundesideprosjektets forespørselsendepunkt.
Heller ikke det offentlige skjemaet i dette prosjektet har fått identitetskontroll
eller automatisk medlemsrabatt. E-post/telefon i et åpent skjema beviser ikke
rett til et medlemskap.

## Innføring på kundesiden

Kopier den nye helperen og dens prisavhengigheter, eller implementer tilsvarende
logikk i kundesideprosjektet. Beregn på serveren før `rental_schedule.rental_fee`
skrives; bruk samme resultat i prisvisningen, bekreftelsen og eventuell betaling.

```php
// $companyId og $object skal være valgt og kontrollert på serveren.
// $verifiedCustomerId må komme fra kundesidens verifiserte identitet.
// IKKE ta customer_id eller medlemsplan direkte fra POST/e-postoppslag.
$ordinary = rentalPeriodPriceCents($object, $startDate, $endDate);
$rules = rentalEligibleMembershipBenefits(
    $db, $companyId, $object['id'], $verifiedCustomerId
);
$quote = rentalMembershipPrice($ordinary, $startDate, $endDate, $rules);
$totalCents = max(0, $quote['member_base_cents'] + $cleaningCents + $manualAdjustmentCents);
// Lagre avtalt pris og relevante prisopplysninger i bestillingens eksisterende
// øyeblikksbilde, ikke i objektets normalpriser.
```

Koblingen er `membership_product_modifications.output_product_id` til
`output_products.id`, der `product_type='rental'` og `product_id` er objektets id.
Alle koblinger må være avgrenset til samme bedrift.

Kun aktive regler og aktive planer brukes. Kunden må ha aktivt medlemskap med
`payment_status` paid/completed, startdato nådd og utløpsdato ikke passert.
Vurderingen gjelder beregningstidspunktet i Europe/Oslo. Null utløpsdato betyr
ingen registrert sluttdato. Flere medlemskap gir den laveste kvalifiserende prisen,
aldri summerte rabatter. En fast medlemspris får ikke øke normalprisen.
`public_display=0` skjuler offentlig markedsføring, ikke selve retten til rabatten.
Ingen fallback til gamle arrangementspristabeller brukes for utleie.

`amount_discount` + `rental_day` trekker beløpet fra per kalenderdag, inkludert
start- og sluttdato. Én helgepakke kan derfor ha to rabattdager. `percent_discount`
beregnes av ordinær leiesum, ikke renhold/depositum. Pris kan ikke bli negativ.
Reglene opphever ikke kildeobjektets separate medlemskrav eller
`memberverification`; kundesiden må håndheve disse ved sin identitetskontroll.

## Verifiser før publisering

- Aktivt medlem: ukedager med egne satser og helgepakke gir rett medlemspris.
- Ikke-medlem, utløpt eller ubetalt medlemskap: ingen medlemsrabatt.
- Annen bedrift, annen plan eller annet objekt: ingen uvedkommende rabatt.
- Flere gyldige planer: laveste pris, ikke rabattstabling.
- Renhold/depositum gis ikke automatisk medlemsavslag.
- Datoendring bruker de nye dagene og samme beregningsregler.
- Avtalt historisk pris endres ikke ved senere endring av fordelsreglene.
