<?php declare(strict_types=1);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'HEAD':
        \header('HTTP/1.1 202 Accepted');

        break;

    case 'DELETE':
        \header('HTTP/1.1 202 Accepted');
        print 'Your delete request was accepted.';

        break;

    case 'POST':
    case 'PUT':
        $acceptedContentTypes = ['text/xml', 'application/xml'];

        if (
            (isset($_SERVER['CONTENT_TYPE']) && \in_array($_SERVER['CONTENT_TYPE'], $acceptedContentTypes, true)) ||
            // https://bugs.php.net/bug.php?id=66606
            \in_array($_SERVER['HTTP_CONTENT_TYPE'], $acceptedContentTypes, true)
        ) {
            $data    = \fopen('php://input', 'r');
            $content = '';

            while ($chunk = \fread($data, 1024)) {
                $content .= $chunk;
            }
            \fclose($data);

            if ('<a><b>c</b></a>' === $content) {
                \header('HTTP/1.1 201 Created');
                \header('Content-Type: text/xml');
                print \strip_tags($content);
            }
        } else {
            \header('HTTP/1.1 406 Invalid Encoding');
            \header('Content-Type: text/plain');
            print 'Please ensure content type is an XML format.';
        }

        break;

    default:
        \header('HTTP/1.1 405 Method Not Allowed');
        \header('Content-Type: text/plain');
        print 'Method Not Allowed';

        break;
}
