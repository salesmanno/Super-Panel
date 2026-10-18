# Automatisk bildeopplasting

Bildene oppbevares hos **ImgBB**. Prosjektet lagrer bare den direkte HTTPS-adressen i modulens eksisterende bildefelt. Ingen ny tabell, BLOB eller permanent lokal bildekatalog brukes. Dagens bildeadresser fungerer fortsatt.

## Serveroppsett

- Opprett en ImgBB-konto og hent en API-nøkkel fra https://api.imgbb.com/.
- Konfigurer `IMGBB_API_KEY` som en hemmelig miljøvariabel i PHP-serverens kjøremiljø. Den må være tilgjengelig for `getenv()` i PHP, ikke bare i et interaktivt terminalvindu.
- PHP trenger **cURL**, **Fileinfo** og **GD** med JPEG/PNG-støtte. GD WebP-støtte trengs for WebP-inndata. EXIF-støtte anbefales for automatisk orientering av mobilbilder.
- Sett `upload_max_filesize` til minst `10M` og `post_max_size` til minst `12M`. Tilpass også webserverens eventuelle grense for forespørsler. Ha tilstrekkelig PHP-minne for dekoding av bilder på opptil 12 megapiksler, anbefalt minst 256 MB.
- `sys_get_temp_dir()` må være skrivbar for PHP. Bildebehandling bruker midlertidige filer som slettes etter opplastingen, også ved feil. PHP rydder den mottatte multipart-filen etter forespørselen.
- Ved eksport til webhotell må den samme hemmeligheten konfigureres i **webhotellets PHP-miljø**. Ikke legg nøkler i kildekoden, JavaScript eller eksportarkivet. Utgående HTTPS til `api.imgbb.com` må tillates.
- Kontroller tjenestens gjeldende vilkår og begrensninger hos ImgBB. Integrasjonen sender ingen automatisk utløpstid, men prosjektet kan ikke garantere en ekstern leverandørs oppbevaring.

## Bruk og personvern

Velg «Last opp bildefil» i bildefeltet. «eller bruk bildelenke» bytter til det eksisterende URL-feltet i stedet for filopplasting; «eller last opp bildefil» bytter tilbake. Byttet beholder bildelenken og krever ingen konfigurert ImgBB-nøkkel for manuelle lenker. Bildet valideres og re-enkodes for å fjerne metadata, og store bilder skaleres ned. Foto har maksimalt 1600 piksler på lengste side, logo 800 og favicon 256. Transparent PNG bevares. Opplasting av SVG, GIF, HEIC, dokumenter eller andre filtyper avvises; bruk JPEG, PNG eller WebP.

Bildene blir tilgjengelige gjennom en offentlig URL. Ikke last opp private dokumenter eller sensitive bilder. Bare det valgte bildet sendes til ImgBB; ingen kunde-, betalings- eller objektdata sendes som metadata. Tilfeldige filnavn brukes.

Etter opplasting må objektet/skjemaet lagres som vanlig. Hvis skjemaet avbrytes, kan bildet allerede være lastet opp eksternt uten at lenken er lagret i prosjektet. Automatisk sletting hos leverandøren er ikke implementert, siden gamle eller delte bilder ikke må slettes utilsiktet.

## Feilhåndtering

Manglende nøkkel eller PHP-støtte gir en tydelig feil. Systemet faller ikke tilbake til lokal permanent bildelagring. Opplastinger krever gyldig administrator, valgt bedrift, modulrettighet og CSRF-token; firma-/nettsidebilder krever også eksisterende opplåsing av innstillingene. En økt kan forsøke opptil 20 opplastinger per ti minutter.

## Kontroll etter konfigurering

`php tests/image_upload_test.php` tester bildebehandlingen uten eksterne kall.
`node tests/image_upload_browser_test.js` tester felleskontrollen med isolerte nettleserdata.
`node tests/announcement_image_preview_browser_test.js` kontrollerer størrelsesbegrensningene i nyhetsmodalen på mobil og desktop.

En eksplisitt leverandørprøve kan kjøres med:

```sh
php tests/image_upload_live_smoke.php --upload-generated-public-images
```

Denne sender **to genererte, upersonlige testbilder** til den konfigurerte ImgBB-kontoen og leser de offentlige direktelenkene tilbake. Den verifiserer JPEG-dimensjoner, PNG og gjennomsiktighet, men skriver ingenting i databasen. Lokale midlertidige filer fjernes; de to offentlige testbildene blir liggende hos ImgBB. Prøven kjøres ikke automatisk som del av vanlige tester. Den skriver aldri ut API-nøkkel eller slettelenker.

Etter eksport må det eksterne webhotellets PHP-prosess konfigureres separat. En nøkkel lagt inn i Replit følger ikke automatisk med i eksporten.
