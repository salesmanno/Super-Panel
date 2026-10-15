<?php
declare(strict_types=1);

// Deliberately outside the HTTP/service API. Never load this from SalesService.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

final class SalesTestReset {
    private const DIRECT = [
        'sales_register_documents', 'sales_register_journal', 'sales_register_periods',
        'sales_orders', 'sales_cart', 'sales_registers', 'sales_products',
    ];
    private const GUARDS = [
        'srd_no_delete' => 'sales_register_documents',
        'srj_no_delete' => 'sales_register_journal',
        'srp_no_delete' => 'sales_register_periods',
    ];
    private const REGISTER_TRIGGERS = [
        'srp_before_insert', 'srp_after_insert', 'srp_before_update', 'srp_no_delete',
        'srj_before_insert', 'srj_after_insert', 'srj_no_update', 'srj_no_delete',
        'srd_before_insert', 'srd_after_insert', 'srd_no_update', 'srd_no_delete',
    ];
    private string $database;

    public function __construct(private PDO $db, private string $company) {
        if (!preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/D', $company)) {
            throw new RuntimeException('Oppgi en gyldig company_id med små bokstaver.');
        }
        $this->database = (string)$db->query('SELECT DATABASE()')->fetchColumn();
        if ($this->database === '' || $db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new RuntimeException('Verktøyet krever en eksplisitt valgt MySQL-/MariaDB-database.');
        }
    }

