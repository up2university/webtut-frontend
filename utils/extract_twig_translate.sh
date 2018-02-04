#!/bin/bash

cd "$(dirname ${BASH_SOURCE[0]})"
pwd

echo "Updating Cache..."
php make_php_cache_files.php

for lang in en_GB pt_PT de_DE fr_FR nl_NL es_ES it_IT hu_HU nb_NO
do
  echo "Building PO"
  
  xgettext --default-domain=messages -p ../locale/$lang --from-code=UTF-8 -n --omit-header -L PHP `find ../cache/ -name '*.php'`
  
  msgmerge " + po + " translations/messages.pot
  
  echo "Buidling MO"
  msgfmt ../locale/$lang/messages.po -o ../locale/$lang/messages.mo
done

cd -
