#!/bin/ksh

SILENT="$1"
PATH=$PATH:/opt/homebrew/bin/:/usr/local/bin/

if [ "$SILENT" = "false" ]
then
	php -f ./src/download_artworks.php -- "DOWNLOAD_ARTWORKS"
else
	php -f ./src/download_artworks.php -- "DOWNLOAD_ARTWORKS_SILENT"
fi