<?php
declare(strict_types=1);

/** Called inside a transaction. Repeated clicks must never switch the page off. */
function activateMemberPage(PDO $db, string $companyId, string $memberId): void
{
    $stmt = $db->prepare('SELECT my_membership_page_active FROM membership_members
        WHERE id = ? AND company_id = ? FOR UPDATE');
    $stmt->execute([$memberId, $companyId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member) {
        throw new OutOfBoundsException('Medlem ikke funnet.');
    }
    if ((int) $member['my_membership_page_active'] === 1) {
        return;
    }
    $stmt = $db->prepare('UPDATE membership_members SET my_membership_page_active = 1
        WHERE id = ? AND company_id = ?');
    $stmt->execute([$memberId, $companyId]);
}
