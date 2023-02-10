#!/bin/bash

mkdir -p ../out/simpletest.org

# copy static website data
cp -R website/simpletest.org-static/* ../out/simpletest.org

# build website using xml data from "/docs/source"
php website/index.php