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

Datoformat skal testes med den virkelige formattereren, ikke en testdobbel som allerede produserer riktig norsk format. Ta med samspill med andre modulers globale hjelpere.

**Why:** En fixture som hardkoder norsk datoformat kan bestå selv om brukerflaten får feil format fra en annen formatterer. Portalens moduler har delte globale navn, så isolert modulrendering alene beviser ikke at datoformatet er stabilt.

**How to apply:** Last den virkelige datoformattereren i fixture og kontroller ferdig renderte perioder. Test at modulspesifikk visning forblir riktig selv om en annen modul erstatter en generell global formatterer.

Native tastaturaktivering i en headless nettleser må testes i en aktiv og fokusert side med ekte nettleserinndata, ikke bare syntetiske DOM-hendelser.

**Why:** En korrekt native knapp reagerte på musetrykk, men en tastaturtest ga falsk feil da headless-siden ikke hadde riktig fokus/inndata. DOM-fokus alene var ikke tilstrekkelig.

**How to apply:** Skill mellom oppsettsfeil i testnettleseren og feil i brukerflaten. Aktiver siden før native tastetrykk, og kontroller faktisk handling og riktig valgt oppføring etterpå.