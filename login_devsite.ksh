#!/bin/sh
set -x


####################### PARAMÈTRES SPOTIFY #######################
username="vdesabou"
password="7NgutKUZov,wVaRJAhb9Y"

urladminsite=http://developer.spotify.com

tab=Import # Onglet auquel vous voulez accéder
admintab='Admin'$tab # Les script de prestashop vont chercher les onglets avec Admin devant
tab_declaison=ImportProductsAttributs
admintab_declinaison='Admin'$tab_declaison

APPS_FILE=/tmp/apps.html
LOGIN_FILE=/tmp/login.html
COOKIES_FILE=/tmp/cookies.txt


BUGURL="https://developer.spotify.com"
BUGUSERNAME="vdesabou"
BUGPASSWORD="7NgutKUZov,wVaRJAhb9Y"
COOKIE="cookies-dev-spotify"

	curl -m 120 -k --cookie-jar ${COOKIES_FILE} --data username=${BUGUSERNAME=} \
		-o $LOGIN_FILE -s \
		--data password=${BUGPASSWORD} \
		--data submit="Log In" \
		${BUGURL}/login
		
	exit 0	

####################### IDENTIFICATION #######################
# Identification et récupération du cookie
/usr/local/bin/wget --save-cookies=$COOKIES_FILE --post-data='username='$username'&password='$password'&submit=Log In' --keep-session-cookies -q -O $LOGIN_FILE $urladminsite'/login'
if [ -n "$(grep 'error' $LOGIN_FILE)" ]
then
  error=$(grep '<li>.*</li>' $LOGIN_FILE | sed 's/.*<li>\(.*\)<\/li>/\1/g')
  echo 'ERREUR : '$error
else

  echo 'ok'
  
  
  # Si pas d'erreur premier accès à la partie administration
  /usr/local/bin/wget --load-cookies=$COOKIES_FILE --keep-session-cookies -q -O $APPS_FILE $urladminsite'/technologies/apps/' # Accès à la partie /technologies/apps/

fi
exit 0

####################### IMPORTATION CSV #######################
if [ -n "$(echo $token_import | grep ^[a-z0-9]*)" ]
then
 	/usr/local/bin/wget --load-cookies=$COOKIES_FILE --keep-session-cookies --post-data='tab='$admintab'&token='$token_import'&skip=1&csv='$csvfile'&entity=1&iso_lang=fr&separator=;&multiple_value_separator=,'$typevalue'&import=Import CSV data' -q -O $MAJ_FILE $urladminsite'index.php'

	if [ -f $DECLINAISON_CSV_FILE ]
	then
		/usr/local/bin/wget --load-cookies=$COOKIES_FILE --keep-session-cookies --post-data='tab='$admintab_declinaison'&token='$token_declinaison'&productsattributes=20130402072907products_attributes.csv&submitImportProductsAttributes=Importer' -q -O $MAJ_DECLINAISON_FILE $urladminsite'index.php'
	fi
   
  echo "SUCCESS"
else
  echo "ERREUR : Pas de token"
fi

exit 0
