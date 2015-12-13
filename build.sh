#!/bin/bash
set -e
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ZIP="$( dirname "${DIR}" )"/darwinpricing-oscommerce.zip
rm -f ${ZIP}
cd $TMPDIR
rm -rf darwinpricing-oscommerce && mkdir darwinpricing-oscommerce && cd darwinpricing-oscommerce
cp -r ${DIR} ./
rm -rf darwinpricing-oscommerce/.git darwinpricing-oscommerce/.gitignore darwinpricing-oscommerce/nbproject darwinpricing-oscommerce/build.sh
zip -r -X ${ZIP} darwinpricing-oscommerce
echo Created ${ZIP}
