<?php

$lookupApi = file_get_contents(__DIR__ . '/../api/get_membership_renewals.php');
$membershipUi = file_get_contents(__DIR__ . '/../modules/membership_content.php');
$memberUpdateApi = file_get_contents(__DIR__ . '/../api/update_member_fields.php');
$renewalUpdateApi = file_get_contents(__DIR__ . '/../api/update_renewal_status.php');
$lookupUpdateApi = file_get_contents(__DIR__ . '/../api/update_membership_lookup.php');

$failures = [];

$assertContains = static function (string $needle, string $haystack, string $label) use (&$failures): void {
    if (!str_contains($haystack, $needle)) {
        $failures[] = $label;
    }
};

$assertNotContains = static function (string $needle, string $haystack, string $label) use (&$failures): void {
    if (str_contains($haystack, $needle)) {
        $failures[] = $label;
    }
};

$assertContains(
    'AND mmr.order_id   = ?',
    $lookupApi,
    'Eksisterende eksakt oppslag på fornyelses-ID må beholdes'
);
$assertContains(
    'AND UPPER(mm.member_code) = UPPER(?)',
    $lookupApi,
    'Medlemskode må søkes eksakt uten formatavhengig mønster'
);
$assertContains(
    'WHERE mm.company_id = ?',
    $lookupApi,
    'Medlemskodeoppslag må være avgrenset til innlogget firma'
);
$assertContains(
    'ON mm.id = mmr.membership_member_id AND mm.company_id = mmr.company_id',
    $lookupApi,
    'Fornyelsesoppslaget må kreve at medlem og fornyelse tilhører samme firma'
);
$assertContains(
    'ORDER BY mmr_latest.created_at DESC, mmr_latest.id DESC',
    $lookupApi,
    'Siste fornyelse må velges deterministisk'
);
$assertContains(
    "\$row['has_renewal'] = !empty(\$row['id']);",
    $lookupApi,
    'API-et må skille medlem med og uten fornyelse'
);
$assertNotContains(
    "preg_match('/^MEM",
    $lookupApi,
    'Oppslaget må ikke låses til ett MEM-format'
);

$assertContains(
    "document.getElementById('mb-rl-renewal-status').disabled    = !mbLookupHasRenewal",
    $membershipUi,
    'Fornyelsesstatus må deaktiveres når medlemmet mangler fornyelse'
);
$assertContains(
    'membership_member_id: member_id',
    $membershipUi,
    'Et rent medlemstreff må fortsatt kunne lagre medlemsfeltene'
);
$assertContains(
    "renewal_id: mbLookupHasRenewal ? renewal_id : ''",
    $membershipUi,
    'UI-et må bare sende fornyelses-ID når fornyelsen finnes'
);
$assertContains(
    '/api/update_membership_lookup.php',
    $membershipUi,
    'Oppslagspanelet må bruke det atomiske lagringsendepunktet'
);
$assertNotContains(
    'Promise.all(requests)',
    $membershipUi,
    'Oppslagspanelet må ikke lagre medlem og fornyelse i separate parallelle kall'
);
$assertContains(
    'r.plan_name',
    $membershipUi,
    'Oppslagspanelet må vise medlemsplan'
);
$assertContains(
    'r.member_code',
    $membershipUi,
    'Oppslagspanelet må vise medlemskode'
);

foreach ([$memberUpdateApi, $renewalUpdateApi] as $index => $updateApi) {
    $assertContains(
        'SELECT 1',
        $updateApi,
        ($index === 0 ? 'Medlems' : 'Fornyelses') . 'lagring må skille uendret rad fra manglende rad'
    );
    $assertContains(
        'AND company_id = ?',
        $updateApi,
        ($index === 0 ? 'Medlems' : 'Fornyelses') . 'lagring må være tenant-sikret'
    );
}

$assertContains(
    '$db->beginTransaction();',
    $lookupUpdateApi,
    'Felles lagring må starte en transaksjon'
);
$assertContains(
    '$db->commit();',
    $lookupUpdateApi,
    'Felles lagring må fullføre transaksjonen samlet'
);
$assertContains(
    '$db->rollBack();',
    $lookupUpdateApi,
    'Felles lagring må rulle tilbake ved feil'
);
$assertContains(
    'AND membership_member_id = ?',
    $lookupUpdateApi,
    'Fornyelsen må valideres mot medlemmet før oppdatering'
);
$assertContains(
    'AND company_id = ?',
    $lookupUpdateApi,
    'Felles lagring må være avgrenset til innlogget firma'
);

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "membership member-code lookup tests passed\n";