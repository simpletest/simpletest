#!/bin/sh

# Builds project release.
#
cd ../..

NAME=php_simpletest_`cat simpletest/VERSION`.tar.gz
FILES=(simpletest/errors.php \
          simpletest/options.php \
          simpletest/dumper.php \
          simpletest/expectation.php \
          simpletest/socket.php \
          simpletest/encoding.php \
          simpletest/url.php \
          simpletest/http.php \
          simpletest/authentication.php \
          simpletest/user_agent.php \
          simpletest/browser.php \
          simpletest/parser.php \
          simpletest/tag.php \
          simpletest/form.php \
          simpletest/frames.php \
          simpletest/page.php \
          simpletest/remote.php \
          simpletest/runner.php \
          simpletest/scorer.php \
          simpletest/reporter.php \
          simpletest/mock_objects.php \
          simpletest/simple_test.php \
          simpletest/unit_tester.php \
          simpletest/web_tester.php \
          simpletest/shell_tester.php \
          simpletest/xml.php \
          simpletest/extensions/pear_test_case.php \
          simpletest/extensions/phpunit_test_case.php \
          simpletest/README \
          simpletest/VERSION \
          simpletest/LICENSE \
          simpletest/HELP_MY_TESTS_DONT_WORK_ANYMORE \
          simpletest/test/all_tests.php \
          simpletest/test/unit_tests.php \
          simpletest/test/acceptance_test.php \
          simpletest/test/errors_test.php \
          simpletest/test/options_test.php \
          simpletest/test/dumper_test.php \
          simpletest/test/expectation_test.php \
          simpletest/test/adapter_test.php \
          simpletest/test/socket_test.php \
          simpletest/test/url_test.php \
          simpletest/test/encoding_test.php \
          simpletest/test/http_test.php \
          simpletest/test/authentication_test.php \
          simpletest/test/user_agent_test.php \
          simpletest/test/browser_test.php \
          simpletest/test/parser_test.php \
          simpletest/test/tag_test.php \
          simpletest/test/form_test.php \
          simpletest/test/frames_test.php \
          simpletest/test/page_test.php \
          simpletest/test/remote_test.php \
          simpletest/test/simple_mock_test.php \
          simpletest/test/visual_test.php \
          simpletest/test/shell_test.php \
          simpletest/test/web_tester_test.php \
          simpletest/test/unit_tester_test.php \
          simpletest/test/shell_tester_test.php \
          simpletest/test/xml_test.php \
          simpletest/test/live_test.php \
          simpletest/test/real_sites_test.php \
          simpletest/test/parse_error_test.php
          simpletest/test/test_with_parse_error.php
          simpletest/docs/en/docs.css \
          simpletest/docs/en/index.html \
          simpletest/docs/en/overview.html \
          simpletest/docs/en/unit_test_documentation.html \
          simpletest/docs/en/group_test_documentation.html \
          simpletest/docs/en/server_stubs_documentation.html \
          simpletest/docs/en/mock_objects_documentation.html \
          simpletest/docs/en/partial_mocks_documentation.html \
          simpletest/docs/en/reporter_documentation.html \
          simpletest/docs/en/expectation_documentation.html \
          simpletest/docs/en/web_tester_documentation.html \
          simpletest/docs/en/form_testing_documentation.html \
          simpletest/docs/en/authentication_documentation.html \
          simpletest/docs/en/browser_documentation.html)

tar -zcf $NAME ${FILES[*]}
