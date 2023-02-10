#!/bin/bash
xsltproc bundled_docs.xslt source/simple_test.xml > ../docs/index.html
xsltproc bundled_docs.xslt source/overview.xml > ../docs/overview.html
xsltproc bundled_docs.xslt source/unit_test_documentation.xml > ../docs/unit_test_documentation.html
xsltproc bundled_docs.xslt source/group_test_documentation.xml > ../docs/group_test_documentation.html
xsltproc bundled_docs.xslt source/mock_objects_documentation.xml > ../docs/mock_objects_documentation.html
xsltproc bundled_docs.xslt source/partial_mocks_documentation.xml > ../docs/partial_mocks_documentation.html
xsltproc bundled_docs.xslt source/reporter_documentation.xml > ../docs/reporter_documentation.html
xsltproc bundled_docs.xslt source/expectation_documentation.xml > ../docs/expectation_documentation.html
xsltproc bundled_docs.xslt source/web_tester_documentation.xml > ../docs/web_tester_documentation.html
xsltproc bundled_docs.xslt source/form_testing_documentation.xml > ../docs/form_testing_documentation.html
xsltproc bundled_docs.xslt source/authentication_documentation.xml > ../docs/authentication_documentation.html
xsltproc bundled_docs.xslt source/browser_documentation.xml > ../docs/browser_documentation.html
