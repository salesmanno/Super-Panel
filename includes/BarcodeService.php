<?php
/**
 * BarcodeService - Generates EAN13 and QR codes for events
 */
class BarcodeService {
    
    /**
     * Generate EAN13 barcode for events_checkin
     * EAN13 format: 12 digits + 1 check digit
     * 
     * @param string $checkInId The events_checkin.id (will be hashed to create unique code)
     * @param int $eventId The event_id (included in code)
     * @return string 13-digit EAN13 code
     */
    public static function generateEAN13($checkInId, $eventId) {
        // Create a seed from checkin ID and event ID
        $seed = $checkInId . $eventId;
        $hash = substr(md5($seed), 0, 11); // Get 11 first chars from hash
        $digits = preg_replace('/[^0-9]/', '', $hash); // Keep only digits
        $digits = str_pad(substr($digits, 0, 11), 11, '0', STR_PAD_LEFT); // Pad to 11 digits
        
        // Add check digit (EAN13 algorithm)
        $checkDigit = self::calculateEAN13CheckDigit($digits);
        $ean13 = $digits . $checkDigit;
        
        return $ean13;
    }
    
    /**
     * Calculate EAN13 check digit (Luhn algorithm variant)
     * 
     * @param string $code12 12-digit code without check digit
     * @return string Single check digit
     */
    private static function calculateEAN13CheckDigit($code12) {
        $code12 = str_pad($code12, 12, '0', STR_PAD_LEFT);
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $digit = intval($code12[$i]);
            // Odd positions (1,3,5...) have weight 1, even (2,4,6...) have weight 3
            $weight = ($i % 2 == 0) ? 1 : 3;
            $sum += $digit * $weight;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        return (string)$checkDigit;
    }
    
    /**
     * Generate QR code content (URL/text to embed in QR)
     * Returns a URL or text suitable for QR encoding
     * 
     * @param string $checkInId
     * @param string $eventId
     * @param string $baseUrl (optional) e.g., "https://yourdomain.com"
     * @return string QR code content (URL or text)
     */
    public static function generateQRContent($checkInId, $eventId, $baseUrl = '') {
        // Generate a compact identifier
        $ean13 = self::generateEAN13($checkInId, $eventId);
        
        // Option 1: Return EAN13 as plain text (compact)
        // This is lightweight and can be scanned by any barcode scanner
        return $ean13;
        
        // Option 2: Return URL for QR code (uncomment if you have a verification endpoint)
        // if (!empty($baseUrl)) {
        //     return $baseUrl . '/api/verify_checkin.php?code=' . $ean13;
        // }
        // return '/api/verify_checkin.php?code=' . $ean13;
    }
    
    /**
     * Generate SVG barcode (for displaying on screen)
     * Returns inline SVG for EAN13 barcode
     * 
     * @param string $ean13 13-digit EAN13 code
     * @param int $width Barcode width (default 200px)
     * @param int $height Barcode height (default 100px)
     * @return string SVG HTML
     */
    public static function generateSVGBarcode($ean13, $width = 200, $height = 100) {
        $bars = self::encodeEAN13($ean13);
        $barWidth = $width / (strlen($bars) + 2);
        
        $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg" style="border: 1px solid #ccc; padding: 5px;">';
        $x = $barWidth;
        
        foreach (str_split($bars) as $bit) {
            if ($bit == '1') {
                $svg .= '<rect x="' . $x . '" y="10" width="' . $barWidth . '" height="' . ($height - 20) . '" fill="black"/>';
            }
            $x += $barWidth;
        }
        
        $svg .= '<text x="' . ($width / 2) . '" y="' . ($height - 5) . '" font-size="12" text-anchor="middle" font-family="monospace">' . htmlspecialchars($ean13) . '</text>';
        $svg .= '</svg>';
        
        return $svg;
    }
    
    /**
     * Encode EAN13 to bar pattern (internal use)
     * 
     * @param string $ean13
     * @return string Binary string representing bars
     */
    private static function encodeEAN13($ean13) {
        $leftOdd = ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011'];
        $leftEven = ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111'];
        $right = ['1110010', '1100110', '1101100', '1000010', '1011100', '1001110', '1010000', '1000100', '1001000', '1110100'];
        
        $firstDigit = intval($ean13[0]);
        $pattern = ['0', '0', '0', '0', '0', '0'];
        
        $firstDigitPattern = [
            '0' => '000000',
            '1' => '001011',
            '2' => '001101',
            '3' => '001110',
            '4' => '010011',
            '5' => '011001',
            '6' => '011100',
            '7' => '010101',
            '8' => '010110',
            '9' => '011010'
        ];
        
        $pattern = str_split($firstDigitPattern[$firstDigit]);
        
        $code = '101'; // Start guard
        
        for ($i = 1; $i <= 6; $i++) {
            $digit = intval($ean13[$i]);
            if ($pattern[$i - 1] == '0') {
                $code .= $leftOdd[$digit];
            } else {
                $code .= $leftEven[$digit];
            }
        }
        
        $code .= '01010'; // Center guard
        
        for ($i = 7; $i <= 12; $i++) {
            $digit = intval($ean13[$i]);
            $code .= $right[$digit];
        }
        
        $code .= '101'; // End guard
        
        return $code;
    }
}
