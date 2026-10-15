<?php
declare(strict_types=1);

/** All retail rounding is integer half-up; selling_price is gross, not net. */
final class SalesMoney {
    public const MAX = 9999999999; // unified_ledger DECIMAL(10,2), in øre.
    public static function minor(mixed $value): int {
        if (!is_string($value) && !is_int($value) && !is_float($value)) throw new InvalidArgumentException('Ugyldig beløp');
        $text=str_replace(',', '.', trim((string)$value));
        if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/D',$text,$m)) throw new InvalidArgumentException('Beløp må være positivt med høyst to desimaler');
        $minor=(int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');
        if ($minor>self::MAX) throw new InvalidArgumentException('Beløpet er for stort');
        return $minor;
    }
    public static function decimal(int $minor): string {
        return intdiv($minor,100).'.'.str_pad((string)($minor%100),2,'0',STR_PAD_LEFT);
    }
    public static function integer(mixed $value,int $min=0,int $max=1000000): int {
        if(is_string($value) && preg_match('/^(\d+)\.0{1,2}$/D',$value,$match))$value=$match[1];
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^\d{1,10}$/D',(string)$value)
            || (int)$value<$min || (int)$value>$max) throw new InvalidArgumentException('Ugyldig heltall');
        return (int)$value;
    }
    public static function line(array $product,int $quantity,bool $taxRegistered,?DateTimeImmutable $now=null): array {
        $now??=new DateTimeImmutable('now',new DateTimeZone('Europe/Oslo'));
        $list=self::minor($product['selling_price']);
        $discount=self::minor($product['discount_percent']??'0');
        if ($discount>10000) throw new InvalidArgumentException('Rabatt kan ikke overstige 100 prosent');
        foreach (['discount_starts_at','discount_ends_at'] as $key) {
            if (!empty($product[$key]) && !self::date((string)$product[$key])) throw new InvalidArgumentException('Ugyldig rabattdato');
        }
        $active=(!$product['discount_starts_at'] || $now>=new DateTimeImmutable($product['discount_starts_at'],new DateTimeZone('Europe/Oslo')))
            && (!$product['discount_ends_at'] || $now<=new DateTimeImmutable($product['discount_ends_at'],new DateTimeZone('Europe/Oslo')));
        if (!$active) $discount=0;
        $unit=intdiv($list*(10000-$discount)+5000,10000);
        $total=$unit*$quantity;
        if ($total>self::MAX) throw new InvalidArgumentException('Linjesummen overstiger regnskapsgrensen');
        $rate=$taxRegistered && (int)($product['item_taxable']??0)===1 && ($product['tax_class']??'')!=='exempt'
            ? self::minor($product['tax_rate']??'0') : 0;
        if ($rate>10000) throw new InvalidArgumentException('Ugyldig MVA-sats');
        $net=intdiv($total*10000+intdiv(10000+$rate,2),10000+$rate);
        return ['unit_price_minor'=>$unit,'list_price_minor'=>$list,'total_minor'=>$total,
            'subtotal_minor'=>$net,'tax_minor'=>$total-$net,'tax_rate'=>self::decimal($rate),'discount_percent'=>self::decimal($discount)];
    }
    public static function date(string $value): bool {
        $d=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value);
        return $d && $d->format('Y-m-d H:i:s')===$value;
    }
}
