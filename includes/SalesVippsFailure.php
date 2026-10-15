<?php
declare(strict_types=1);

/** Only allowlisted diagnostics cross the provider boundary. Never retain raw text. */
final class SalesVippsFailure extends RuntimeException {
    private const MESSAGES=[
        'invalid_subscription_key'=>'Vipps avviste abonnementnøkkelen. Kontroller at den tilhører riktig salgssted og test-/produksjonsmiljø.',
        'missing_subscription_key'=>'Vipps mottok ikke abonnementnøkkelen. Kontroller bedriftens betalingsoppsett.',
        'subscription_not_found'=>'Vipps fant ikke abonnementet. Kontroller salgsstedets ePayment-tilgang og betalingsoppsett.',
        'token_expired'=>'Tilgangstokenet hos Vipps har utløpt. Kontroller bedriftens betalingsoppsett.',
        'invalid_token'=>'Vipps avviste tilgangstokenet. Kontroller nøklene, salgsstedet og test-/produksjonsmiljøet.',
        'unauthorized'=>'Vipps avviste tilgangen. Kontroller nøklene, salgsstedet og test-/produksjonsmiljøet.',
        'ecommerce_not_enabled'=>'Salgsstedet mangler tilgang til ePayment. Kontroller betalingstilgang og eventuell partnertilknytning hos Vipps.',
        'push_not_allowed'=>'Vipps har ikke godkjent salgsstedet for PUSH_MESSAGE. Be Vipps aktivere telefonbaserte betalingsforespørsler for dette salgsstedet.',
        'forbidden'=>'Vipps tillater ikke handlingen for salgsstedet. Kontroller ePayment-tilgang og eventuell partnertilknytning.',
        'reference_conflict'=>'Vipps rapporterte en konflikt for betalingsreferansen. Ikke start en ny betaling; den eksisterende betalingen må avstemmes.',
        'invalid_phone_number'=>'Vipps avviste mobilnummeret. Kontroller nummeret før et nytt betalingsforsøk etter avklaring.',
        'invalid_amount'=>'Vipps avviste betalingsbeløpet. Kontroller beløp og valuta i betalingsoppsettet.',
        'bank_not_verified'=>'Salgsstedets bankkonto er ikke bekreftet hos Vipps. Bedriftens betalingsoppsett må ferdigstilles.',
        'merchant_configuration'=>'Vipps kunne ikke opprette betalingen for salgsstedet. Kontroller betalingsoppsettet.',
        'payment_not_found'=>'Vipps fant ikke betalingsreferansen for det valgte salgsstedet. Status må avklares før et nytt betalingsforsøk.',
        'invalid_request'=>'Vipps avviste forespørselen. Kontroller mobilnummer og betalingsoppsett.',
        'invalid_response'=>'Vipps returnerte et ugyldig svar. Ikke start en ny betaling; status må avklares.',
        'payment_mismatch'=>'Referanse, valuta eller beløp hos Vipps samsvarer ikke med salget. Ikke start en ny betaling; avstemming kreves.',
        'invalid_payment_state'=>'Vipps-beløpet eller betalingsstatusen kunne ikke bekreftes. Ikke start en ny betaling; avstemming kreves.',
        'network_failure'=>'Vipps kunne ikke nås sikkert. Ikke start en ny betaling; status kontrolleres videre.',
        'rate_limited'=>'Vipps mottar for mange forespørsler. Ikke start en ny betaling; status kontrolleres videre.',
        'provider_unavailable'=>'Vipps kunne ikke behandle forespørselen nå. Ikke start en ny betaling; status kontrolleres videre.',
        'reconciliation_required'=>'Salget kunne ikke sluttføres lokalt. Ikke start en ny betaling; status må avstemmes.',
        'provider_rejected'=>'Vipps-svaret kunne ikke avklares. Ikke start en ny betaling; status må kontrolleres.',
    ];
    private const OPERATIONS=[
        'access_token'=>'Henting av tilgangstoken',
        'payment_creation'=>'Opprettelse av betalingsforespørsel',
        'payment_lookup'=>'Kontroll av betalingsstatus',
        'payment_capture'=>'Belastning av godkjent betaling',
        'payment_cancel'=>'Avbryting av betaling',
        'payment_reconciliation'=>'Avstemming av salg',
    ];
    public readonly string $reason;
    public readonly string $operation;
    public readonly int $providerStatus;
    public readonly string $environment;
    public readonly string $supportReference;

