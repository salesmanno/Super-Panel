<?php

/**
 * Decide how paid_at changes for an explicit payment-status update.
 *
 * @return string One of set_now, clear, keep.
 */
function rentalPaidAtTransition(?string $currentStatus, ?string $requestedStatus): string
{
    if ($requestedStatus === 'paid' && $currentStatus !== 'paid') {
        return 'set_now';
    }
    if ($requestedStatus !== null && $requestedStatus !== 'paid' && $currentStatus === 'paid') {
        return 'clear';
    }
    return 'keep';
}