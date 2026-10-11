---
name: Ukedagspriser for utleie
description: Brukerens valg om grunnpris per ukedag og avgrensning mot markedspris.
---

Utleie skal kunne bruke enten flat grunnpris eller egne priser for mandag til fredag, tilsvarende valget mellom felles og individuelle helgesatser. Ukedagsvalget styres separat: på mandag–fredag skal `1` bruke de individuelle dagsprisene, mens `0` bruker grunnprisen. Felles helgepris og separate lørdags-/søndagspriser er gjensidig utelukkende valg; helgeprisene påvirkes ikke av ukedagsvalget. Redigeringsseksjonen skal hete «Grunnpris og helgepris». Markedspris skal ikke ligge i denne seksjonen.

**Why:** Brukeren ba uttrykkelig om egne ukedagspriser «istedenfor å sette en flat grunnpris», senere presiserte at bryteren må velge mellom de individuelle ukedagsprisene og `rental_price`, og bekreftet at de eksisterende, alternative helgeprisberegningene allerede fungerer.

**How to apply:** Behandle dette som et faktisk prisvalg, ikke bare ekstra informasjonsfelt. Samme valg må forstås av opprettelse, redigering, forhåndsberegning og serverens prisberegning.