    public function __construct(string $reason,string $operation,int $status=0,string $environment='unknown',?string $reference=null) {
        $this->reason=isset(self::MESSAGES[$reason])?$reason:'provider_rejected';
        $this->operation=isset(self::OPERATIONS[$operation])?$operation:'payment_reconciliation';
        $this->providerStatus=$status>=100 && $status<=599?$status:0;
        $this->environment=in_array($environment,['test','production'],true)?$environment:'unknown';
        $this->supportReference=is_string($reference) && preg_match('/^[a-f0-9]{16}$/D',$reference)?$reference:bin2hex(random_bytes(8));
        $where=['test'=>'testmiljø','production'=>'produksjonsmiljø','unknown'=>'ukjent miljø'][$this->environment];
        $http=$this->providerStatus?" (HTTP {$this->providerStatus})":'';
        parent::__construct(self::OPERATIONS[$this->operation].': '.self::MESSAGES[$this->reason].
            " Miljø: $where$http. Salget er ikke bekreftet fullført i kassen. Feilreferanse: {$this->supportReference}.");
    }
    public function diagnostics(): array {
        return ['error_code'=>'SALES_VIPPS_'.strtoupper($this->reason),'operation'=>$this->operation,
            'provider_http_status'=>$this->providerStatus,'environment'=>$this->environment,'support_reference'=>$this->supportReference];
    }
    public function withReference(string $reference): self {
        return new self($this->reason,$this->operation,$this->providerStatus,$this->environment,$reference);
    }
    public static function fromDiagnostics(array $diagnostics): ?self {
        if(!is_string($diagnostics['error_code']??null) || !is_string($diagnostics['operation']??null)
            || !is_int($diagnostics['provider_http_status']??null) || !is_string($diagnostics['environment']??null)
            || !is_string($diagnostics['support_reference']??null))return null;
        $reason=strtolower(substr($diagnostics['error_code'],strlen('SALES_VIPPS_')));
        if(!isset(self::MESSAGES[$reason]) || !isset(self::OPERATIONS[$diagnostics['operation']]))return null;
        $failure=new self($reason,$diagnostics['operation'],$diagnostics['provider_http_status'],$diagnostics['environment'],$diagnostics['support_reference']);
        // MySQL JSON storage normalizes object-key order.
        $restored=$failure->diagnostics();ksort($restored);ksort($diagnostics);
        return $restored===$diagnostics?$failure:null;
    }
    public static function fromResponse(array $response,string $operation,string $environment='unknown'): self {
        $status=is_int($response['code']??null)?$response['code']:0;
        $reason=self::classify($response,$status);
        return new self($reason,$operation,$status,$environment);
    }
    private static function classify(array $response,int $status): string {
        if($status===0)return 'network_failure';
        if($status>=200 && $status<300)return 'invalid_response';
        $data=is_array($response['data']??null)?$response['data']:[];
        $texts=[];
        foreach(['message','title','detail','error','error_description'] as $field)
            if(is_string($data[$field]??null))$texts[]=strtolower(substr($data[$field],0,4096));
        $text=implode(' ',$texts);
        if($status===401) {
            foreach(['invalid subscription key'=>'invalid_subscription_key','missing subscription key'=>'missing_subscription_key',
                'subscription not found'=>'subscription_not_found','token has expired'=>'token_expired','token is expired'=>'token_expired',
                'invalid access token'=>'invalid_token','invalid token'=>'invalid_token'] as $phrase=>$reason)
                if(str_contains($text,$phrase))return $reason;
            return 'unauthorized';
        }
        // Vipps documents ErrorCode in extraDetails; titles can change over time.
        if(in_array($status,[400,403,409,422],true)) {
            $codes=['5080'=>'push_not_allowed','4070'=>'invalid_phone_number','4010'=>'reference_conflict',
                '4020'=>'reference_conflict','4150'=>'reference_conflict','5060'=>'bank_not_verified',
                '5070'=>'merchant_configuration','4040'=>'invalid_amount','5090'=>'payment_not_found'];
            foreach(array_slice(is_array($data['extraDetails']??null)?$data['extraDetails']:[],0,50) as $detail) {
                if(!is_array($detail))continue;
                if(($detail['name']??null)==='ErrorCode' && is_scalar($detail['reason']??null) && isset($codes[(string)$detail['reason']]))
                    return $codes[(string)$detail['reason']];
                if(in_array($detail['name']??null,['phoneNumber','customer.phoneNumber'],true))return 'invalid_phone_number';
            }
            if(preg_match('/push[_ ]?message/',$text) && preg_match('/not allowed|not permitted|not approved|forbidden|approval/',$text))return 'push_not_allowed';
        }
        return match(true) {
            $status===403 && str_contains($text,'merchant not allowed for ecommerce payment')=>'ecommerce_not_enabled',
            $status===403=>'forbidden',$status===404=>'payment_not_found',$status===409=>'reference_conflict',
            $status===400 || $status===422=>'invalid_request',$status===429=>'rate_limited',
            $status>=500=>'provider_unavailable',default=>'provider_rejected',
        };
    }
}
