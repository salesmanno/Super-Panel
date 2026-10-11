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

Oppsett av skjemafelt skal være enkelt, særlig for utleie.

**Why:** Brukeren sa at «dynamic_form_fields var veldig tungvindt å sette opp» og ønsket noe enklere, spesielt på rental.

**How to apply:** Gjør vanlige kontaktfelt enkle å velge uten manuell teknisk konfigurering. Avanserte innstillinger må fortsatt være tilgjengelige, men skal ikke dominere vanlig oppsett.

Vis kontaktfeltoversikten uten alle feltredigeringene åpne. Et trykk på et konkret felt skal åpne akkurat den feltlinjen med dagens redigeringsutseende. Vellykket lagring lukker feltredigeringen. Dette gjelder både arrangementer og utleie.

**Why:** Brukeren liker det nye kontaktfeltoppsettet, men vil ikke ha alle dynamiske feltlinjer åpne hele tiden.

**How to apply:** Behold feltoversikten og gjør åpning av ett felt tydelig forskjellig fra å velge om feltet skal være med i skjemaet.

I Utleiedashboard skal trykk på objektnavnet ved bildet rulle objektet til toppen av objektlisten og åpne et ellers sammenfoldet feltoppsett med enkle avkryssinger. Etter lagring skal panelet lukkes og listen gå tilbake til normalen.

**Why:** Brukeren ønsker enkelt og intuitivt feltoppsett direkte i Rental-grensesnittet, ikke enda en separat administrasjonsflate.

**How to apply:** Behold skillet mellom grunnopplysninger og skjemafelt. Ikke legg til et nytt sett subfaner eller en redundant «Nytt utleieobjekt»-knapp ved overskriften; forespørsler og kommende utleie skal åpnes fra dashboardkort i modaler.

Ved samling av feltdefinisjoner må operative oppsett skilles fra eldre, ubrukte oppsett i en felles tabell. Avvik og ugyldige innstillinger skal avklares før overføring, ikke automatisk overskrives eller repareres ved sidevisning.

**Why:** Det fantes allerede felles feltdefinisjoner som ikke var brukt av modulene, med andre feltvalg, rekkefølge og valideringsregler enn de aktive dedikerte oppsettene.

**How to apply:** Lag en skrivefri kontrollrapport og innhent godkjenning før tilkoblet database endres. Behold innsendte svar og gamle definisjoner, og ikke la en gjentatt overføring overskrive senere feltredigering.
