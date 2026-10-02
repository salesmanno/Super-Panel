---
name: Nullstilling av nettleserskjema
description: Nettlesersemantikk som gjør vanlige form.reset-tester med simulert DOM utilstrekkelige.
---

Når et tidsfelt konverteres til et skjult felt bak synlige time- og minuttvelgere, kan senere oppdatering av feltets value også oppdatere standardverdien. En vanlig form.reset kan derfor beholde forrige klokkeslett. Tøm både verdi og standardverdi eksplisitt ved full nullstilling før de synlige kontrollene synkroniseres.

**Why:** En simulert DOM-test godkjente nullstilling som faktisk beholdt forrige klokkeslett i Chromium. Dette er en nettlesersemantikk som en enkel testdobbel ikke gjenskaper.

**How to apply:** Ved egendefinerte skjemakontroller må gjentatte nullstillinger, valg etter nullstilling og feil som skal bevare verdier også testes i en ekte nettleser. Nullstill avledede oppsummeringer selv når nye feltverdier er tomme.

## Produktkrav for arrangementopprettelse

Gjentatte trykk skal ikke opprette duplikater. Når ny arrangementopprettelse initieres, skal arrangementsopprettelsesskjemaet renskes og nullstilles.

**Why:** Brukeren ba uttrykkelig om begge disse oppførslene.

**How to apply:** Start av ny opprettelse skal gi et tomt skjema på første steg. Ikke erstatt eller nullstill utkastet til en lagring som fremdeles pågår; det er ikke starten på en ny opprettelse.