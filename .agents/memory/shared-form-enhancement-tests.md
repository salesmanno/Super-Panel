---
name: Skjematester med felles sidekode
description: Hvorfor modulspesifikke datokontroller må testes sammen med felles dashbordkode.
---

Ved egne dato- og tidskontroller må nettlesertester inkludere dashbordets felles skjemaforbedring, ikke bare modulkoden.

**Why:** Isolerte Events-tester bestod uten felles sidekode. Den felles datoforbedringen kunne likevel erstatte modulens native kalender med et tekstfelt og gjøre berøringsområdet ubrukelig. Tester av modulen alene oppdaget ikke samspillet.

**How to apply:** Ved nye eller endrede skjemakontroller, kjør relevant felles sidekode i samme nettleserfixture og kontroller både verdier, validering og at velgerens berøringsområde ikke blir erstattet. En lokal kontroll som eier sin egen velger må ikke forbedres en gang til.