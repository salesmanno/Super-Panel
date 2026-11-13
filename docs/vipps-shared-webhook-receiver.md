# Vipps-webhook: felles database og aktivering fra Economy

## Hva er implementert her?

På Vipps-kortet i **Economy → Betalingsløsninger**:

- «Aktiver» kontrollerer riktig firma, miljø, nøkler, mottaker og eksisterende
  abonnement før registrering og aktivering.
- Allerede aktive oppsett har «Fullfør webhook-oppsett», uten at man først må
  deaktivere betalingsmetoden.
- «Oppdater status» leser Vipps og mottakeren; den registrerer ikke abonnementer
  og skriver ikke til databasen.
- «Deaktiver» stopper nye betalingsvalg. Abonnement og signeringshemmelighet
  beholdes for påbegynte betalinger.
- Registrert betyr **ikke** at en virkelig webhook er levert og behandlet.

Ingen virkelig registrering eller betaling er utført under implementeringen.

## Lagring – ingen nye tabeller eller migrering

Den eksisterende kolonnen `economy_configuration_data.config_data` brukes.
Andre JSON-egenskaper i denne kolonnen bevares.

```json
{
  "_salesman_vipps_webhooks_v1": {
    "production": {
      "binding": "serverberegnet fingeravtrykk",
      "attempted_at": "ISO-8601-tidspunkt",
      "id": "Vipps-registrerings-ID",
      "secret": "KUN SERVERLAGRING"
    },
    "test": {
      "binding": "eget fingeravtrykk for test",
      "attempted_at": "ISO-8601-tidspunkt",
      "id": "egen testregistrerings-ID",
      "secret": "KUN SERVERLAGRING"
    }
  }
}
```

`secret` er tekstbytes, ikke base64-dekodet. Innholdet må aldri sendes til
nettleseren, logges, hardkodes eller eksporteres til Git. Databasetilgangen
må være begrenset til serverne som trenger nøklene.

Dette prosjektet lagrer eksisterende Vipps-nøkler som ren tekst selv om
kolonnen heter `client_secret_encrypted`. Denne utvidelsen innfører ikke
en ny krypteringsstandard som det andre prosjektet ikke kan lese.

Den gamle kolonnen `webhook_secret` overskrives **ikke**: den har ingen sikker
miljø-/URL-kobling og kan brukes av en annen eksisterende webhook.
Nye registreringer leses fra JSON-strukturen over, adskilt per miljø.
Begge prosjekter må bevare disse JSON-egenskapene ved annen konfigurasjonslagring.

## Må innføres i prosjektet som eier betalingsmottaket

Mottaket finnes ikke her. Det andre prosjektet må oppdateres før aktivering
kan lykkes. Mottakeradressen beregnes utelukkende fra firmaets serverlagrede
`tenant_companies.subdomain`:

`https://{subdomain}.salesman.no/api/vipps_callback.php`

Ikke bruk nettleserens `company_id`, URL-felt, Host-header eller webhookens
MSN alene til å gi firmatilgang. Mottakerprosjektet må slå opp firma fra sin
eksisterende godkjente hostname-konfigurasjon.

### 1. Del PHP-hjelperne

Kopier tilsvarende implementasjon av disse filene til betalingsprosjektet:

- `includes/VippsWebhookRegistry.php`
- `includes/SalesVipps.php`
- `includes/SalesVippsFailure.php`

Det er også mulig å implementere protokollen separat, men kontrakten for
JSON-lagring, nøkkelvalg, binding og signering må være identisk.

### 2. Les riktig konfigurasjon

Last `economy_configuration_data` gjennom
`economy_payment_gateways.configuration_id`, med `company_id` på **begge**
tabellene. Last `tenant_companies.subdomain` i samme godkjente firmakontekst.
Konfigurasjonsarrayet må inneholde hele raden, `id`, `company_id`, `test_mode`,
`config_data` og `subdomain`.

Ved oppsettskontrollen kan metoden være inaktiv: den er ennå ikke aktivert.
Ved webhook for en eksisterende ordre må konfigurasjonen lastes fra ordren,
ikke fra dagens aktive betalingsvalg. Deaktivering skal ikke stoppe oppfølging
av gamle ordre.

### 3. Støtt den signerte oppsettskontrollen

Legg GET-grenen før mottakets vanlige POST-behandling. `$config` må være lastet
fra godkjent firma; ikke bruk spørrestrengens konfigurasjons-ID uten denne
firmakontrollen.

