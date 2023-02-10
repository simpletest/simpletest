#!/bin/bash

# building the old docs requires xsltproc
# apt install xsltproc

mkdir -p ../out/docs

# transform xml to html
( cd docs ; ./transform_docs.sh)