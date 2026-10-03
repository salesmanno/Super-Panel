---
name: Isolerte transporttester med PDO
description: Hvorfor SMTP- og HTTP-fangstservere må være egne prosesser når testen allerede eier en MySQL-forbindelse.
---

Ikke fork en fangstserver fra en prosess som allerede eier en PDO-forbindelse. Start en uavhengig prosess i stedet.

**Why:** En forket SMTP-fixture arvet MySQL-socketen. PDO-opprydding ved barnets avslutning ødela forelderens forbindelse og ga en misvisende databasefeil etter vellykket SMTP-test.

**How to apply:** Bruk egne prosesser for fangst-/HTTP-servere, og la forelderens PDO-forbindelse forbli privat. Avslutt testserverne i oppryddingen uten at de arver levende databasehåndtak.