---
name: Felles skjemafelt og delt redigering
description: Brukerens valg om felles feltdefinisjoner og separate redigeringer av grunnopplysninger og skjemafelt.
---

Alle moduler som oppretter skjemafelt i dedikerte tabeller skal bruke dynamic_form_fields ved både opprettelse og redigering.

**Why:** Brukeren ønsker å samle feltopprettelsen i én felles løsning fremfor dedikerte felt-tabeller.

**How to apply:** Skill feltdefinisjoner fra faktiske arrangements-/objektdata og innsendte svar. Samlingen må dekke lesing og validering, ikke bare nye INSERT-er.

Arrangementer og utleie skal ha «Grunnopplysninger» og «Skjemafelt» som to egne redigeringer, med forhåndsoppsett tilpasset arrangement eller utleie.

**Why:** Brukeren opplevde dagens redigering som «wonky» og valgte uttrykkelig denne todelingen.

**How to apply:** Hold de to redigeringene tydelig atskilt og unngå at lagring av én del utilsiktet endrer den andre.
