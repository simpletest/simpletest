#!/bin/sh
Xalan -o ../docs/pkg/QuickStart.pkg ../docs/source/simple_test.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/Overview.pkg ../docs/source/overview.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/UnitTestCase.pkg ../docs/source/unit_test_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/GroupTests.pkg ../docs/source/group_test_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/ServerStubs.pkg ../docs/source/server_stubs_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/MockObjects.pkg ../docs/source/mock_objects_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/PartialMock.pkg ../docs/source/partial_mocks_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/Reporting.pkg ../docs/source/reporter_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/TestRunner.pkg ../docs/source/runner_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/Expectations.pkg ../docs/source/expectation_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/WebTester.pkg ../docs/source/web_tester_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/FormTesting.pkg ../docs/source/form_testing_documentation.xml phpdoc_docs.xslt
Xalan -o ../docs/pkg/Authentication.pkg ../docs/source/authentication_documentation.xml phpdoc_docs.xslt
