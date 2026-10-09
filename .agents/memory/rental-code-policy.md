---
name: Reservasjonskode og neste utleie
description: Brukerens regler for tildeling, fysisk kodebytte og cron-utsending.
---

Koden skal tildeles automatisk ved godkjenning og for kommende utleier som mangler gyldig kode, med samme antall sifre som lokalets kode har tegn. Detaljvinduet skal ikke generere eller tildele kode. Spørsmål om fysisk kodebytte gjelder bare neste kommende utleie per objekt, ikke senere utleier. Ikke spør dersom startdato er i dag eller eldre. Bekreftelse på fysisk bytte etterspørres ved hver åpning inntil reservasjonens kode er lik lokalets kode.

**Why:** Brukeren endret ønsket fra manuell tildeling til automatisk forhåndstildeling, men ønsker fortsatt å bekrefte fysisk kodebytte for neste leietaker.

**How to apply:** Skill mellom reservasjonens tildelte kode og bekreftet fysisk kode på objektet. Behold gyldige tildelte koder. Vis tildelt kode på kort og i detaljer; oppdater lokalets kode til denne først ved uttrykkelig bekreftelse på fysisk bytte. Ukjent kodelengde må forklares, ikke gjettes. Ikke erstatt koder som allerede er sendt eller lagt i e-postkø uten en egen avklart korrigeringsflyt.

Utleie-cron skal bruke SAMME e-postmal og reservasjonens tildelte kode for gjeldende utleie. Cron skal ikke flytte lokalets stående kode over til reservasjonen.

**Why:** Brukeren presiserte at koden settes i forkant, og at eksisterende mal og kodeutsending skal beholdes.

**How to apply:** Endre kildedata og fjern automatisk kodekopiering, ikke malens tekst eller tag-navn. Manglende tildeling skal ikke maskeres med lokalets kode.
