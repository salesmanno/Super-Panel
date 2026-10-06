<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/rental_request.php';

if (empty($_SESSION['rental_request_token'])) {
    $_SESSION['rental_request_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['rental_request_token'];
$objectId = (string) ($_GET['object_id'] ?? '');
$object = null;
$settings = [];

try {
    if ($objectId !== '') {
        $db = getDBConnection();
        $stmt = $db->prepare(
            'SELECT id, company_id, object_name, description, rental_price
             FROM rental_objects
             WHERE id = ? AND ' . rentalPublicEligibilitySql()
        );
        $stmt->execute([$objectId]);
        $object = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($object) {
            $settings = getRentalCustomerFieldSettings($db, $object['company_id'], $objectId);
        }
    }
} catch (Throwable $e) {
    error_log('rental_request page error: ' . $e->getMessage());
    $object = null;
}

if (!$object) {
    http_response_code(404);
}

function rentalRequestHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $object ? rentalRequestHtml($object['object_name']) : 'Utleieforespørsel'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-4" style="max-width: 760px;">
    <?php if (!$object): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4">Utleieobjektet er ikke tilgjengelig</h1>
                <p class="text-muted mb-0">Lenken er ugyldig eller objektet er ikke aktivt.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h3"><?php echo rentalRequestHtml($object['object_name']); ?></h1>
                <?php if (!empty($object['description'])): ?>
                    <p class="text-muted"><?php echo nl2br(rentalRequestHtml($object['description'])); ?></p>
                <?php endif; ?>
                <p class="small text-muted">Send en forespørsel. Utleien er ikke bekreftet før den er godkjent.</p>

                <div id="requestMessage" class="alert d-none" role="alert"></div>
                <form id="rentalRequestForm"
                      method="post"
                      action="../api/create_rental_request.php"
                      novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo rentalRequestHtml($csrfToken); ?>">
                    <input type="hidden" name="object_id" value="<?php echo rentalRequestHtml($object['id']); ?>">
                    <div class="position-absolute" style="left:-10000px" aria-hidden="true">
                        <label for="website">Nettsted</label>
                        <input id="website" name="_website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <h2 class="h5 mt-3">Kundeopplysninger</h2>
                    <?php foreach ($settings as $setting): ?>
                        <?php echo renderRentalRequestField($setting); ?>
                    <?php endforeach; ?>

                    <h2 class="h5 mt-4">Leieperiode</h2>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="start_date">Fra dato <span class="text-danger">*</span></label>
                            <input class="form-control" id="start_date" name="start_date" type="date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="end_date">Til dato <span class="text-danger">*</span></label>
                            <input class="form-control" id="end_date" name="end_date" type="date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hentetid</label>
                            <div class="form-control bg-light" aria-label="Fast hentetid">08:00</div>
                            <input id="start_time" name="start_time" type="hidden" value="08:00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="end_time">Sluttid <span class="text-danger">*</span></label>
                            <input class="form-control" id="end_time" name="end_time" type="time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="logistics">Logistikk</label>
                        <select class="form-select" id="logistics" name="logistics">
                            <option value="pick_up">Henting</option>
                            <option value="delivery">Levering</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notes">Notater</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" maxlength="5000"></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Send forespørsel</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>
<?php if ($object): ?>
<script>
let rentalSubmissionId = crypto.randomUUID();
let rentalSubmissionBusy = false;
document.getElementById('rentalRequestForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    if (rentalSubmissionBusy) return;
    const form = event.currentTarget;
    const message = document.getElementById('requestMessage');
    const fields = {};
    form.querySelectorAll('[name^="fields["]').forEach((input) => {
        const field = input.name.slice(7, -1);
        fields[field] = input.type === 'checkbox' ? (input.checked ? '1' : '0') : input.value;
    });
    const payload = {
        submission_id: rentalSubmissionId,
        csrf_token: form.elements.csrf_token.value,
        object_id: form.elements.object_id.value,
        fields,
        start_date: form.elements.start_date.value,
        end_date: form.elements.end_date.value,
        start_time: form.elements.start_time.value,
        end_time: form.elements.end_time.value,
        logistics: form.elements.logistics.value,
        notes: form.elements.notes.value,
        _website: form.elements._website.value
    };
    message.className = 'alert d-none';
    rentalSubmissionBusy = true;
    const submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        message.textContent = result.message || (result.success ? 'Forespørselen er sendt' : 'Noe gikk galt.');
        message.className = 'alert ' + (result.success ? 'alert-success' : 'alert-danger');
        if (result.success) { form.reset(); rentalSubmissionId = crypto.randomUUID(); }
    } catch (error) {
        message.textContent = 'Kunne ikke sende forespørselen. Prøv igjen.';
        message.className = 'alert alert-danger';
    } finally {
        rentalSubmissionBusy = false;
        submit.disabled = false;
    }
});
</script>
<?php endif; ?>
</body>
</html>