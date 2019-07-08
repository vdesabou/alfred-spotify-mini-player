#!/bin/ksh

title="$1"
theme_color="$2"
subtitle="$3"
alfred_name="$4"

actions=""
if [[ "${title}" == Now* ]]
then
	actions="-actions Next,Pause,Lyrics,Add,Share"
fi

APPICON="/tmp/tmp_"$(whoami)
ANSWER=$(./alerter -title "${title}" -sender "com.spotify.miniplayer.${theme_color}" -appIcon "${APPICON}" -message "${subtitle}" -closeLabel Close ${actions} -timeout 10 -group com.spotify.miniplayer -remove com.spotify.miniplayer)
case ${ANSWER} in
    "@TIMEOUT") echo "Timeout man, sorry" ;;
    "@CLOSED") echo "You clicked on the default alert' close button" ;;
    "@CONTENTCLICKED") open -a "Spotify" ;;
    #"@ACTIONCLICKED") echo "You clicked the alert default action button" ;;
    "Next") 
		osascript -e "tell application id \"$alfred_name\" to run trigger \"next\" in workflow \"com.vdesabou.spotify.mini.player\" with argument \"test\""
		;;
    "Pause") 
		osascript -e "tell application id \"$alfred_name\" to run trigger \"playpause\" in workflow \"com.vdesabou.spotify.mini.player\" with argument \"test\""
		;;
    "Lyrics") 
		osascript -e "tell application id \"$alfred_name\" to run trigger \"lyrics\" in workflow \"com.vdesabou.spotify.mini.player\" with argument \"test\"" 
		;;
    "Add") 
		osascript -e "tell application id \"$alfred_name\" to run trigger \"add_current_track_to\" in workflow \"com.vdesabou.spotify.mini.player\" with argument \"test\""
		;;
    "Share") 
		osascript -e "tell application id \"$alfred_name\" to run trigger \"share\" in workflow \"com.vdesabou.spotify.mini.player\" with argument \"test\""
		;;
    **) 
		echo "? --> $ANSWER" 
		;;
esac
