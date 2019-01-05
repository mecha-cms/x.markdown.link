<?php namespace fn\markdown;

function link($content = "", array $lot = []) {
    if ($this->type !== 'Markdown') {
        return $content;
    }
    if (strpos($content, '[link:') === false) {
        return $content;
    }
    global $language, $url;
    return preg_replace_callback('#(?:\[([^][]*?)\])?\[link:((?:\.{2}/)*|\.{2})([\w/-]*?)([?&\#].*?)?\]#', function($m) use($language, $lot, $url) {
        if (!$path = $this->path) {
            return $m[0];
        }
        $u = rtrim(str_replace(PAGE . DS, "", dirname($path) . DS), DS);
        if (!empty($m[2])) {
            if ($m[2] === '..' && empty($m[3])) {
                $u = \Path::D($u);
                $m[2] = "";
            } else if (($i = substr_count($m[2], '../')) !== 0) {
                $u = \Path::D($u, $i);
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
        $f = \File::exist([
            $p . '.page',
            $p . '.archive'
        ], null);
        if ($m[3] && !$f) {
            \Hook::fire('on.' . basename(__DIR__) . '.x', [$m, $lot], $this);
            return \HTML::s($m[1] ?: $language->link_broken, [
                'title' => $m[1] ? $language->link_broken : $m[0],
                'style[]' => ['color' => '#f00']
            ]);
        }
        $t = \To::title(\Path::B($m[2]));
        $p = new \Page($f);
        $title = $p->get('title', $t);
        $id = md5($m[3] . $m[4]) . '-' . uniqid(); // Unique ID
        $u = $m[3] ? $url . \To::URL(strpos($m[3], '/') === 0 ? $m[3] : $u . '/' . $m[3]) . $m[4] . ' "' . \To::text($title) . '"' : $url . \To::URL($u) . $m[4];
        return '[' . ($m[1] ?: $title) . '](' . $u . ')';
    }, $content);
}

\Hook::set(['*.content', '*.description'], __NAMESPACE__ . "\\link", 1.9); // Make sure to run before `fn\markdown` hook