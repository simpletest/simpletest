#!/bin/sh
Xalan -o ../docs/en/index.html ../docs/source/en/simple_test.xml bundled_docs.xslt
Xalan -o ../docs/en/overview.html ../docs/source/en/overview.xml bundled_docs.xslt
Xalan -o ../docs/en/unit_test_documentation.html ../docs/source/en/unit_test_documentation.xml bundled_docs.xslt
Xalan -o ../docs/en/group_test_documentation.html ../docs/source/en/group_test_documentation.xml bundled_docs.xslt
Xalan -o ../docs/en/server_stubs_documentation.html ../docs/source/en/server_stubs_documentation.xml bundled_docs.xslt
Xalan -o ../docs/en/mock_objects_documentation.html ../docs/source/en/mock_objects_documentation.xml bundled_docs.xslt
Xalan -o ../docs/en/partial_mocks_documentation.html ../docs/source/en/partial_mocks_documentation.xml bundled_docs.xslt
Xalan -o ../docs/en/reporter_documentation.html ../docs/source/en/reporter_documentation.xml bundled_docs.xslt
Xalan -o ../docs/en/expectation_documentation.html ../docs/source/en/expectation_documentation.xml bundled_docs.xslt
Xalan -o ../docs/en/web_tester_documentation.html ../docs/source/en/web_tester_documentation.xml bundled_docs.xslt
Xalan -o ../docs/en/form_testing_documentation.html ../docs/source/en/form_testing_documentation.xml bundled_docs.xslt
Xalan -o ../docs/en/authentication_documentation.html ../docs/source/en/authentication_documentation.xml bundled_docs.xslt
Xalan -o ../docs/en/browser_documentation.html ../docs/source/en/browser_documentation.xml bundled_docs.xslt
