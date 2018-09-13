#!/bin/ksh

title="$1"
theme_color="$2"
subtitle="$3"

actions=""
if [[ "${title}" == Now* ]]
then
	actions="-actions ⏭Next,⏸Pause,🎤Lyrics,➕Add,🔗Share"
fi

ANSWER=$(./alerter -title "${title}" -sender "com.spotify.miniplayer.${theme_color}" -appIcon "/tmp/tmp" -message "${subtitle}" -timeout 10 -closeLabel Close ${actions})
case ${ANSWER} in
    "@TIMEOUT") echo "Timeout man, sorry" ;;
    "@CLOSED") echo "You clicked on the default alert' close button" ;;
    "@CONTENTCLICKED") open -a "Spotify" ;;
    #"@ACTIONCLICKED") echo "You clicked the alert default action button" ;;
    "⏭Next") 
		osascript -e 'tell application "Alfred 3" to run trigger "next" in workflow "com.vdesabou.spotify.mini.player" with argument "test"' 
		;;
    "⏸XPause") 
		osascript -e 'tell application "Alfred 3" to run trigger "playpause" in workflow "com.vdesabou.spotify.mini.player" with argument "test"' 
		;;
    "🎤Lyrics") 
		osascript -e 'tell application "Alfred 3" to run trigger "lyrics" in workflow "com.vdesabou.spotify.mini.player" with argument "test"' 
		;;
    "➕Add") 
		osascript -e 'tell application "Alfred 3" to run trigger "add_current_track_to" in workflow "com.vdesabou.spotify.mini.player" with argument "test"' 
		;;
    "🔗Share") 
		osascript -e 'tell application "Alfred 3" to run trigger "share" in workflow "com.vdesabou.spotify.mini.player" with argument "test"' 
		;;
    **) 
		echo "? --> $ANSWER" 
		;;
esac
