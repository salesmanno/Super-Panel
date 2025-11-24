<?php
/**
 * Event Results Service
 * Manages event summary statistics and economic tracking in events_results table
 * 
 * Tracks:
 * - Revenue by payment method (cash, card, vipps, invoice)
 * - Additional revenue (merchandise, donations)
 * - Expenses by type (staff, venue, equipment, materials, other)
 * - Attendance metrics (no_shows, attendance_rate, average_age)
 */

class EventResultsService {
    private $db;
    private $companyId;
    
    public function __construct($db, $companyId) {
        $this->db = $db;
        $this->companyId = $companyId;
    }
    
    /**
     * Add ticket revenue to events_results
     * 
     * @param string $eventId
     * @param string $paymentMethod (cash, card, vipps, invoice)
     * @param decimal $amount
     * @param string $ticketType (regular, member, staff, etc)
     * @return bool
     */
    public function addTicketRevenue($eventId, $paymentMethod, $amount, $ticketType = 'regular') {
        try {
            // Normalize payment method to column name
            $columnMap = [
                'cash' => 'revenue_tickets_cash',
                'card' => 'revenue_tickets_card',
                'vipps' => 'revenue_tickets_vipps',
                'invoice' => 'revenue_tickets_invoice'
            ];
            
            $column = $columnMap[$paymentMethod] ?? 'revenue_tickets_cash';
            
            // Update events_results, create if not exists
            $stmt = $this->db->prepare("
                INSERT INTO events_results (event_id, company_id, date_start, {$column})
                SELECT ?, ?, start_date, ?
                FROM events_eventdata
                WHERE id = ? AND company_id = ?
                ON DUPLICATE KEY UPDATE 
                    {$column} = {$column} + ?,
                    total_ticket_sales = total_ticket_sales + ?,
                    total_revenue = total_revenue + ?
            ");
            
            return $stmt->execute([
                $eventId,
                $this->companyId,
                $amount,
                $eventId,
                $this->companyId,
                $amount,  // for column update
                $amount,  // for total_ticket_sales
                $amount   // for total_revenue
            ]);
        } catch (Exception $e) {
            error_log("EventResultsService::addTicketRevenue error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add other revenue (merchandise, donations)
     * 
     * @param string $eventId
     * @param string $revenueType (merchandise, donations)
     * @param decimal $amount
     * @return bool
     */
    public function addOtherRevenue($eventId, $revenueType, $amount) {
        try {
            $columnMap = [
                'merchandise' => 'revenue_merchandise',
                'donations' => 'revenue_donations'
            ];
            
            $column = $columnMap[$revenueType] ?? 'revenue_merchandise';
            
            $stmt = $this->db->prepare("
                UPDATE events_results
                SET {$column} = {$column} + ?,
                    total_revenue = total_revenue + ?
                WHERE event_id = ? AND company_id = ?
            ");
            
            return $stmt->execute([$amount, $amount, $eventId, $this->companyId]);
        } catch (Exception $e) {
            error_log("EventResultsService::addOtherRevenue error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add expense to events_results
     * 
     * @param string $eventId
     * @param string $expenseType (staff, venue, equipment, materials, other)
     * @param decimal $amount
     * @param string $description Optional
     * @return bool
     */
    public function addExpense($eventId, $expenseType, $amount, $description = null) {
        try {
            $columnMap = [
                'staff' => 'expense_staff',
                'venue' => 'expense_venue',
                'equipment' => 'expense_equipment',
                'materials' => 'expense_materials',
                'other' => 'expense_other'
            ];
            
            $column = $columnMap[$expenseType] ?? 'expense_other';
            
            $stmt = $this->db->prepare("
                UPDATE events_results
                SET {$column} = {$column} + ?,
                    total_costs = total_costs + ?,
                    profit_loss = total_revenue - (total_costs + ?)
                WHERE event_id = ? AND company_id = ?
            ");
            
            return $stmt->execute([$amount, $amount, $amount, $eventId, $this->companyId]);
        } catch (Exception $e) {
            error_log("EventResultsService::addExpense error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update attendance statistics
     * Automatically recalculates: no_shows, attendance_rate, average_age
     * 
     * @param string $eventId
     * @return bool
     */
    public function updateAttendanceStats($eventId) {
        try {
            // Calculate attendance metrics from events_checkin
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_registered,
                    SUM(CASE WHEN checked_in = 1 THEN 1 ELSE 0 END) as total_checkins,
                    SUM(CASE WHEN checked_in = 0 THEN 1 ELSE 0 END) as no_shows,
                    ROUND(SUM(CASE WHEN checked_in = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as attendance_rate,
                    IFNULL(ROUND(AVG(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(attendee_name, ' ', -1), ' ', 1) AS UNSIGNED))), 0) as average_age
                FROM events_checkin
                WHERE event_id = ? AND company_id = ?
            ");
            $stmt->execute([$eventId, $this->companyId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stats && $stats['total_registered'] > 0) {
                $updateStmt = $this->db->prepare("
                    UPDATE events_results
                    SET 
                        total_checkins = ?,
                        no_shows = ?,
                        attendance_rate = ?,
                        average_age = ?
                    WHERE event_id = ? AND company_id = ?
                ");
                
                return $updateStmt->execute([
                    $stats['total_checkins'] ?? 0,
                    $stats['no_shows'] ?? 0,
                    $stats['attendance_rate'] ?? 0,
                    $stats['average_age'] ?? 0,
                    $eventId,
                    $this->companyId
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("EventResultsService::updateAttendanceStats error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update event status
     * 
     * @param string $eventId
     * @param string $status (planning, confirmed, in_progress, completed, cancelled)
     * @param string $notes Optional
     * @return bool
     */
    public function updateStatus($eventId, $status, $notes = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE events_results
                SET status = ?,
                    notes = COALESCE(?, notes)
                WHERE event_id = ? AND company_id = ?
            ");
            
            return $stmt->execute([$status, $notes, $eventId, $this->companyId]);
        } catch (Exception $e) {
            error_log("EventResultsService::updateStatus error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Finalize event results
     * Marks event as completed and sets finalized_at timestamp
     * 
     * @param string $eventId
     * @param array $notes Optional [notes => '', organizer_notes => '']
     * @return bool
     */
    public function finalize($eventId, $notes = []) {
        try {
            $stmt = $this->db->prepare("
                UPDATE events_results
                SET status = 'completed',
                    notes = COALESCE(?, notes),
                    organizer_notes = COALESCE(?, organizer_notes),
                    finalized_at = NOW()
                WHERE event_id = ? AND company_id = ?
            ");
            
            return $stmt->execute([
                $notes['notes'] ?? null,
                $notes['organizer_notes'] ?? null,
                $eventId,
                $this->companyId
            ]);
        } catch (Exception $e) {
            error_log("EventResultsService::finalize error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get event summary
     * 
     * @param string $eventId
     * @return array|null
     */
    public function getSummary($eventId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM events_results
                WHERE event_id = ? AND company_id = ?
            ");
            $stmt->execute([$eventId, $this->companyId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("EventResultsService::getSummary error: " . $e->getMessage());
            return null;
        }
    }
}
?>
