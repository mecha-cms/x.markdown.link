<?php namespace _\lot\x\markdown;

function link($content = "", array $lot = []) {
    $type = $this->type;
    if ('Markdown' !== $type && 'text/markdown' !== $type) {
        return $content;
    }
    if (false === \strpos($content, '[link:')) {
        return $content;
    }
    global $url;
    return \preg_replace_callback('/(?:\[([^]]*)\])?\[link:((?:\.{2}\/)*|\.{2})([^\s?&#]*)([?&#].*)?\]/', function($m) use($lot, $url) {
        if (!$path = $this->path) {
            return $m[0];
        }
        $u = \rtrim(\str_replace(\PAGE . \DS, "", \dirname($path) . \DS), \DS);
        if (!empty($m[2])) {
            if ('..' === $m[2] && empty($m[3])) {
                $u = \dirname($u);
                $m[2] = "";
            } else if (0 !== ($i = \substr_count($m[2], '../'))) {
                $u = \dirname($u, $i);
                $m[2] = \str_replace('../', "", $m[2]);
            }
        }
        $u = '.' === $u ? "" : $u;
        if ("" !== $u) {
            $u = '/' . $u;
        }
        if (empty($m[2]) && 0 === \strpos($m[3], '/')) {
            $p = \PAGE . $m[3];
        } else {
            $p = \PAGE . $u . \DS . $m[3];
        }
        $m[4] = isset($m[4]) ? $m[4] : "";
        $f = \File::exist([
            $p . '.page',
            $p . '.archive'
        ]);
        if ($m[3] && !$f) {
            return '<s title="' . ($m[1] ? \i('broken link') : $m[0]) . '" style="color:#f00;">' . ($m[1] ?: \i('broken link')) . '</s>';
        }
        $t = \To::title(\basename($m[2]));
        $p = new \Page($f);
        $title = $p->title ?? $t;
        $id = \md5($m[3] . $m[4]) . '-' . \uniqid(); // Unique ID
        $u = \strtr($m[3] ? $url . (0 === \strpos($m[3], '/') ? $m[3] : $u . '/' . $m[3]) . $m[4] . ' "' . \To::text($title) . '"' : $url . $u . $m[4], \DS, '/');
        return '[' . ($m[1] ?: $title) . '](' . $u . ')';
    }, $content);
}

\Hook::set([
    'page.content',
    'page.description'
], __NAMESPACE__ . "\\link", 1.9); // Make sure to run before `_\lot\x\markdown` hook