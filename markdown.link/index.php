<?php

function fn_markdown_link($content, $lot = [], $that) {
    if ($that->get('type') !== 'Markdown') {
        return $content;
    }
    if (strpos($content, '[link:') === false) {
        return $content;
    }
    global $language, $url;
    $links = "";
    return preg_replace_callback('#(?:\[(.*?)\])?\[link:((?:\.{2}/)*|\.{2})([\w/-]*?)([?&\#].*?)?\]#', function($m) use(&$links, $language, $lot, $url) {
        if (!isset($lot['path'])) {
            return $m[0];
        }
        $u = rtrim(str_replace(PAGE . DS, "", dirname($lot['path']) . DS), DS);
        if (!empty($m[2])) {
            if ($m[2] === '..' && empty($m[3])) {
                $u = Path::D($u);
                $m[2] = "";
            } else if (($i = substr_count($m[2], '../')) !== 0) {
                $u = Path::D($u, $i);
                $m[2] = str_replace('../', "", $m[2]);
            }
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
        ], null);
        if ($m[3] && !$f) {
            Hook::fire('on.' . basename(__DIR__) . '.x', [$url->current, $lot, $m]);
            return HTML::s($m[1] ?: $language->link_broken, [
                'title' => $m[1] ? $language->link_broken : $m[0],
                'style[]' => ['color' => '#f00']
            ]);
        }
        $t = To::title(Path::B($m[2]));
        $title = Page::open($f)->get('title', $t);
        $title_text = is_file($f) ? Page::apart(file_get_contents($f), 'title', $t) : $t;
        $id = md5($m[3] . $m[4]) . '-' . uniqid(); // Unique ID
        $links .= "\n" . '[link:' . $id . ']: ' . ($m[3] ? $url . To::URL(strpos($m[3], '/') === 0 ? $m[3] : $u . '/' . $m[3]) . $m[4] . ' "' . To::text($title) . '"' : $url . To::URL($u) . $m[4]);
        return '[' . ($m[1] ?: $title_text) . '][link:' . $id . ']';
    }, $content) . "\n" . $links;
}

Hook::set(['*.content', '*.description'], 'fn_markdown_link', 1.9);