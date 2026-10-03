---
name: E-postmaler er autoritative
description: Regelen for hvordan lagret maltekst og eksplisitte tags skal behandles ved e-postutsendelse.
---

Lagret emne- og body-mal er autoritativ. Bare eksplisitte `{{tags}}` som faktisk finnes i malen skal erstattes; hardkodert tekst skal sendes uendret.

**Why:** Skjult runtime-omskriving av et bestemt navn og telefonnummer til kontakt-tags gjorde at utsendt «utleie innsjekk»-innhold avvek fra malen administratoren kunne se.

**How to apply:** Nye dynamiske felt skal innføres som synlige tags i standardmalen og dokumenteres i malvisningen. Legacy-innhold kan oppgraderes eksplisitt og idempotent, aldri som en skjult del av kølegging eller sending.

Ugyldig lagret markup skal gi en eksplisitt feil, ikke repareres ved utsending. Transportkoding skal kunne dekodes tilbake til nøyaktig samme ferdig renderte body.

**Why:** Synlig tabellkode hos en mottaker kan skyldes enten malfeil eller transport, selv når kø og utsendelseslogg er korrekte. Skjult dekoding eller omskriving kan maskere årsaken og samtidig gjøre kundetekst til HTML.

**How to apply:** Skill autoritativ malmarkup, dynamiske tekstverdier og MIME-transport. Test hele løypen med en lokalt fanget SMTP-melding, og ikke påstå at faktisk mottakerårsak er bekreftet uten originalmeldingen.