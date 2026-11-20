<?php
$previewAccountName = trim((string)($adminName ?? 'Bruker')) ?: 'Bruker';
$previewInitials = '';
foreach (array_slice(preg_split('/\s+/u', $previewAccountName) ?: [], 0, 2) as $part) {
    $previewInitials .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
}
?>
<details class="pwp-account">
    <summary aria-label="Brukermeny for <?= htmlspecialchars($previewAccountName, ENT_QUOTES, 'UTF-8') ?>">
        <span class="pwp-account-avatar" aria-hidden="true"><?= htmlspecialchars($previewInitials, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="pwp-account-copy"><strong><?= htmlspecialchars($previewAccountName, ENT_QUOTES, 'UTF-8') ?></strong><small>Administrer side</small></span>
        <svg class="pwp-account-chevron" width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m4 6 4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="pwp-account-menu" aria-label="Brukervalg">
        <button type="button" data-pwp-page="about">Innstillinger</button>
        <a class="pwp-header-logout" href="/auth/logout.php"><svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M8 4H4v12h4m4-10 4 4-4 4m-4-4h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>Logg ut</a>
    </div>
</details>
