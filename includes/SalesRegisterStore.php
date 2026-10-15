<?php
declare(strict_types=1);

/**
 * Fiscal storage adapter. Call writes only inside SalesService's company
 * transaction, then lock register before period. Never runs DDL.
 */
final class SalesRegisterStore {
    public function __construct(private PDO $db,private string $company,private ?string $actor) {}
    public function q(string $sql,array $args=[]): PDOStatement {
        $q=$this->db->prepare($sql);$q->execute($args);return $q;
    }
    public static function id(): string {
        $h=bin2hex(random_bytes(16));
        return substr($h,0,8).'-'.substr($h,8,4).'-4'.substr($h,13,3).'-a'.substr($h,17,3).'-'.substr($h,20);
    }
    public static function json(array $data): string {
        return json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
    public function ready(): void {
        foreach(['registers','register_periods','register_journal','register_documents'] as $table)
            $this->q("SELECT id FROM sales_$table LIMIT 0");
        $expected=['srp_before_insert','srp_after_insert','srp_before_update','srp_no_delete',
            'srj_before_insert','srj_after_insert','srj_no_update','srj_no_delete',
            'srd_before_insert','srd_after_insert','srd_no_update','srd_no_delete'];
        $triggers=$this->q('SHOW TRIGGERS')->fetchAll(PDO::FETCH_ASSOC);
        foreach($expected as $name)if(!in_array($name,array_column($triggers,'Trigger'),true))
            throw new RuntimeException('Kasseoppsettet mangler integritetsvern. Kontroller de tolv kassetriggerne før salg.');
        $z=$this->q("SHOW COLUMNS FROM sales_register_documents LIKE 'z_period_id'")->fetch(PDO::FETCH_ASSOC);
        if(!$z || str_contains(strtoupper($z['Extra']),'GENERATED'))
            throw new RuntimeException('Kasseoppsettet må samsvare med den innførte varianten med vanlig z_period_id.');
    }
    public function register(string $id,bool $lock=false): array {
        $row=$this->q('SELECT * FROM sales_registers WHERE company_id=? AND id=?'.($lock?' FOR UPDATE':''),[$this->company,$id])->fetch(PDO::FETCH_ASSOC);
        if(!$row)throw new OutOfBoundsException('Kassapunktet finnes ikke i bedriften');
        return $row;
    }
    public function period(string $register,bool $lock=false): ?array {
        return $this->q("SELECT * FROM sales_register_periods WHERE company_id=? AND register_id=? AND status<>'closed'".($lock?' FOR UPDATE':''),[$this->company,$register])->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public function openContext(string $id): array {
        $this->ready();$r=$this->register($id,true);$p=$this->period($id,true);
        if($r['status']!=='active' || !$p || $p['status']!=='open')
            throw new DomainException('Velg aktivt kassapunkt og åpne kassen før salg');
        if($this->q("SELECT id FROM sales_register_documents WHERE company_id=? AND register_id=? AND period_id=? AND document_type='z_report'",[$this->company,$id,$p['id']])->fetchColumn())
            throw new DomainException('Perioden har allerede en Z-rapport og kan ikke brukes igjen');
        $this->verify($id);
        return ['register_id'=>$id,'period_id'=>$p['id'],'register'=>$r,'period'=>$p];
    }
    public function event(string $register,?string $period,string $type,string $key,array $payload,?string $order=null): array {
        $head=$this->register($register,true);
        $prior=$this->q('SELECT * FROM sales_register_journal WHERE company_id=? AND register_id=? AND idempotency_key=?',[$this->company,$register,$key])->fetch(PDO::FETCH_ASSOC);
        $json=self::json($payload);
        if($prior) {
            if($prior['event_type']!==$type || $prior['payload']!==$json || $prior['period_id']!==$period || $prior['order_id']!==$order)
                throw new DomainException('Handlingsreferansen er brukt til en annen kassehandling');
            return $prior;
        }
        $id=self::id();
        $this->q('INSERT INTO sales_register_journal(id,company_id,register_id,period_id,event_type,idempotency_key,actor_id,order_id,payload) VALUES(?,?,?,?,?,?,?,?,?)',
            [$id,$this->company,$register,$period,$type,$key,$this->actor,$order,$json]);
        $stored=$this->q('SELECT * FROM sales_register_journal WHERE company_id=? AND id=?',[$this->company,$id])->fetch(PDO::FETCH_ASSOC);
        $after=$this->register($register);
        if((int)$stored['sequence_number']!==(int)$head['last_journal_number']+1
            || $stored['previous_hash']!==$head['last_journal_hash'] || $stored['payload']!==$json
            || !hash_equals($stored['entry_hash'],$this->entryHash($stored))
            || (int)$after['last_journal_number']!==(int)$stored['sequence_number']
            || $after['last_journal_hash']!==$stored['entry_hash'])
            throw new RuntimeException('Kassetriggerne lagret ikke journalen korrekt. Handlingen er rullet tilbake.');
        return $stored;
    }
    private function entryHash(array $j): string {
        $stamp=(new DateTimeImmutable($j['recorded_at_utc'],new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u');
        $parts=[$j['company_id'],$j['register_id'],$j['id'],(string)$j['sequence_number'],$j['period_id']??'',$j['actor_id']??'',$j['order_id']??'',
            $j['event_type'],$j['idempotency_key'],$j['system_version'],$stamp,$j['previous_hash']??str_repeat('0',64),hash('sha256',$j['payload'])];
        $encoded='SRJ1';foreach($parts as $part)$encoded.=strlen($part).':'.$part;
        return hash('sha256',$encoded);
    }
    public function document(string $id): array {
        $d=$this->q('SELECT * FROM sales_register_documents WHERE company_id=? AND id=?',[$this->company,$id])->fetch(PDO::FETCH_ASSOC);
        if(!$d)throw new OutOfBoundsException('Kassedokumentet finnes ikke');
        if(!hash_equals($d['payload_sha256'],hash('sha256',$d['payload'])))throw new RuntimeException('Kassedokumentets kontrollsum stemmer ikke');
        $d['payload']=json_decode($d['payload'],true,64,JSON_THROW_ON_ERROR);
        return $d;
    }
    public function orderDocument(string $order): ?array {
        $id=$this->q("SELECT id FROM sales_register_documents WHERE company_id=? AND order_id=? AND document_type='sale' LIMIT 1",[$this->company,$order])->fetchColumn();
        return $id?$this->document($id):null;
    }
    public function issue(array $binding,string $type,string $key,array $payload,int $total=0,?string $order=null,?string $source=null): array {
        $r=$this->register($binding['register_id'],true);
        $old=$this->q('SELECT d.id FROM sales_register_documents d JOIN sales_register_journal j ON j.id=d.journal_id AND j.company_id=d.company_id WHERE d.company_id=? AND d.register_id=? AND j.idempotency_key=?',[$this->company,$r['id'],$key])->fetchColumn();
        if($old)return $this->document($old);
        $payload['document']=['type'=>$type,'number'=>(int)$r['last_document_number']+1,
            'receipt_number'=>in_array($type,['sale','return'],true)?(int)$r['last_receipt_number']+1:null,
            'z_number'=>$type==='z_report'?(int)$r['last_z_number']+1:null];
        $journal=$this->event($r['id'],$binding['period_id'],'document.'.$type,$key,$payload,$order);
        $id=self::id();
        $this->q('INSERT INTO sales_register_documents(id,company_id,register_id,period_id,journal_id,document_type,order_id,source_document_id,total_minor,payload) VALUES(?,?,?,?,?,?,?,?,?,?)',
            [$id,$this->company,$r['id'],$binding['period_id'],$journal['id'],$type,$order,$source,$total,self::json($payload)]);
        $d=$this->document($id);
        if((int)$d['sequence_number']!==$payload['document']['number'])throw new RuntimeException('Dokumentnummeret samsvarer ikke med kassejournalen');
        return $d;
    }
    /** Verify exact bytes, all links and mutable counter heads, not JSON reserialization. */
    public function verify(string $register): void {
        $r=$this->register($register);
        $rows=$this->q('SELECT *,DATE_FORMAT(recorded_at_utc,\'%Y-%m-%dT%H:%i:%s.%f\') AS hash_time FROM sales_register_journal WHERE company_id=? AND register_id=? ORDER BY sequence_number',[$this->company,$register])->fetchAll(PDO::FETCH_ASSOC);
        $previous=null;$number=0;
        foreach($rows as $j) {
            $number++;
            $parts=[$j['company_id'],$j['register_id'],$j['id'],(string)$number,$j['period_id']??'',$j['actor_id']??'',$j['order_id']??'',
                $j['event_type'],$j['idempotency_key'],$j['system_version'],$j['hash_time'],$previous??str_repeat('0',64),hash('sha256',$j['payload'])];
            $encoded='SRJ1';foreach($parts as $part)$encoded.=strlen($part).':'.$part;
            if((int)$j['sequence_number']!==$number || $j['previous_hash']!==$previous
                || !hash_equals($j['payload_sha256'],hash('sha256',$j['payload'])) || !hash_equals($j['entry_hash'],hash('sha256',$encoded)))
                throw new RuntimeException('Kassejournalens integritet kunne ikke bekreftes. Salg er sperret.');
            $previous=$j['entry_hash'];
        }
        if((int)$r['last_journal_number']!==$number || $r['last_journal_hash']!==$previous)throw new RuntimeException('Kassejournalens hode samsvarer ikke');
        $heads=$this->q('SELECT COALESCE(MAX(sequence_number),0) AS last_document_number,COALESCE(MAX(receipt_number),0) AS last_receipt_number,COALESCE(MAX(training_number),0) AS last_training_number,COALESCE(MAX(z_number),0) AS last_z_number FROM sales_register_documents WHERE company_id=? AND register_id=?',[$this->company,$register])->fetch(PDO::FETCH_ASSOC);
        foreach($heads as $field=>$value)if((int)$r[$field]!== (int)$value)throw new RuntimeException('Kassens dokumentnummerering samsvarer ikke');
        $last=$this->q('SELECT COALESCE(MAX(period_number),0) FROM sales_register_periods WHERE company_id=? AND register_id=?',[$this->company,$register])->fetchColumn();
        if((int)$r['last_period_number']!==(int)$last)throw new RuntimeException('Kassens periodenummerering samsvarer ikke');
    }
}
