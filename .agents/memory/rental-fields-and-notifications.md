---
name: Relevante utleiefelter og konkrete varsler
description: Brukerens krav til lokaleopprettelse og forståelige hendelsesvarsler.
---

Markedspris er ikke nødvendig ved opprettelse av venue/lokale.

**Why:** Brukeren presiserte at dette feltet ikke trengs for lokaleopprettelse.

**How to apply:** Avgrens opprettelsesfelter etter objekttype; ikke behandle alle generelle objektfelter som nødvendige for lokaler.

Hendelsesvarsler skal beskrive hvem som har gjort hva, eksempelvis at en navngitt kunde har sendt en utleieforespørsel, ikke bare gi beskjed om antall hendelser.

**Why:** Brukeren ønsker konkrete hendelsesbeskjeder fremfor generelle antallsvarsler.

**How to apply:** Knytt varselet til den faktiske hendelsen og ressursen. Vis tilgjengelig kundenavn uten å gjette når navnet mangler.

Varselkvittering beholder firmadelt semantikk inntil individuell lesestatus blir eksplisitt innført.

**Why:** Den eksisterende pollingtransporten kvitterer på bedriftsnivå. Denne utvidelsen skulle bruke dagens databaseskjema, uten migrering eller en ny varselmodul.

**How to apply:** Ikke lov individuell «ulest»-status på toppen av den firmadelte kvitteringen. En eventuell overgang må planlegges som en egen funksjon.

Kontroller også unikhetsindekser i den tilkoblede MySQL-databasen før gjenbruk av pollingtabeller som hendelseslager.

**Why:** Kolonneoversikten alene viste ikke at pollingkilden er unik per bedrift og modul. En testdatabase uten denne begrensningen lot flere hendelser passere, mens den faktiske databasen ville avvise den andre.

**How to apply:** La isolerte databaser gjengi relevante indekser fra faktisk skjema. Skill mellom gjenbrukbare pollingjobber og individuelle hendelser; ikke fjern indeksvern eller migrer databasen uten godkjenning.
