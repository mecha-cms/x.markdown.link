<?php

function fn_markdown_replace_link($content, $lot) {
    if (!isset($lot['type']) || $lot['type'] !== 'Markdown') {
        return $content;
    }
    if (strpos($content, '[link:') === false) {
        return $content;
    }
    global $language, $url;
    $links = "";
    return preg_replace_callback('#(?:\[(.*?)\])?\[link:((?:\.{2}/)*)([a-z\d/-]*?)([?&\#].*?)?\]#', function($m) use(&$links, $language, $url) {
        $pp = Path::D($url->path);
        if (!empty($m[2]) && ($i = substr_count($m[2], '../')) !== 0) {
            $pp = Path::D($pp, $i);
            $m[2] = str_replace('../', "", $m[2]);
        }
        $pp = $pp ? '/' . $pp : "";
        if (empty($m[2]) && strpos($m[3], '/') === 0) {
            $p = PAGE . $m[3];
        } else {
            $p = PAGE . $pp . '/' . $m[3];
        }
        $m[4] = isset($m[4]) ? $m[4] : "";
        $p = To::path($p);
        $ff = File::exist([$p . '.page', $p . '.archive']);
        if ($m[3] && !$ff) {
            return HTML::s($m[1] ?: $language->link_broken, [
                'title' => $m[1] ? $language->link_broken : $m[0],
                'css' => ['color' => '#f00']
            ]);
        }
        $t = To::title(Path::B($m[2]));
        $title = Page::open($ff)->get('title', $t);
        $title_text = Page::open($ff, [], "")->get('title', $t);
        $uid = md5($m[3] . $m[4]) . '-' . uniqid(); // Unique ID
        $links .= "\n" . '[link:' . $uid . ']: ' . ($m[3] ? $url . To::url(strpos($m[3], '/') === 0 ? $m[3] : $pp . '/' . $m[3]) . $m[4] . ' "' . To::text($title) . '"' : $m[4]);
        return '[' . ($m[1] ?: $title_text) . '][link:' . $uid . ']';
    }, $content) . "\n" . $links;
}

Hook::set('page.content', 'fn_markdown_replace_link', 1.9);