```php
require_once __DIR__ . '/../includes/VippsWebhookRegistry.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && ($_GET['salesman_webhook_probe'] ?? '') === '1') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    try {
        // $db og $config er lastet fra mottakets godkjente firmakontekst.
        echo json_encode(
            VippsWebhookRegistry::receiverProbe($db, $config, $_GET, $_SERVER),
            JSON_THROW_ON_ERROR
        );
    } catch (Throwable $e) {
        http_response_code(403);
        echo '{"success":false}';
    }
    exit;
}
```

Denne grenen skal bare innføres sammen med punkt 4. Den bekrefter at mottaket
støtter kontrakten. Den gjenleser JSON fra felles database og gir et HMAC-bevis
for den leste registreringen, uten å returnere hemmeligheter.
Aktiveringen kontrollerer mottaket både før registrering og etter lagring av
signeringshemmeligheten. Redirect eller feil respons godtas ikke.

### 4. Bruk delt signeringshemmelighet ved POST

Behold eksisterende ordrefullføring, men erstatt den lokale filbaserte
signeringshemmeligheten med felles lagring:

```php
$body = file_get_contents('php://input', false, null, 0, 65537);
if (strlen($body) > 65536
    || !VippsWebhookRegistry::verifyDelivery(
        $config, $orderCompanyId, $body, $_SERVER
    )) {
    http_response_code(401);
    exit;
}
```

**Før dette** må `$config` være bundet til den eksisterende ordren og dens
opprinnelige firma, MSN og betalingsmiljø. Ikke anta at dagens `test_mode` er
det samme som ordren opprinnelig brukte. Ved endret miljø/nøkkelsett må
eksisterende avstemmingsløp håndtere dette eksplisitt, ikke prøve andre nøkler.

Hjelperen kontrollerer POST, vertsnavn, sti, kroppshash, HMAC, tidsstempel,
MSN, miljø og registreringens konfigurasjonsbinding.

Deretter må eksisterende betalingslogikk fortsatt:

- Hente autoritativ betalingsstatus fra Vipps og kontrollere referanse,
  valuta og beløp mot den eksisterende ordren.
- Beholde `payment_charge=0` som reservasjon og `payment_charge=1` som
  belastning etter godkjenning.
- Kreve bekreftet belastning før status `paid`.
- Gjenbruke eksisterende ordre og idempotensnøkler.
- Tåle gjentatte webhooks og samtidig nettleserretur uten doble belastninger,
  påmeldinger, ledger-poster eller e-poster.
- Returnere HTTP 5xx ved midlertidig fullføringsfeil, ikke 200.

Adminprosjektet kan ikke endre eller verifisere denne eksterne
betalingsbehandlingen. Den er en separat utrullingsforutsetning.

## Når aktiveringen stopper

- Manglende mottakerstøtte: «Krever oppfølging». Ingen registrerings-POST.
- Abonnement finnes uten identisk lagret ID, binding og hemmelighet:
  «Krever oppfølging». Ikke opprett et nytt automatisk.
- Manglende påkrevde hendelser: krever oppfølging, ingen automatisk sletting.
- Timeout etter registreringsforsøk: et vedvarende forsøksmerke hindrer
  blind gjentakelse. «Oppdater status» er trygt.
- Et mislykket/uavklart forsøk må undersøkes server-side før forsøksmerket
  eventuelt fjernes. Ikke slett eksisterende Vipps-abonnement automatisk.

## Utrulling og verifikasjon

1. Innfør mottakerendringene i betalingsprosjektet, uten testbetalinger mot
   virkelige kunder.
2. Eksporter adminendringene.
3. Bruk «Oppdater status», så aktivering i ønsket miljø. Miljøet kommer fra
   `test_mode` i serverlagret oppsett, ikke et fritt nettleserfelt.
4. Verifiser én kontrollert testbetaling og gjentatt levering i testmiljø:
   belastning/påmelding/ledger/e-post skal ikke dupliseres.
5. Registrering i produksjon og eventuelle virkelige betalinger krever
   særskilt godkjenning. Denne implementeringen aktiverer ingenting selv.

Kildedokumentasjon:

- https://developer.vippsmobilepay.com/docs/APIs/webhooks-api/api-guide/
- https://developer.vippsmobilepay.com/docs/APIs/webhooks-api/request-authentication/
