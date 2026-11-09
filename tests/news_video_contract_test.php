<?php
require_once __DIR__.'/../includes/announcement_elements.php';
foreach ([
    ['https://youtu.be/abcdefghijk', 'youtube'],
    ['https://www.youtube.com/shorts/abcdefghijk', 'youtube'],
    ['https://vimeo.com/123456789', 'vimeo'],
    ['https://vimeo.com/123456789/abc123', 'vimeo'],
    ['https://player.vimeo.com/video/123456789?h=abc123', 'vimeo'],
    ['https://cdn.example.test/movie.MP4?token=test', 'file'],
    ['https://cdn.example.test/movie.webm', 'file'],
] as [$url, $provider]) {
    $input = [['element_type'=>'youtube_video','content'=>['url'=>$url,'caption'=>'Min video']]];
    $first = announcement_validate_elements($input);
    $content = json_decode($first[0]['content_json'], true);
    if (($content['provider'] ?? 'youtube') !== $provider) throw new RuntimeException('Wrong provider: '.$url);
    $loaded = announcement_element_editor_content('youtube_video', $content);
    $second = announcement_validate_elements([['element_type'=>'youtube_video','content'=>$loaded]]);
    if ($first !== $second) throw new RuntimeException('Round-trip loses data: '.$url);
    if (str_contains($url, 'abc123') && !str_contains($loaded['url'], 'abc123')) throw new RuntimeException('Lost unlisted Vimeo hash');
}
foreach (['javascript:alert(1)', 'https://evil.example/watch?v=abcdefghijk', 'https://youtube.com.evil.test/watch?v=abcdefghijk', 'https://vimeo.com/1234/invalid-hash', 'https://u:p@cdn.example.test/a.mp4', 'https://vimeo.com/123?h[]=x', 'https://youtu.be/short'] as $url) {
    try {
        announcement_validate_elements([['element_type'=>'youtube_video','content'=>['url'=>$url]]]);
    } catch (InvalidArgumentException $e) { continue; }
    throw new RuntimeException('Unsafe/invalid video accepted: '.$url);
}
echo "News video storage contracts passed\n";
