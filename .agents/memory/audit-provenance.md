---
name: Proveniens ved e-post og administratorendringer
description: Hvorfor audit må bevare registrert identitet og faktisk malversjon, uten å rekonstruere historien fra dagens data.
---

En lagret utsendelse må kunne spores til malversjonen som faktisk ble brukt ved rendering og til den registrerte initiativtakeren. Automatisk utfører og initiativtaker er ulike roller. Ikke erstatt historisk identitet med dagens hovedadministrator eller et oppslag mot et senere omdøpt personregister.

**Why:** Utredningen av synlig tabellkode i utleiebekreftelser kunne sammenligne dagens mal, købody og utsendelsesbody, men kunne ikke bevise eller avkrefte en midlertidig malendring mellom utsendelser. Etterfølgende oppslag mot dagens mal ville gi feil proveniens.

**How to apply:** Frys malidentifikasjon ved rendering, og bruk registrerte korrelasjoner og navnesnapshots i historikk. Manglende historisk initiativ skal forbli ukjent. SMTP-aksept er et irreversibelt sendesteg, ikke bevis på mottakerlevering; etterfølgende lagrings-/auditfeil må aldri utløse automatisk ny sending.

Ved global SQL-instrumentering må støtten kontrolleres mot SQL fra faktiske endringsendepunkter, ikke bare syntetiske modultester. Eksterne betalinger krever at journalberedskap kontrolleres før belastning, og at endelig auditfeil ikke gjør en belastet betaling lokalt ubetalt igjen.

**Why:** Vanlige fixture-INSERT/UPDATE-tester fanget ikke at Vipps-flytene brukte joined UPDATE etter ekstern capture. Et nytt fail-closed-lag kunne dermed avvise den lokale lagringen først etter at pengene var belastet.

**How to apply:** Behold tenant-avgrensning når SQL-former tilpasses, og verifiser faktiske kontrollere med bare leverandørtransporten erstattet i isolerte tester. Test feil både før og etter det irreversible steget. Ikke gjenbruk automatisk database-rollback som kompensasjon for eksterne sideeffekter.