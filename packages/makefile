# Builds project release.
#
VERSION = 1.0beta4
NAME = php_simple_test_$(VERSION).tar.gz
SCRIPTS = simpletest/errors.php \
          simpletest/options.php \
          simpletest/runner.php \
          simpletest/dumper.php \
          simpletest/expectation.php \
          simpletest/socket.php \
          simpletest/query_string.php \
          simpletest/http.php \
          simpletest/user_agent.php \
          simpletest/browser.php \
          simpletest/parser.php \
          simpletest/tag.php \
          simpletest/frames.php \
          simpletest/page.php \
          simpletest/remote.php \
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
          simpletest/test/boundary_tests.php \
          simpletest/test/errors_test.php \
          simpletest/test/options_test.php \
          simpletest/test/dumper_test.php \
          simpletest/test/expectation_test.php \
          simpletest/test/adapter_test.php \
          simpletest/test/socket_test.php \
          simpletest/test/query_string_test.php \
          simpletest/test/http_test.php \
          simpletest/test/user_agent_test.php \
          simpletest/test/browser_test.php \
          simpletest/test/parser_test.php \
          simpletest/test/tag_test.php \
          simpletest/test/frames_test.php \
          simpletest/test/page_test.php \
          simpletest/test/remote_test.php \
          simpletest/test/simple_mock_test.php \
          simpletest/test/visual_test.php \
          simpletest/test/shell_test.php \
          simpletest/test/shell_tester_test.php \
          simpletest/test/xml_test.php \
          simpletest/test/live_test.php \
          simpletest/test/real_sites_test.php \
          simpletest/docs/index.html \
          simpletest/docs/overview.html \
          simpletest/docs/unit_test_documentation.html \
          simpletest/docs/group_test_documentation.html \
          simpletest/docs/reporter_documentation.html \
          simpletest/docs/server_stubs_documentation.html \
          simpletest/docs/mock_objects_documentation.html \
          simpletest/docs/partial_mocks_documentation.html \
          simpletest/docs/expectation_documentation.html \
          simpletest/docs/web_tester_documentation.html \
          simpletest/docs/form_testing_documentation.html

release: $(NAME)

$(NAME): $(SCRIPTS)
	tar -zcf $(NAME) $(SCRIPTS)

