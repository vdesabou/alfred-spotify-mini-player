#!/bin/ksh 

set -x

QUERY="$1"
TYPE="$2"

# query is csv form: track_uri|album_uri|artist_uri|playlist_uri|spotify_command|max_results|other_action

track_uri=$(echo "${QUERY}" | cut -f1 -d"|")
album_uri=$(echo "${QUERY}" | cut -f2 -d"|")
artist_uri=$(echo "${QUERY}" | cut -f3 -d"|")
playlist_uri=$(echo "${QUERY}" | cut -f4 -d"|")
spotify_command=$(echo "${QUERY}" | cut -f5 -d"|")
original_query=$(echo "${QUERY}" | cut -f6 -d"|")
max_results=$(echo "${QUERY}" | cut -f7 -d"|")
other_action=$(echo "${QUERY}" | cut -f8 -d"|")


if [ "${TYPE}" = "TRACK" ]
then
if [ "-${track_uri}-" != "--" ]
then
osascript <<EOT
tell application "Spotify"
	open location "${track_uri}"
end tell
EOT
fi
elif [ "${TYPE}" = "ALBUM" ]
then
osascript <<EOT
tell application "Spotify"
	open location "spotify:app:miniplayer:playartistoralbum:${album_uri}"
	#open location "${album_uri}"
end tell
EOT
elif [ "${TYPE}" = "ARTIST" ]
then
osascript <<EOT
tell application "Spotify"
	open location "spotify:app:miniplayer:playartistoralbum:${artist_uri}"
	#open location "${artist_uri}"
end tell
EOT
fi

# playlist
if [ "-${playlist_uri}-" != "--" ]
then
osascript <<EOT
tell application "Spotify"
	open location "spotify:app:miniplayer:startplaylist:${playlist_uri}"
	#open location "${playlist_uri}"
end tell
EOT
elif [ "-${spotify_command}-" != "--" ]
then
osascript <<EOT
tell application "Spotify"
	${spotify_command}
end tell
EOT
elif [ "-${max_results}-" != "--" ]
then
php -f set_max_results.php -- "${max_results}"
elif [ "-${other_action}-" != "--" ]
then
	if [ "${other_action}" == "cache" ]
	then
		php -f download_all_artworks.php	
	elif [ "${other_action}" == "clear" ]
	then
		php -f clear.php
	elif [ "${other_action}" == "disable_all_playlist" ]
	then
		php -f set_all_playlists.php -- "false"
	elif [ "${other_action}" == "enable_all_playlist" ]
	then
		php -f set_all_playlists.php -- "true"
	elif [ "${other_action}" == "disable_spotifiuous" ]
	then
		php -f set_spotifious.php -- "false"
	elif [ "${other_action}" == "enable_spotifiuous" ]
	then
		php -f set_spotifious.php -- "true"
	elif [ "${other_action}" == "open_spotify_export_app" ]
	then
osascript <<EOT
tell application "Spotify"
	activate
	open location "spotify:app:miniplayer"
end tell
EOT
	elif [ "${other_action}" == "update_library_json" ]
	then
		php -f update_library.php

		oldIFS="$IFS"
		IFS=$'\n'
		NVPREFS="${HOME}/Library/Application Support/Alfred 2/Workflow Data/"
		BUNDLEID=$(/usr/libexec/PlistBuddy  -c "Print :bundleid" "info.plist")
		DATADIR="${NVPREFS}${BUNDLEID}"
			
		if [ -f ${DATADIR}/library.json ]
		then
			cp ${DATADIR}/library.json ${DATADIR}/library.json.bak
			sed "s/&amp;/\&/g" ${DATADIR}/library.json.bak > ${DATADIR}/library.json
			cp ${DATADIR}/library.json ${DATADIR}/library.json.bak
			sed "s/&apos;/'/g" ${DATADIR}/library.json.bak > ${DATADIR}/library.json
			rm ${DATADIR}/library.json.bak
			
			# cleanup all json 
			rm -f ${DATADIR}/library_starred_playlist.json
			rm -f ${DATADIR}/playlist*.json
			# create one json file per playlist
			php -f create_playlists.php
	
			cp ${DATADIR}/playlists.json ${DATADIR}/playlists.json.bak
			sed "s/&amp;/\&/g" ${DATADIR}/playlists.json.bak > ${DATADIR}/playlists.json
			cp ${DATADIR}/playlists.json ${DATADIR}/playlists.json.bak
			sed "s/&apos;/'/g" ${DATADIR}/playlists.json.bak > ${DATADIR}/playlists.json
			rm ${DATADIR}/playlists.json.bak
		fi
		IFS="$oldIFS"
	fi 
elif [ "-${original_query}-" != "--" ]
then
osascript <<EOT
tell application "Alfred 2" to search "spot ${original_query}"
EOT
fi
