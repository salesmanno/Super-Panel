---
name: Skjematester med felles sidekode
description: Hvorfor modulspesifikke datokontroller må testes sammen med felles dashbordkode.
---

Ved egne dato- og tidskontroller må nettlesertester inkludere dashbordets felles skjemaforbedring, ikke bare modulkoden.

**Why:** Isolerte Events-tester bestod uten felles sidekode. Den felles datoforbedringen kunne likevel erstatte modulens native kalender med et tekstfelt og gjøre berøringsområdet ubrukelig. Tester av modulen alene oppdaget ikke samspillet.

**How to apply:** Ved nye eller endrede skjemakontroller, kjør relevant felles sidekode i samme nettleserfixture og kontroller både verdier, validering og at velgerens berøringsområde ikke blir erstattet. En lokal kontroll som eier sin egen velger må ikke forbedres en gang til.

For beregning og lagring må testen kontrollere at kode leser den kanoniske verdien, ikke bare den norske visningen, etter at felles forbedring har kjørt.

**Why:** Felles forbedring flytter feltets CSS-klasser til et nytt visningsfelt. Et klassebasert oppslag som virker før forbedring kan derfor lese norsk visning i stedet for SQL-dato etterpå.

**How to apply:** Verifiser både prisberegning etter redigering og dato-/tidsverdiene i lagringsdata med den virkelige forbedringen aktiv.

Mål emulert mobilbredde før tester som kaller nettleserens `reportValidity`.

**Why:** Native validering kan fokusere og zoome mobilvisningen; i Chromium endret dette `innerWidth` under testen selv om riktig bredde var satt ved start.

**How to apply:** Bruk startbredden for å bekrefte testens emulering. Kontroller separat eventuell zoom som en brukeropplevelse, ikke som feil i emuleringsoppsettet.