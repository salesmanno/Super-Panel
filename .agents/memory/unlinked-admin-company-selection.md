---
name: Bedriftsvalg uten ansattkobling
description: Godkjent firmatilgang for administrative kontoer uten kobling til ansatt.
---

Brukere uten ansattkobling skal velge blant ALLE aktive bedrifter ved innlogging, ikke bare eksplisitt tildelte bedrifter. Gammelt company_id fjernes ved godkjent innlogging og valgt company_id lagres på brukeren. Bedriftsnavn og logo skal vises på lasteskjermen.

**Why:** Brukeren valgte fri bevegelse mellom firmaer for å avhjelpe og feilsøke problemstillinger, etter at tilgang på tvers av bedrifter ble forklart.

**How to apply:** Behold bedriftsbindingen for ansattkoblede kontoer. Passord må være verifisert før gammel tilknytning endres, og vanlig tilgang til bedriftsdata åpnes først når en aktiv bedrift er valgt på serveren.

Delvis innlogging må aldri sette det vanlige tenant_admin_id-signalet. Bedriftsbytte må også avvise gamle faners kontekst og vente på allerede påbegynte forespørsler for samme bruker.

**Why:** Eldre API-er godtar tenant_admin_id alene. Bare å nullstille company_id eller skjule dashbordet beskytter derfor ikke valget, og en forespørsel som allerede kjører kan ellers skrive med forrige bedriftskontekst.

**How to apply:** Nye innloggings-, sesjons- og databaseinnganger må bevare disse kontrollene. Test samtidighet med forskjellige HTTP-prosesser og ulike nettlesersesjoner, ikke bare sekvensielle forespørsler.

Modultilgang må testes mot faktiske handlingsendepunkter, ikke bare kunstige ruter med modulnavn.

**Why:** Sluttkontrollen fant at navnebasert rutekontroll overså handlekurv og kasse fordi endepunktene ikke inneholdt ordet «sales». En test av en kunstig, tydelig navngitt modulrute fanget ikke dette.

**How to apply:** Ved nye eller endrede modulrettigheter, kartlegg API-kallene fra modulen og test direkte HTTP-kall uten både brukertildeling og bedriftslisens. Skill delte leseruter fra handlinger som tilhører én modul.
