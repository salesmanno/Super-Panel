<div id="<?= htmlspecialchars($economyFilterId, ENT_QUOTES, 'UTF-8') ?>" class="economy-module-filters d-flex flex-wrap align-items-center gap-2" role="group" aria-label="Filtrer transaksjoner og summer etter modul">
    <?php foreach (['membership'=>'Medlemskap','rental'=>'Utleie','events'=>'Arrangementer','sales'=>'Salg','other'=>'Øvrige'] as $source => $label): ?>
    <label class="small d-inline-flex align-items-center gap-1 mb-0">
        <input type="checkbox" class="form-check-input mt-0" value="<?= $source ?>" checked>
        <?= $label ?>
    </label>
    <?php endforeach; ?>
</div>
