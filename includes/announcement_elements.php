<?php
require_once __DIR__.'/announcement_helpers.php';

const ANNOUNCEMENT_ELEMENT_TYPES = ['text','image','text_image','image_carousel','youtube_video'];

function announcement_element_url($value, string $label): string {
    if (!is_string($value) || strlen($value) > 2048 ||
        !filter_var($value, FILTER_VALIDATE_URL) ||
        !in_array(strtolower((string)parse_url($value, PHP_URL_SCHEME)), ['http','https'], true)) {
        throw new InvalidArgumentException($label.' må være en gyldig http- eller https-lenke.');
    }
    return $value;
}

function announcement_validate_elements($input): array {
    if (!is_array($input) || !array_is_list($input) || count($input) > 50) {
        throw new InvalidArgumentException('Innholdet må bestå av maksimalt 50 elementer.');
    }
    $elements = [];
    foreach ($input as $index => $item) {
        if (!is_array($item) || !in_array($item['element_type'] ?? null, ANNOUNCEMENT_ELEMENT_TYPES, true)) {
            throw new InvalidArgumentException('Ugyldig type innholdselement.');
        }
        $type = $item['element_type'];
        $content = $item['content'] ?? null;
        if (!is_array($content)) throw new InvalidArgumentException('Innholdet i et element er ugyldig.');
        $clean = [];
        if ($type === 'text') {
            $html = trim(announcement_sanitize_content((string)($content['html'] ?? '')));
            if (trim(announcement_content_to_plain_text($html)) === '') throw new InvalidArgumentException('Tekstelementet kan ikke være tomt.');
            $clean = ['html'=>$html];
        } elseif ($type === 'image') {
            $clean = ['url'=>announcement_element_url($content['url'] ?? null,'Bildefeltet'),
                'alt'=>mb_substr(trim((string)($content['alt'] ?? '')),0,255),
                'caption'=>mb_substr(trim((string)($content['caption'] ?? '')),0,2000)];
        } elseif ($type === 'text_image') {
            $html = trim(announcement_sanitize_content((string)($content['html'] ?? '')));
            if (trim(announcement_content_to_plain_text($html)) === '') throw new InvalidArgumentException('Tekst og bilde må inneholde tekst.');
            $requestedLayout=$content['layout']??'image_top';
            $layout = in_array($requestedLayout,['image_top','image_left','image_right'],true) ? $requestedLayout : 'image_top';
            $clean = ['html'=>$html,'url'=>announcement_element_url($content['url'] ?? null,'Bildefeltet'),
                'alt'=>mb_substr(trim((string)($content['alt'] ?? '')),0,255),
                'caption'=>mb_substr(trim((string)($content['caption'] ?? '')),0,2000),'layout'=>$layout];
        } elseif ($type === 'image_carousel') {
            $urls = $content['urls'] ?? [];
            if (!is_array($urls) || !array_is_list($urls) || count($urls)<2 || count($urls)>20) {
                throw new InvalidArgumentException('En bildegalleri må ha mellom 2 og 20 bildeadresser.');
            }
            $clean = ['images'=>array_map(static fn($url)=>['url'=>announcement_element_url($url,'Bildeadressen'),'alt'=>''], $urls)];
        } else {
            $url = announcement_element_url($content['url'] ?? null,'YouTube-lenken');
            $host = strtolower((string)parse_url($url,PHP_URL_HOST));
            $path = trim((string)parse_url($url,PHP_URL_PATH),'/');
            parse_str((string)parse_url($url,PHP_URL_QUERY),$query);
            $videoId = '';
            if (in_array($host,['youtu.be','www.youtu.be'],true)) $videoId=explode('/',$path)[0]??'';
            elseif (in_array($host,['youtube.com','www.youtube.com','m.youtube.com','youtube-nocookie.com','www.youtube-nocookie.com'],true)) {
                $videoId=($query['v']??'');
                if (!$videoId && preg_match('~^(?:embed|shorts)/([^/]+)~',$path,$match)) $videoId=$match[1];
            }
            if (!preg_match('/^[A-Za-z0-9_-]{11}$/',$videoId)) throw new InvalidArgumentException('YouTube-lenken ser ikke ut til å inneholde en gyldig video.');
            $clean = ['video_id'=>$videoId,'caption'=>mb_substr(trim((string)($content['caption']??'')),0,2000)];
        }
        $elements[]=['position'=>$index+1,'element_type'=>$type,'content_json'=>json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)];
    }
    return $elements;
}

function announcement_save_elements(PDO $db, string $companyId, string $announcementId, array $elements): void {
    $delete=$db->prepare('DELETE FROM tenant_news_elements WHERE company_id=? AND announcement_id=?');
    $delete->execute([$companyId,$announcementId]);
    if (!$elements) return;
    $insert=$db->prepare('INSERT INTO tenant_news_elements (id,company_id,announcement_id,position,element_type,content_json,created_by) VALUES (UUID(),?,?,?,?,?,?)');
    foreach ($elements as $element) $insert->execute([$companyId,$announcementId,$element['position'],$element['element_type'],$element['content_json'],$_SESSION['tenant_admin_id']??null]);
}

function announcement_load_elements(PDO $db, string $companyId, array $announcementIds): array {
    if (!$announcementIds) return [];
    $marks=implode(',',array_fill(0,count($announcementIds),'?'));
    $stmt=$db->prepare("SELECT announcement_id,position,element_type,content_json FROM tenant_news_elements WHERE company_id=? AND announcement_id IN ($marks) ORDER BY announcement_id,position,id");
    $stmt->execute(array_merge([$companyId],$announcementIds));
    $map=[];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[$row['announcement_id']][]=['position'=>(int)$row['position'],'element_type'=>$row['element_type'],'content'=>json_decode($row['content_json'],true)??[]];
    }
    return $map;
}
