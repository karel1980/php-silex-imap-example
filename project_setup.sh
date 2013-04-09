#!/bin/bash

p=$(pwd)
cd $(dirname $0)

rm -rf .git

git init
echo '!.gitignore' > vendor/.gitignore
git add .
git status
git commit -m "Initial import."
echo '' >> vendor/.gitignore
echo '*' >> vendor/.gitignore
git commit -am "Preventing vendor contents from being committed."

cd $p

echo "Project Setup Complete"
