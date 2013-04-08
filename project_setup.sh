#/bin/bash

p=$(pwd)
cd $(dirname $0)

rm -rf .git

git init
git add .
git commit -m "Initial import."

cd $p

echo "Project Setup Complete"
