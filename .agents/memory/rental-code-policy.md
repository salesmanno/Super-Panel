---
name: Reservasjonskode og neste utleie
description: Brukerens regler for tildeling, fysisk kodebytte og cron-utsending.
---

Koden skal tildeles reservasjonen på forhånd. Spørsmål om tildeling eller fysisk kodebytte gjelder bare neste kommende utleie per objekt, ikke senere utleier. Ikke spør dersom startdato er i dag eller eldre. Bekreftelse på fysisk bytte etterspørres ved hver åpning inntil reservasjonens kode er lik lokalets kode.

**Why:** Brukeren ønsker å forberede riktig kode til neste leietaker uten spørsmål på andre leieforhold eller automatisk endring av lokalets fysiske kode.

**How to apply:** Skill mellom reservasjonens tildelte kode og bekreftet fysisk kode på objektet. En tilfeldig kode er bare et forslag før administratoren godtar tildelingen; oppdater lokalets kode først ved uttrykkelig bekreftelse på fysisk bytte.

Utleie-cron skal bruke SAMME e-postmal og reservasjonens tildelte kode for gjeldende utleie. Cron skal ikke flytte lokalets stående kode over til reservasjonen.

**Why:** Brukeren presiserte at koden settes i forkant, og at eksisterende mal og kodeutsending skal beholdes.

**How to apply:** Endre kildedata og fjern automatisk kodekopiering, ikke malens tekst eller tag-navn. Manglende tildeling skal ikke maskeres med lokalets kode.
