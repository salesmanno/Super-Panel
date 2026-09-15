<?php

/**
 * Build the public result shape from database aggregates.
 *
 * Kept free of PDO/HTTP concerns so the accounting rules can be regression
 * tested without connecting to the production database.
 */
function calculateRentalResults(
    array $aggregates,
    array $statusDistribution = [],
    array $recentPaidContracts = []
): array {
    $totalRevenue = (float) ($aggregates['total_revenue'] ?? 0);
    $paidContractCount = (int) ($aggregates['paid_contract_count'] ?? 0);
    $totalRentals = (int) ($aggregates['total_rentals'] ?? 0);
    $monthlyRevenue = (float) ($aggregates['monthly_revenue'] ?? 0);

    return [
        'total_revenue' => $totalRevenue,
        'total_rentals' => $totalRentals,
        'monthly_revenue' => $monthlyRevenue,
        'average_per_paid_rental' => $paidContractCount > 0
            ? $totalRevenue / $paidContractCount
            : 0.0,
        'paid_contract_count' => $paidContractCount,
        'status_distribution' => $statusDistribution,
        'recent_paid_contracts' => $recentPaidContracts,
    ];
}