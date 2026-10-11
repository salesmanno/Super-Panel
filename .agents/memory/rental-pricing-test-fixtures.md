---
name: Forutsetninger for utleiepristester
description: Hvorfor offentlige utleiepristester må konfigurere kontaktfeltene eksplisitt.
---

En ende-til-ende-test av offentlig utleieprising må sette opp kontaktfeltene som innsendingen bruker. Ikke anta at et nyopprettet testobjekt tillater alle kontaktopplysninger.

**Why:** En pristest ble avvist av skjemavalideringen før prisberegningen fordi innsendingen inneholdt et skjult kontaktfelt. Feilen fremstod først som en feil i den nye prisflyten.

**How to apply:** La testoppsettet aktivere de kontaktfeltene som sendes inn, og behold den virkelige feltvalideringen. Ved uventet avslag, kontroller hvilken forutsetning som feilet før prisene; ikke omgå sikkerhetskontrollene for å få pristesten gjennom.
