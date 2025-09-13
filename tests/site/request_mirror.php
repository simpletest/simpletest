<?php
// A single request mirror script for SimpleTest acceptance tests.
// Returns HTML for browser-like requests and JSON for programmatic requests.
// https://github.com/simpletest/simpletest/issues/19
require_once __DIR__ . '/page_request.php';

if (!\function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (\str_starts_with($name, 'HTTP_')) {
                $header           = \str_replace(' ', '-', \ucwords(\strtolower(\str_replace('_', ' ', \substr($name, 5)))));
                $headers[$header] = $value;
            }
        }

        return $headers;
    }
}

$scheme = (!empty($_SERVER['HTTPS']) && \strtolower($_SERVER['HTTPS']) !== 'off') ? 'https://' : 'http://';
$host   = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
$path   = $_SERVER['PHP_SELF'] ?? '';
$url    = $scheme . $host . $path;

if (!empty($_SERVER['QUERY_STRING'])) {
    $url .= '?' . $_SERVER['QUERY_STRING'];
}

$payload = [
    'body'    => \file_get_contents('php://input'),
    'headers' => getallheaders(),
    'origin'  => $_SERVER['REMOTE_ADDR'] ?? null,
    'method'  => $_SERVER['REQUEST_METHOD'] ?? null,
    'params'  => [
        'get'  => PageRequest::get(),
        'post' => PageRequest::post(),
    ],
    'url' => $url,
];

$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
// If the client doesn't send an Accept header, treat it as a browser request
// and return HTML. Also return HTML for explicit text/html or */*.
$wantsHtml = $accept === '' || \stripos($accept, 'text/html') !== false || \stripos($accept, '*/*') !== false;

$title = 'Simple test target file';

if ($wantsHtml) {
    // Render a simple HTML page similar to the old network_confirm.php
    \header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!doctype html>
    <html>
    <head><title><?php print \htmlspecialchars($title); ?></title></head>
    <body>
        A target for the SimpleTest test suite.
        <h1>Request</h1>
        <dl>
            <dt>Protocol version</dt><dd><?php print \htmlspecialchars($_SERVER['SERVER_PROTOCOL'] ?? ''); ?></dd>
            <dt>Request method</dt><dd><?php print \htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? ''); ?></dd>
            <dt>Accept header</dt><dd><?php print \htmlspecialchars($_SERVER['HTTP_ACCEPT'] ?? ''); ?></dd>
        </dl>
        <h1>Cookies</h1>
        <?php
            if (!empty($_COOKIE)) {
                foreach ($_COOKIE as $key => $value) {
                    print \htmlspecialchars($key) . '=[' . \htmlspecialchars($value) . "]<br />\n";
                }
            }
    ?>
        <h1>Raw GET data</h1>
        <?php if (!empty($_SERVER['QUERY_STRING'])) {
            print '[' . \htmlspecialchars($_SERVER['QUERY_STRING']) . ']';
        } ?>
        <h1>GET data</h1>
        <?php
            $get = PageRequest::get();

    if (\count($get) > 0) {
        foreach ($get as $k => $v) {
            if (\is_array($v)) {
                $v = \implode(', ', $v);
            }
            print \htmlspecialchars($k) . '=[' . \htmlspecialchars((string) $v) . "]<br />\n";
        }
    }
    ?>
        <h1>Dump of $_GET data</h1>
        <pre><?php \print_r($_GET); ?></pre>
        <h1>Raw POST data</h1>
        <?php print '[' . \htmlspecialchars(\file_get_contents('php://input')) . ']'; ?>
        <pre><?php \print_r(PageRequest::post()); ?></pre>
        <h1>POST data</h1>
        <?php
        if (!empty($_POST)) {
            function show_array_value($array)
            {
                $html = '';

                foreach ($array as $key => $value) {
                    $html .= \htmlspecialchars($key) . '=[';

                    if (\is_array($value)) {
                        $html .= show_array_value($value);
                    } else {
                        $html .= \htmlspecialchars((string) $value);
                    }
                    $html .= ']';
                }

                return $html;
            }

            print show_array_value($_POST) . "<br />\n";
        }
    ?>
    </body>
    </html>
    <?php
} else {
    \header('Content-Type: application/json; charset=utf-8');
    print \json_encode($payload);
}
