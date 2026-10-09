---
name: MySQL-låsesyntaks
description: Produksjonsdatabasens bekreftede kompatibilitetsbegrensning for delte radlåser.
---

Bruk `LOCK IN SHARE MODE`, ikke `FOR SHARE`, for delte radlåser i denne appens MySQL-spørringer.

**Why:** Brukeren testet begge syntaksene manuelt mot produksjonsdatabasen. Serveren avviste `FOR SHARE` med MySQL-feil 1064, mens `LOCK IN SHARE MODE` fungerte. Dette bekrefter syntaksbegrensningen, men ikke en bestemt serverversjon.

**How to apply:** Bevar transaksjonene og låsebeskyttelsen. En lokal server som støtter nyere syntaks er ikke bevis på produksjonskompatibilitet; verifiser produksjonsdialekten når SQL-endringer innføres.
