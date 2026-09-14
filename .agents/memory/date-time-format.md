---
name: Dato- og tidsformat
description: Prosjektets grense mellom brukerrettet norsk format og kanonisk databaseformat.
---

Brukerrettede datofelt skal godta og vise `DD.MM.YYYY`, og klokkeslett skal bruke 24-timersformatet `HH:mm` uten AM/PM. Databaseverdier skal fortsatt lagres i eksisterende kanonisk SQL-format etter eksplisitt validering og konvertering.

**Why:** Direkte bruk av databaseformat i skjemaene ga brukerfeil, tvetydig parsing og risiko for dagsforskyvning. Samtidig må SQL-datoer forbli sorterbare og kompatible med eksisterende spørringer.

**How to apply:** Ved nye eller endrede brukerflyter skal norsk format brukes ved UI-grensen, mens API-et normaliserer strengt før lagring. Ikke bruk permissiv `strtotime` eller implicit JavaScript-parsing av brukerens kalenderdato.