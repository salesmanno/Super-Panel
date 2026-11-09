<?php
require_once __DIR__.'/announcement_helpers.php';

const ANNOUNCEMENT_ELEMENT_TYPES = ['text','image','text_image','image_carousel','youtube_video'];

/** Keep canonical JSON compatible with the reader while exposing editable URLs. */
function announcement_element_editor_content(string $type, array $content): array {
    if ($type === 'image_carousel' && !isset($content['urls'])) {
        if (isset($content['images']) && !is_array($content['images'])) throw new InvalidArgumentException('Bildekarusellen har ugyldige bildedata.');
        $content['urls'] = array_map(static fn($image) => is_array($image) ? ($image['url'] ?? '') : '', $content['images'] ?? []);
    }
    if ($type === 'youtube_video' && empty($content['url']) &&
        preg_match('/^[A-Za-z0-9_-]{11}$/', (string)($content['video_id'] ?? ''))) {
        $content['url'] = 'https://www.youtube.com/watch?v=' . $content['video_id'];
    }
    return $content;
}

function announcement_element_url($value, string $label): string {
    if (!is_string($value) || strlen($value) > 2048 ||
        !filter_var($value, FILTER_VALIDATE_URL) ||
        !in_array(strtolower((string)parse_url($value, PHP_URL_SCHEME)), ['http','https'], true)) {
        throw new InvalidArgumentException($label.' må være en gyldig http- eller https-lenke.');
    }
    return $value;
}

/** Legacy element_type stays unchanged; provider is carried in content_json. */
function announcement_video_content(array $content): array {
    $value = $content['url'] ?? null;
    $url = announcement_element_url(is_string($value) ? trim($value) : $value, 'Videolenken');
    if (parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null) {
        throw new InvalidArgumentException('Videolenken kan ikke inneholde påloggingsinformasjon.');
    }
    $host = preg_replace('/^www\./', '', strtolower((string)parse_url($url, PHP_URL_HOST)));
    $path = (string)parse_url($url, PHP_URL_PATH);
    parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
    $caption = mb_substr(trim((string)($content['caption'] ?? '')), 0, 2000);
    if (in_array($host, ['youtu.be','youtube.com','m.youtube.com','youtube-nocookie.com'], true)) {
        $id = $host === 'youtu.be' ? (explode('/', trim($path, '/'))[0] ?? '') : ($query['v'] ?? '');
        if (!$id && preg_match('~^/(?:embed|shorts)/([^/]+)~', $path, $match)) $id = $match[1];
        if (!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            throw new InvalidArgumentException('YouTube-lenken inneholder ikke en gyldig video.');
        }
        // Preserve the original reader contract for YouTube.
        return ['video_id'=>$id, 'caption'=>$caption];
    }
    if (in_array($host, ['vimeo.com','player.vimeo.com'], true)) {
        if (!preg_match('~^/(?:video/)?(\d+)(?:/([A-Za-z0-9]+))?/?$~', $path, $match)) {
            throw new InvalidArgumentException('Vimeo-lenken inneholder ikke en gyldig video.');
        }
        $hash = $query['h'] ?? ($match[2] ?? '');
        if (!is_string($hash) || ($hash !== '' && !preg_match('/^[A-Za-z0-9]+$/', $hash))) {
            throw new InvalidArgumentException('Vimeo-lenken har en ugyldig delingsnøkkel.');
        }
        return ['provider'=>'vimeo', 'url'=>'https://vimeo.com/'.$match[1].($hash !== '' ? '/'.$hash : ''), 'caption'=>$caption];
    }
    if (preg_match('/\.(mp4|webm)$/i', $path)) {
        return ['provider'=>'file', 'url'=>$url, 'caption'=>$caption];
    }
    throw new InvalidArgumentException('Bruk YouTube, Vimeo eller en direkte lenke til .mp4 eller .webm.');
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
        $content = announcement_element_editor_content($type, $content);
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
            $savedImages = $content['images'] ?? [];
            $clean = ['images'=>array_map(static function($url) use ($savedImages) {
                $url = announcement_element_url($url,'Bildeadressen');
                $alt = '';
                foreach ($savedImages as $image) {
                    if (is_array($image) && ($image['url'] ?? null) === $url) {
                        $alt = mb_substr(trim((string)($image['alt'] ?? '')), 0, 255);
                        break;
                    }
                }
                return ['url'=>$url,'alt'=>$alt];
            }, $urls)];
        } else {
            $clean = announcement_video_content($content);
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
        $content = json_decode($row['content_json'],true);
        if (!is_array($content)) throw new UnexpectedValueException('Et lagret nyhetselement har ugyldig JSON.');
        $map[$row['announcement_id']][]=['position'=>(int)$row['position'],'element_type'=>$row['element_type'],'content'=>announcement_element_editor_content($row['element_type'],$content)];
    }
    return $map;
}
