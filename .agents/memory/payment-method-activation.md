---
name: Aktivering av betalingsmetoder
description: Betalingsmetoder skal være aktiverte valg, ikke faste medlemskapsvalg.
---

Brukerens regel: «betalingsmetoder må aktiveres, ikke ligge statisk – som i medlemskap».

**Why:** Brukeren ønsker aktivering som grunnlag for tilgjengelige betalingsmetoder, også i medlemskap.

**How to apply:** Bruk regelen ved opprettelse, redigering og registrering av nye betalinger i medlemskap og arrangementer. Ikke legg inn faste Vipps- eller Kontant-valg som om de alltid er tilgjengelige.

Aktivering gjelder firmaets betalingsoppsett. Ikke innfør et ekstra personlig Economy-krav for administratorer som velger allerede aktiverte metoder i arrangementer eller medlemskap.

**Why:** Den godkjente avgrensningen beholder den eksisterende modul-/rollemodellen; tilgang til å administrere aktivering er noe annet enn å bruke firmaets aktiverte betalingsmåter.

**How to apply:** Kontroller tilgangen når betalingsoppsettet endres, og valider firmaets aktive metoder ved nye betalingsvalg. Ikke endre historiske betalinger eller kreve at deres metode fortsatt er aktiv for å redigere andre opplysninger.

En innsjekking med betaling som fortsatt avventes er ikke en ny betaling. Gratis medlemsfornyelser skal heller ikke opprette en betalingspost.

**Why:** Aktiveringskontrollen skal hindre ugyldige nye betalinger, ikke sperre legitime handlinger der ingen penger er mottatt. Fornyelse og ubetalt innsjekking har andre krav enn førstegangsopprettelse.

**How to apply:** Test hele flyten med ekte modulmarkup og isolert HTTP: betalt fornyelse velger en aktiv metode, gratis fornyelse trenger ingen, og innsjekking uten betaling beholder ventende status uten betalingsmetode eller inntekt.
