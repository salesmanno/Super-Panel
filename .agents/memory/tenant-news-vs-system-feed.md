---
name: Tenant-nyheter og systemfeed
description: Avgrensningen mellom firmaspesifikke nyheter og globale systemmeldinger på innloggingssiden.
---

Tenant-nyheter skal ikke kobles direkte til den globale nyhetsfeeden på innloggingssiden uten at den offentlige flaten først får en entydig tenant-identifikasjon.

**Why:** De to feedene har ulikt datagrunnlag og publikum. En direkte omkobling uten tenant-kontekst kan vise feil firmas innhold eller erstatte globale systemmeldinger.

**How to apply:** Nye offentlige visninger av firmanyheter må bruke en tenant-identifisert rute eller kontekst og de delte aktive- og rangeringsreglene. Den globale systemfeeden beholdes separat.