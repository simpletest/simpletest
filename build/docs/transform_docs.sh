#!/bin/bash
xsltproc bundled_docs.xslt source/simple_test.xml > ../../out/docs/index.html
xsltproc bundled_docs.xslt source/overview.xml > ../../out/docs/overview.html
xsltproc bundled_docs.xslt source/unit_test_documentation.xml > ../../out/docs/unit_test_documentation.html
xsltproc bundled_docs.xslt source/group_test_documentation.xml > ../../out/docs/group_test_documentation.html
xsltproc bundled_docs.xslt source/mock_objects_documentation.xml > ../../out/docs/mock_objects_documentation.html
xsltproc bundled_docs.xslt source/partial_mocks_documentation.xml > ../../out/docs/partial_mocks_documentation.html
xsltproc bundled_docs.xslt source/reporter_documentation.xml > ../../out/docs/reporter_documentation.html
xsltproc bundled_docs.xslt source/expectation_documentation.xml > ../../out/docs/expectation_documentation.html
xsltproc bundled_docs.xslt source/web_tester_documentation.xml > ../../out/docs/web_tester_documentation.html
xsltproc bundled_docs.xslt source/form_testing_documentation.xml > ../../out/docs/form_testing_documentation.html
xsltproc bundled_docs.xslt source/authentication_documentation.xml > ../../out/docs/authentication_documentation.html
xsltproc bundled_docs.xslt source/browser_documentation.xml > ../../out/docs/browser_documentation.html
