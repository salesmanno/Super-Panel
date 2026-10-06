---
name: Felles skjemafelt og delt redigering
description: Brukerens valg om felles feltdefinisjoner og separate redigeringer av grunnopplysninger og skjemafelt.
---

Alle moduler som oppretter skjemafelt i dedikerte tabeller skal bruke dynamic_form_fields ved både opprettelse og redigering.

**Why:** Brukeren ønsker å samle feltopprettelsen i én felles løsning fremfor dedikerte felt-tabeller.

**How to apply:** Skill feltdefinisjoner fra faktiske arrangements-/objektdata og innsendte svar. Samlingen må dekke lesing og validering, ikke bare nye INSERT-er.

Arrangementer og utleie skal ha «Grunnopplysninger» og «Skjemafelt» som to egne redigeringer, med forhåndsoppsett tilpasset arrangement eller utleie. Alt skal ligge i sin respektive modul, ingenting utenfor modulene. Data knyttes til det konkrete objektet eller arrangementet.

**Why:** Brukeren opplevde dagens redigering som «wonky» og valgte uttrykkelig denne todelingen.

**How to apply:** Hold de to redigeringene tydelig atskilt inne i eksisterende moduler og unngå at lagring av én del utilsiktet endrer den andre. Ikke lag en separat skjemamodul eller administrasjonsside; felles lagring må være avgrenset til bedrift og konkret objekt eller arrangement.
