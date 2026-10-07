---
name: Dato- og tidsformat
description: Prosjektets grense mellom brukerrettet norsk format og kanonisk databaseformat.
---

Brukerrettede datofelt skal godta og vise `DD.MM.YYYY`, og klokkeslett skal bruke 24-timersformatet `HH:mm` uten AM/PM. Databaseverdier skal fortsatt lagres i eksisterende kanonisk SQL-format etter eksplisitt validering og konvertering. Regler som avhenger av «i dag» skal bruke norsk kalenderdag (`Europe/Oslo`) på serversiden.

**Why:** Direkte bruk av databaseformat i skjemaene ga brukerfeil, tvetydig parsing og risiko for dagsforskyvning. Samtidig må SQL-datoer forbli sorterbare og kompatible med eksisterende spørringer.

**How to apply:** Ved nye eller endrede brukerflyter skal norsk format brukes ved UI-grensen, mens API-et normaliserer strengt før lagring. Ikke bruk permissiv `strtotime` eller implicit JavaScript-parsing av brukerens kalenderdato. Sammenlign kalenderdeler eksplisitt og la API-et være autoritativt rundt midnatt.

Alle tidsvelgere skal bruke ett felt, ikke separate HH- og MM-valg. Fire tall som `1900` skal vises som `19:00`, med et diskret kolon inne i feltet som brukeren ikke trenger å skrive.

**Why:** Brukeren synes separate time- og minuttvalg er tungvinte og ba om enklere tidsinntasting.

**How to apply:** Bruk samme løsning ved opprettelse og redigering, med numerisk mobiltastatur og fortsatt streng 24-timersvalidering.