#!/bin/sh
Xalan -o ../docs/index.html ../docs/source/simple_test.xml bundled_docs.xslt
Xalan -o ../docs/overview.html ../docs/source/overview.xml bundled_docs.xslt
Xalan -o ../docs/unit_test_documentation.html ../docs/source/unit_test_documentation.xml bundled_docs.xslt
Xalan -o ../docs/group_test_documentation.html ../docs/source/group_test_documentation.xml bundled_docs.xslt
Xalan -o ../docs/server_stubs_documentation.html ../docs/source/server_stubs_documentation.xml bundled_docs.xslt
Xalan -o ../docs/mock_objects_documentation.html ../docs/source/mock_objects_documentation.xml bundled_docs.xslt
Xalan -o ../docs/partial_mocks_documentation.html ../docs/source/partial_mocks_documentation.xml bundled_docs.xslt
Xalan -o ../docs/reporter_documentation.html ../docs/source/reporter_documentation.xml bundled_docs.xslt
Xalan -o ../docs/expectation_documentation.html ../docs/source/expectation_documentation.xml bundled_docs.xslt
Xalan -o ../docs/web_tester_documentation.html ../docs/source/web_tester_documentation.xml bundled_docs.xslt
Xalan -o ../docs/form_testing_documentation.html ../docs/source/form_testing_documentation.xml bundled_docs.xslt
