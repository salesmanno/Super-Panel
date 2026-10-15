# Git-eksport: bare Super-Panel

Tillatt mål for prosjektets GitHub-workflows:

```text
https://github.com/salesmanno/Super-Panel.git
```

## Kontroller uten å eksportere

Fra prosjektroten:

```sh
python3 scripts/super_panel_export.py check
```

Dette leser Git-oppsettet. Det utfører ikke staging, commit, nettverksforespørsler,
pull, push eller endringer av origin. Utdata viser aldri autentisert remote-URL.

## Eksportknappene

- **PUSH Super, Company_panel1** kjører `sync-push`: sjekk, staging, commit,
  eksisterende pull/rebase-flyt, ny målsjekk og push til origin/main.
- **One** kjører `reindex-push`: sjekk, eksisterende oppfrisking av indeksen for
  ignore-regler, staging, commit og push til origin/main.
- Valget som pekte mot `tenant_copy-company-panel-2.git` er fjernet.

De to eksportvalgene **endrer og eksporterer kode når brukeren starter dem**.
De er ikke startet som del av sikringen eller testene. En mislykket pull eller
push kan etterlate en lokal commit eller pågående rebase, akkurat som tidligere;
scriptet forsøker ikke en automatisk reset eller historikkomskriving.

`TARGET_REPO` velger ikke lenger mål for disse workflowene. Eksisterende
origin-autentisering beholdes; ingen token kopieres fra miljøet eller skrives til
konfigurasjon av scriptet. Bruk prosjektets sikre nøkkelflyt hvis autentisering
senere må endres, ikke legg en nøkkel i chat eller et kommandolinjeeksempel.

## Hva som stopper eksporten

- Annen branch enn `main`, manglende origin eller feil sporingsmål.
- Feil repository, eier eller host, flere fetch-/push-URL-er.
- Git URL-omskriving, alternativ remote-transport, speil-push eller egne
  push-refspecs.
- URL-spesifikke HTTP-viderekoblinger som ikke uttrykkelig er avslått.
- En annen samtidig eksport gjennom disse knappene.

HTTP-viderekoblinger og rekursiv submodule-push/-fetch er slått av for
Git-kommandoene, slik at et submodule ikke utløser eksport til et annet repository.
Målene sjekkes før
hvert muterende steg. Ukjente mål gjengis ikke i feilmeldinger; Git-utdata som
kan inneholde påloggingsopplysninger skjules. Interne backup- og samarbeidsremotes
endres ikke.

Kontrollen gjelder disse prosjektstyrte workflowene. Den er ikke en
sikkerhetsgrense mot vilkårlige manuelle Git-kommandoer, egendefinerte hooks
eller at noen med skrivetilgang endrer scriptet/oppsettet.

## Historiske funn

Ved undersøkelsen pekte både origin fetch og push mot Super-Panel, og tre
tilgjengelige workflow-logger viste samme mål. Ingen av disse sporene viste
`www.git`. Dette er ikke en garanti for at ingen eldre push har gått et annet
sted; full historikk hos GitHub eller andre prosjektmiljøer er ikke undersøkt.

## Lokal verifisering

```sh
python3 -B -m unittest discover -s tests -p 'test_super_panel_export.py' -v
```

Testene bruker disponibelt lokalt Git-oppsett og simulert transport. Ingen ekte
GitHub-push, pull eller eksport er nødvendig.
