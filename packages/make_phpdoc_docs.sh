#!/bin/sh
Xalan -o ../docs/pkg/QuickStart.pkg ../docs/source/en/simple_test.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/Overview.pkg ../docs/source/en/overview.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/UnitTestCase.pkg ../docs/source/en/unit_test_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/GroupTests.pkg ../docs/source/en/group_test_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/ServerStubs.pkg ../docs/source/en/server_stubs_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/MockObjects.pkg ../docs/source/en/mock_objects_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/PartialMock.pkg ../docs/source/en/partial_mocks_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/Reporting.pkg ../docs/source/en/reporter_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/Expectations.pkg ../docs/source/en/expectation_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/WebTester.pkg ../docs/source/en/web_tester_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/FormTesting.pkg ../docs/source/en/form_testing_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/Authentication.pkg ../docs/source/en/authentication_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/Browser.pkg ../docs/source/en/browser_documentation.xml phpdoc_docs.xslt
