<?php
declare(strict_types=1);

/** ISO Code 128, code set B. Internal codes are not GS1/EAN identifiers. */
final class SalesCode128 {
    private const WIDTHS=['212222','222122','222221','121223','121322','131222','122213','122312','132212','221213','221312','231212','112232','122132','122231','113222','123122','123221','223211','221132','221231','213212','223112','312131','311222','321122','321221','312212','322112','322211','212123','212321','232121','111323','131123','131321','112313','132113','132311','211313','231113','231311','112133','112331','132131','113123','113321','133121','313121','211331','231131','213113','213311','213131','311123','311321','331121','312113','312311','332111','314111','221411','431111','111224','111422','121124','121421','141122','141221','112214','112412','122114','122411','142112','142211','241211','221114','413111','241112','134111','111242','121142','121241','114212','124112','124211','411212','421112','421211','212141','214121','412121','111143','111341','131141','114113','114311','411113','411311','113141','114131','311141','411131','211412','211214','211232','2331112'];
    public static function symbols(string $code): array {
        if(!preg_match('/^[\x20-\x7e]{1,100}$/D',$code))throw new InvalidArgumentException('Produktet har ingen gyldig skannbar kode. Lagre produktet først.');
        $symbols=[104];$checksum=104;
        foreach(str_split($code) as $i=>$char){$value=ord($char)-32;$symbols[]=$value;$checksum+=($i+1)*$value;}
        return [...$symbols,$checksum%103,106];
    }
    public static function svg(string $code): string {
        $x=10;$bars='';
        foreach(self::symbols($code) as $symbol)foreach(str_split(self::WIDTHS[$symbol]) as $i=>$width) {
            $w=(int)$width;if($i%2===0)$bars.='<rect x="'.$x.'" y="2" width="'.$w.'" height="48"/>';$x+=$w;
        }
        $text=htmlspecialchars($code,ENT_XML1|ENT_QUOTES,'UTF-8');
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.($x+10).' 68" role="img" aria-label="'.$text.'"><rect width="100%" height="100%" fill="white"/><g fill="black">'.$bars.'</g><text x="'.(($x+10)/2).'" y="63" text-anchor="middle" font-size="10" font-family="monospace">'.$text.'</text></svg>';
    }
}
