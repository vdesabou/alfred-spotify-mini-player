#!/bin/ksh

SILENT="$1"
PATH=$PATH:/opt/homebrew/bin/php:/usr/local/bin/

if [ "$SILENT" = "false" ]
then
	php -f ./src/action.php -- "" "DOWNLOAD_ARTWORKS" "DOWNLOAD_ARTWORKS"
else
	php -f ./src/action.php -- "" "DOWNLOAD_ARTWORKS_SILENT" "DOWNLOAD_ARTWORKS_SILENT"
fi