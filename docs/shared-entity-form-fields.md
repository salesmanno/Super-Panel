# Skjemafelt i arrangement og utleie

Opprettelse og redigering ligger fortsatt i sine respektive moduler.
«Grunnopplysninger» lagrer arrangementet/utleieobjektet uten å lagre feltoppsettet.
«Skjemafelt» lagrer kun feltdefinisjoner for den aktuelle ressursen.
Forhåndsoppsett er kopier, ikke globale skjemaer med levende koblinger.

Definisjoner lagres i `dynamic_form_fields`, avgrenset av `company_id`,
`entity_type` (`event` eller `rental_object`) og `entity_id`.
Ingen nye skjema-/menymoduler eller databaseskjemaer er nødvendig.
Et usynlig internt `__configured`-felt skiller et bevisst tomt oppsett fra et
oppsett som ennå ikke er konfigurert. Det returneres aldri til feltredigering,
rendering eller innsendingsvalidering.

Eksisterende navn `address_street`, `address_zip`, `address_city` i den felles
tabellen beholdes fysisk. Leieflyten bruker fortsatt de kompatible svarkeyene
`street_address`, `postal_code`, `city`. Historiske innsendte svar flyttes eller
slettes ikke. Medlemskap og kategori-/egenskapstabeller er ikke del av endringen.

## Kontrollert overføring

Kjør først **uten skriveflagg**:

```sh
php scripts/migrate_entity_form_fields.php
```

Rapporten viser antall ressurser/felt, overlapp, ugyldige eksisterende
fellesinnstillinger og et fingeravtrykk. Den viser ikke kundeopplysninger,
standardverdier eller faktiske valideringsmønstre.

Etter eksplisitt godkjenning kan samme datagrunnlag overføres:

```sh
php scripts/migrate_entity_form_fields.php --apply --expected=FINGERPRINT --conflicts=use-legacy
```

- `abort`: standard; ingen overføring ved motstridende oppsett.
- `use-legacy`: behold det tidligere aktive moduloppsettet ved overlapp.
- `keep-shared`: behold allerede gyldige fellesinnstillinger ved overlapp.
  Kan ikke brukes hvis en overlappende eksisterende innstilling er ugyldig.

Ved endret datagrunnlag avbrytes operasjonen før skriving. Overføring og
aktivitetslogg utføres i samme transaksjon. Ressurser som allerede er konfigurert
i den felles tabellen hoppes over, slik at senere feltredigering ikke overskrives.
Gamle definisjonstabeller beholdes; de brukes kun til lesing i overgangsperioden.
Ingen overføring skjer automatisk ved oppstart eller sidevisning.

## Verifisering

```sh
bash tests/rental_unavailable_mysql_isolated_test.sh --forms
```

Dette bruker en separat, midlertidig MySQL-server uten tilgang til appens data.
Testene dekker lagring, tenant-avgrensning, tomme oppsett, forhåndsoppsett,
aliaser, kontrollerte konflikter, idempotens, historiske svar, audit/rollback,
rendering, validering og faktiske GET/POST-endepunkter med CSRF.
