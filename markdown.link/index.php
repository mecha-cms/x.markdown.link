<?php

function fn_markdown_link($content, $lot) {
    if (!isset($lot['type']) || $lot['type'] !== 'Markdown') {
        return $content;
    }
    if (strpos($content, '[link:') === false) {
        return $content;
    }
    global $language, $url;
    $links = "";
    return preg_replace_callback('#(?:\[(.*?)\])?\[link:((?:\.{2}/)*)([a-z\d/-]*?)([?&\#].*?)?\]#', function($m) use(&$links, $language, $lot, $url) {
        $u = rtrim(str_replace(PAGE . DS, "", dirname($lot['path']) . DS), DS);
        if (!empty($m[2]) && ($i = substr_count($m[2], '../')) !== 0) {
            $u = Path::D($u, $i);
            $m[2] = str_replace('../', "", $m[2]);
        }
        if ($u !== "") {
            $u = '/' . $u;
        }
        if (empty($m[2]) && strpos($m[3], '/') === 0) {
            $p = PAGE . $m[3];
        } else {
            $p = PAGE . $u . DS . $m[3];
        }
        $m[4] = isset($m[4]) ? $m[4] : "";
        $f = File::exist([
            $p . '.page',
            $p . '.archive'
        ]);
        if ($m[3] && !$f) {
            Hook::fire('on.' . basename(__DIR__) . '.x', [$url->current, $lot, $m]);
            return HTML::s($m[1] ?: $language->link_broken, [
                'title' => $m[1] ? $language->link_broken : $m[0],
                'css' => ['color' => '#f00']
            ]);
        }
        $t = To::title(Path::B($m[2]));
        $title = Page::open($f)->get('title', $t);
        $title_text = Page::apart(file_get_contents($f), 'title', $t);
        $id = md5($m[3] . $m[4]) . '-' . uniqid(); // Unique ID
        $links .= "\n" . '[link:' . $id . ']: ' . ($m[3] ? $url . To::url(strpos($m[3], '/') === 0 ? $m[3] : $u . '/' . $m[3]) . $m[4] . ' "' . To::text($title) . '"' : $m[4]);
        return '[' . ($m[1] ?: $title_text) . '][link:' . $id . ']';
    }, $content) . "\n" . $links;
}

Hook::set(['page.content', 'page.description'], 'fn_markdown_link', 1.9);