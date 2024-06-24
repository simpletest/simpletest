        <h1>Request</h1>
        <dl>
            <dt>Protocol version</dt><dd><?php print $_SERVER['SERVER_PROTOCOL']; ?></dd>
            <dt>Request method</dt><dd><?php print $_SERVER['REQUEST_METHOD']; ?></dd>
            <dt>Accept header</dt><dd><?php print $_SERVER['HTTP_ACCEPT'] ?? ''; ?></dd>
        </dl>
        <h1>Cookies</h1>
        <?php
            if ($_COOKIE !== []) {
                foreach ($_COOKIE as $key => $value) {
                    print "{$key}=[{$value}]<br />\n";
                }
            }
            ?>
        <h1>Raw GET data</h1>
        <?php
                if (!empty($_SERVER['QUERY_STRING'])) {
                    print '[' . $_SERVER['QUERY_STRING'] . ']';
                }
            ?>
        <h1>GET data</h1>
        <?php
                $get = PageRequest::get();

            if (\count($get) > 0) {
                foreach ($get as $key => $value) {
                    if (\is_array($value)) {
                        $value = \implode(', ', $value);
                    }
                    print "{$key}=[{$value}]<br />\n";
                }
            }
            ?>
        <h1>Raw POST data</h1>
        <?php
                print '[' . \file_get_contents('php://input') . ']';
            ?>
        <pre><?php \print_r(PageRequest::post()); ?></pre>
        <h1>POST data</h1>
        <?php
                if ($_POST !== []) {
                    foreach ($_POST as $key => $value) {
                        print $key . '=[';

                        if (\is_array($value)) {
                            print \implode(', ', $value);
                        } else {
                            print $value;
                        }
                        print "]<br />\n";
                    }
                }
            ?>