    private function q(string $sql, array $args = []): PDOStatement {
        $s = $this->db->prepare($sql); $s->execute($args); return $s;
    }
    private function ident(string $value): string { return '`'.str_replace('`', '``', $value).'`'; }
    private function table(string $name): string { return $this->ident($this->database).'.'.$this->ident($name); }
    private static function json(mixed $data): string {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** The SELECTs are also reused for backups; all company values are bound. */
    private function selectors(): array {
        $out = [];
        foreach (self::DIRECT as $table) $out[$table] = 'SELECT * FROM '.$this->table($table).' WHERE company_id=? ORDER BY id';
        $out['sales_orders_items'] = 'SELECT i.* FROM '.$this->table('sales_orders_items').' i JOIN '.
            $this->table('sales_orders').' o ON o.id=i.order_id WHERE o.company_id=? ORDER BY i.id';
        $out['sales_cart_items'] = 'SELECT i.* FROM '.$this->table('sales_cart_items').' i JOIN '.
            $this->table('sales_cart').' c ON c.id=i.cart_id WHERE c.company_id=? ORDER BY i.id';
        $out['unified_ledger'] = 'SELECT * FROM '.$this->table('unified_ledger').
            " WHERE company_id=? AND module_source='sales' ORDER BY id";
        $exists = $this->q('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?',
            [$this->database, 'email_queue'])->fetchColumn();
        if ($exists) $out['email_queue'] = 'SELECT * FROM '.$this->table('email_queue').
            " WHERE company_id=? AND module_source='sales' ORDER BY id";
        return $out;
    }

    public function snapshot(): array {
        $company = $this->q('SELECT id FROM '.$this->table('tenant_companies').' WHERE id=?', [$this->company])->fetchColumn();
        if ($company === false) throw new RuntimeException('Bedriften finnes ikke i valgt database.');
        $selectors = $this->selectors();
        // Unknown Sales tables or external references need explicit review, never CASCADE.
        $tables = $this->q('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=?',
            [$this->database])->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($tables as $name => $engine) {
            if (str_starts_with($name, 'sales_') && !isset($selectors[$name])) {
                throw new RuntimeException("Ukjent Sales-tabell: $name. Manuell gjennomgang kreves.");
            }
            if (isset($selectors[$name]) && strcasecmp((string)$engine, 'InnoDB') !== 0) {
                throw new RuntimeException("Tabellen $name må være InnoDB for sikker tilbakerulling.");
            }
        }
        $refs = $this->q('SELECT TABLE_SCHEMA,TABLE_NAME,REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL', [$this->database])->fetchAll(PDO::FETCH_ASSOC);
        $deleted = array_fill_keys(array_diff(array_keys($selectors), ['sales_products', 'sales_registers']), true);
        foreach ($refs as $ref) {
            if (isset($deleted[$ref['REFERENCED_TABLE_NAME']]) &&
                ($ref['TABLE_SCHEMA'] !== $this->database || !isset($deleted[$ref['TABLE_NAME']]))) {
                throw new RuntimeException('En tabell utenfor sletteomfanget refererer testhistorikken: '.$ref['TABLE_NAME']);
            }
        }
        $triggers = [];
        foreach ($this->db->query('SHOW TRIGGERS FROM '.$this->ident($this->database))->fetchAll(PDO::FETCH_ASSOC) as $t) {
            if (!isset($selectors[$t['Table']])) continue;
            if (!in_array($t['Trigger'], self::REGISTER_TRIGGERS, true)) {
                throw new RuntimeException('Ukjent trigger på berørte tabeller: '.$t['Trigger']);
            }
            $row = $this->db->query('SHOW CREATE TRIGGER '.$this->table($t['Trigger']))->fetch(PDO::FETCH_ASSOC);
            $triggers[$t['Trigger']] = [
                'table' => $t['Table'], 'event' => $t['Event'], 'timing' => $t['Timing'],
                'sql' => $row['SQL Original Statement'], 'sql_mode' => $row['sql_mode'],
                'charset' => $row['character_set_client'], 'collation' => $row['collation_connection'],
            ];
        }
        ksort($triggers);
        foreach (self::REGISTER_TRIGGERS as $name) {
            if (!isset($triggers[$name])) throw new RuntimeException("Forventet integritetsvern mangler: $name");
        }
        foreach (self::GUARDS as $name => $table) {
            if ($triggers[$name]['table'] !== $table || $triggers[$name]['event'] !== 'DELETE' || $triggers[$name]['timing'] !== 'BEFORE') {
                throw new RuntimeException('Slettesperrene har uventet struktur.');
            }
        }
        $rows = []; $ddl = [];
        foreach ($selectors as $table => $query) {
            $rows[$table] = $this->q($query, [$this->company])->fetchAll(PDO::FETCH_ASSOC);
            $definition = $this->db->query('SHOW CREATE TABLE '.$this->table($table))->fetch(PDO::FETCH_NUM);
            $ddl[$table] = $definition[1];
        }
        if ($this->q('SELECT COUNT(*) FROM '.$this->table('unified_ledger').' l JOIN '.$this->table('sales_orders').
            " o ON o.id=l.order_id WHERE o.company_id=? AND (l.company_id<>? OR l.module_source IS NULL OR l.module_source<>'sales')",
            [$this->company, $this->company])->fetchColumn()) {
            throw new RuntimeException('Ordrene har ledgerreferanser fra andre moduler/bedrifter. Manuell gjennomgang kreves.');
        }
        $snapshot = ['format' => 'sales-test-reset-v1', 'database' => $this->database,
            'company_id' => $this->company, 'rows' => $rows, 'ddl' => $ddl, 'triggers' => $triggers];
        $snapshot['stock_restore'] = $this->stockRestore($rows);
        $snapshot['fingerprint'] = hash('sha256', self::json($snapshot));
        return $snapshot;
    }

    /** Only known local checkout histories can be unwound without guessing. */
    private function stockRestore(array $rows): array {
        $orders = array_column($rows['sales_orders'], null, 'id');
        $products = array_column($rows['sales_products'], null, 'id');
        $returns = [];
        foreach ($rows['sales_register_documents'] as $doc) {
            if ($doc['document_type'] === 'return') $returns[$doc['id']] = $doc;
        }
        $ledger = [];
        foreach ($rows['unified_ledger'] as $line) {
            if (!isset($orders[$line['order_id']])) {
                // Local full cash returns use the RETURN DOCUMENT ID as ledger
                // order_id, not the original sales_orders ID.
                $doc = $returns[$line['order_id']] ?? null;
                $meta = json_decode((string)$line['metadata'], true, 64, JSON_THROW_ON_ERROR);
                if (!$doc || ($orders[$doc['order_id']]['status'] ?? '') !== 'refunded' ||
                    ($meta['original_order_id'] ?? '') !== $doc['order_id'] ||
                    ($meta['register_document_id'] ?? '') !== $doc['id'] ||
                    (float)$line['selling_price'] > 0) {
                    throw new RuntimeException('Sales-ledger har foreldreløse rader. Manuell gjennomgang kreves.');
                }
                continue;
            }
            $ledger[$line['order_id']][] = $line;
        }
        $items = [];
        foreach ($rows['sales_orders_items'] as $item) {
            if (!isset($products[$item['product_id']])) throw new RuntimeException('En ordrelinje mangler produkt i samme bedrift.');
            $items[$item['order_id']][] = $item;
        }
        $restore = [];
        foreach ($orders as $id => $order) {
            if (!in_array($order['status'], ['paid', 'completed', 'cancelled', 'refunded'], true)) {
                throw new RuntimeException('Ventende/uavklarte betalinger må avsluttes og avstemmes før nullstilling.');
            }
            $positive = $ledger[$id] ?? [];
            if (!$positive || count($positive) !== count($items[$id] ?? [])) {
                throw new RuntimeException('Ufullstendig ordre-/ledgergrunnlag. Lageret kan ikke gjettes.');
            }
            $saleDocs = array_values(array_filter($rows['sales_register_documents'],
                static fn($d) => $d['order_id'] === $id && $d['document_type'] === 'sale'));
            if (($order['status'] === 'cancelled' && $saleDocs) ||
                ($order['status'] !== 'cancelled' && count($saleDocs) !== 1)) {
                throw new RuntimeException('Kvitteringsgrunnlaget stemmer ikke med ordrestatus.');
            }
            $lineItems = array_column($items[$id], null, 'id');
            foreach ($positive as $entry) {
                $item = $lineItems[$entry['source_id']] ?? null;
                if (!$item || (float)$entry['quantity'] !== (float)$item['quantity']) {
                    throw new RuntimeException('Ledgerlinjer og ordrelinjer stemmer ikke overens.');
                }
                unset($lineItems[$entry['source_id']]);
                $meta = json_decode((string)$entry['metadata'], true, 64, JSON_THROW_ON_ERROR);
                $control = $meta['sales'] ?? [];
                if (($control['version'] ?? null) !== 1 || empty($control['register_binding'])) {
                    throw new RuntimeException('Eldre ordre uten sikkert kassegrunnlag krever manuell gjennomgang.');
                }
                if (in_array($order['status'], ['paid', 'completed', 'refunded'], true) &&
                    !in_array($entry['payment_status'], ['paid', 'completed'], true)) {
                    throw new RuntimeException('Betalingsstatus og ordrestatus stemmer ikke overens.');
                }
                if ($order['status'] === 'cancelled' && !in_array($control['provider_state'] ?? '', ['ABORTED', 'EXPIRED', 'TERMINATED', 'REJECTED'], true)) {
                    throw new RuntimeException('Avbrutt ordre har ukjent lagerfrigivelse.');
                }
            }
            if ($order['status'] === 'refunded') {
                $orderReturns = array_filter($returns, static fn($d) => $d['order_id'] === $id);
                if (count($orderReturns) !== 1) throw new RuntimeException('Refusjon uten kjent full retur krever manuell lagerkontroll.');
                $return = array_values($orderReturns)[0];
                $payload = json_decode($return['payload'], true, 64, JSON_THROW_ON_ERROR);
                if (($payload['payment']['method'] ?? '') !== 'cash' ||
                    (int)$return['total_minor'] !== -(int)$saleDocs[0]['total_minor']) {
                    throw new RuntimeException('Bare kjent full kontantretur kan nullstilles med automatisk lagerkontroll.');
                }
                continue; // The local full-return path has already restored stock.
            }
            if ($order['status'] === 'cancelled') continue; // Already released exactly once.
            foreach ($items[$id] as $item) {
                $qty = filter_var((string)$item['quantity'], FILTER_VALIDATE_INT);
                // MySQL DECIMAL quantities are returned as "2.00".
                if ($qty === false && preg_match('/^([0-9]+)\\.0+$/D', (string)$item['quantity'], $m)) $qty = (int)$m[1];
                if ($qty === false || $qty < 1) throw new RuntimeException('Uventet antall på ordrelinje.');
                $product = $item['product_id'];
                $restore[$product] = ($restore[$product] ?? 0) + $qty;
                if ($restore[$product] + (int)$products[$product]['stock_quantity'] > 2147483647) {
                    throw new RuntimeException('Tilbakeføring overstiger lagergrensen.');
                }
            }
        }
        ksort($restore);
        return $restore;
    }

    public static function report(array $snapshot): array {
        return ['mode' => 'read-only', 'database' => $snapshot['database'], 'company_id' => $snapshot['company_id'],
            'fingerprint' => $snapshot['fingerprint'],
            'counts' => array_map('count', $snapshot['rows']), 'stock_restore' => $snapshot['stock_restore'],
            'notice' => 'Produkter, dynamiske felt, betalingsoppsett, andre moduler og audit/e-postlogger beholdes. Ingen ekstern refusjon utføres.'];
    }

    private function privateBackup(string $dir, array $snapshot): string {
        $base = realpath($dir); $root = realpath(dirname(__DIR__, 2));
        if ($base === false || !is_dir($base) || $base === $root || str_starts_with($base.'/', $root.'/')) {
            throw new RuntimeException('Backupmappen må finnes og ligge utenfor hele prosjektets webrot.');
        }
        if ((fileperms($base) & 0077) !== 0) throw new RuntimeException('Backupmappen må ha rettigheter 0700.');
        $path = $base.'/sales-test-reset-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(6));
        if (!mkdir($path, 0700)) throw new RuntimeException('Kunne ikke opprette privat backupmappe.');
        $this->save($path.'/snapshot.json', self::json($snapshot)."\n");
        $recovery = "-- OFFLINE ONLY. Original delete guards; run only if a guard is missing.\nUSE ".$this->ident($this->database).";\nDELIMITER $$\n";
        foreach (self::GUARDS as $name => $_) {
            $t = $snapshot['triggers'][$name];
            $recovery .= 'SET SESSION sql_mode='.$this->db->quote($t['sql_mode'])."$$\n".
                'SET NAMES '.$this->ident($t['charset']).' COLLATE '.$this->ident($t['collation'])."$$\n".
                'DROP TRIGGER IF EXISTS '.$this->table($name)."$$\n".$t['sql']."$$\n";
        }
        $this->save($path.'/restore-delete-guards.sql', $recovery."DELIMITER ;\n");
        $this->save($path.'/status.json', self::json(['phase' => 'backed-up', 'fingerprint' => $snapshot['fingerprint']])."\n");
        return $path;
    }
    private function save(string $path, string $content): void {
        $file = fopen($path, 'wb');
        if (!$file) throw new RuntimeException('Kunne ikke skrive privat backup/status.');
        try {
            if (!chmod($path, 0600) || fwrite($file, $content) !== strlen($content) || !fflush($file)) {
                throw new RuntimeException('Sikkerhetskopien kunne ikke lagres fullstendig.');
            }
            if (function_exists('fsync') && !fsync($file)) throw new RuntimeException('Sikkerhetskopien kunne ikke synkroniseres til disk.');
        } finally { fclose($file); }
    }
    private function recreateGuard(string $name, array $trigger): void {
        $this->db->exec('SET SESSION sql_mode='.$this->db->quote($trigger['sql_mode']));
        $this->db->exec('SET NAMES '.$this->ident($trigger['charset']).' COLLATE '.$this->ident($trigger['collation']));
        $this->db->exec($trigger['sql']);
    }

    /**
     * OFFLINE test databases only. DDL cannot be rolled back. Original guards
     * are saved durably first and restored in finally, even on DML failure.
     */
    public function apply(string $expected, string $confirmation, bool $offline, string $backupDir): array {
        if (!$offline || $confirmation !== 'RESET TEST SALES '.$this->company || !preg_match('/^[a-f0-9]{64}$/D', $expected)) {
            throw new RuntimeException('Krever --offline-confirmed, eksakt bekreftelse og fingerprint fra forhåndskontrollen.');
        }
        if ($this->db->inTransaction()) throw new RuntimeException('Nullstilling krever en egen vedlikeholdsforbindelse.');
        $lock = 'sales-test-reset:'.substr(hash('sha256', $this->database), 0, 45);
        if ((int)$this->q('SELECT GET_LOCK(?,0)', [$lock])->fetchColumn() !== 1) throw new RuntimeException('En annen nullstilling kjører.');
        $dropped = []; $backup = null; $committed = false; $snapshot = null;
        $foreignKeys = (int)$this->db->query('SELECT @@SESSION.foreign_key_checks')->fetchColumn();
        $mode = (string)$this->db->query('SELECT @@SESSION.sql_mode')->fetchColumn();
        $charset = $this->db->query('SELECT @@SESSION.character_set_client,@@SESSION.collation_connection')->fetch(PDO::FETCH_NUM);
        try {
            $snapshot = $this->snapshot();
            if (!hash_equals($expected, $snapshot['fingerprint'])) throw new RuntimeException('Datagrunnlaget er endret. Kjør ny forhåndskontroll.');
            $backup = $this->privateBackup($backupDir, $snapshot);
            // Do not allow the wrapper to write automatic audit rows with no actor.
            $operation = function() use (&$dropped, &$committed, $snapshot, $expected, $backup): void {
                foreach (self::GUARDS as $name => $_) {
                    $this->db->exec('DROP TRIGGER '.$this->table($name)); $dropped[] = $name;
                }
                $this->db->exec('SET SESSION FOREIGN_KEY_CHECKS=0');
                $this->db->beginTransaction();
                $this->q('SELECT id FROM '.$this->table('tenant_companies').' WHERE id=? FOR UPDATE', [$this->company])->fetchColumn();
                // Compare rows again after the DDL boundary, without requiring the
                // now intentionally removed guards.
                foreach ($this->selectors() as $table => $select) {
                    if ($this->q($select, [$this->company])->fetchAll(PDO::FETCH_ASSOC) !== $snapshot['rows'][$table]) {
                        throw new RuntimeException('Data ble endret etter backup. Ingen data nullstilles.');
                    }
                }
                foreach ($snapshot['stock_restore'] as $product => $qty) {
                    $this->q('UPDATE '.$this->table('sales_products').' SET stock_quantity=stock_quantity+? WHERE company_id=? AND id=?',
                        [$qty, $this->company, $product]);
                }
                foreach (['email_queue', 'sales_register_documents', 'sales_register_journal', 'sales_register_periods',
                    'sales_orders_items', 'unified_ledger', 'sales_orders', 'sales_cart_items', 'sales_cart'] as $table) {
                    if (!isset($snapshot['rows'][$table])) continue;
                    // Exact backed-up IDs, plus company scope for directly owned tables.
                    foreach (array_column($snapshot['rows'][$table], 'id') as $id) {
                        $direct = !in_array($table, ['sales_orders_items', 'sales_cart_items'], true);
                        $this->q('DELETE FROM '.$this->table($table).' WHERE id=?'.($direct ? ' AND company_id=?' : ''),
                            $direct ? [$id, $this->company] : [$id]);
                    }
                }
                $this->q('UPDATE '.$this->table('sales_registers')." SET status='draft',activated_at_utc=NULL,retired_at_utc=NULL,
                    last_period_number=0,last_journal_number=0,last_document_number=0,last_receipt_number=0,
                    last_training_number=0,last_z_number=0,last_journal_hash=NULL WHERE company_id=?", [$this->company]);
                foreach ($this->selectors() as $table => $select) {
                    if (in_array($table, ['sales_registers', 'sales_products'], true)) continue;
                    if ($this->q($select, [$this->company])->fetch()) throw new RuntimeException('Etterkontroll fant gjenværende testhistorikk.');
                }
                $this->save($backup.'/status.json', self::json(['phase' => 'ready-to-commit', 'fingerprint' => $expected])."\n");
                $this->db->commit(); $committed = true;
            };
            if (method_exists($this->db, 'withExplicitAudit')) $this->db->withExplicitAudit($operation); else $operation();
        } finally {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $failures = [];
            foreach ($dropped as $name) {
                try {
                    $this->recreateGuard($name, $snapshot['triggers'][$name]);
                    $restored = $this->db->query('SHOW CREATE TRIGGER '.$this->table($name))->fetch(PDO::FETCH_ASSOC);
                    if ($restored['SQL Original Statement'] !== $snapshot['triggers'][$name]['sql']) {
                        throw new RuntimeException('Gjenopprettet trigger avviker fra originalen.');
                    }
                }
                catch (Throwable $e) { $failures[] = $name; }
            }
            $this->db->exec('SET SESSION FOREIGN_KEY_CHECKS='.$foreignKeys);
            $this->db->exec('SET SESSION sql_mode='.$this->db->quote($mode));
            $this->db->exec('SET NAMES '.$this->ident($charset[0]).' COLLATE '.$this->ident($charset[1]));
            $this->q('SELECT RELEASE_LOCK(?)', [$lock]);
            if ($backup) $this->save($backup.'/status.json', self::json([
                'phase' => $committed ? 'committed' : 'rolled-back', 'missing_guards' => $failures,
                'notice' => 'Hold systemet offline dersom missing_guards ikke er tom. Bruk restore-delete-guards.sql.',
            ])."\n");
            if ($failures) throw new RuntimeException('KRITISK: hold hele systemet offline. Gjenopprett slettesperrer fra privat backup: '.implode(', ', $failures));
        }
        return ['reset' => true, 'company_id' => $this->company, 'backup_directory' => $backup,
            'notice' => 'Testhistorikk nullstilt. Produkter beholdt, lager tilbakeført og kassepunkter satt til utkast. Aktiver og åpne kassene på nytt.'];
    }
}
