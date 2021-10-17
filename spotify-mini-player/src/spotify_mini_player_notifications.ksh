#!/bin/ksh

DATADIR=""
ACTION=""
APP=""
ALFRED_NAME=""

while getopts 'v:d:a:m:' arguments
	do
	  case ${arguments} in
		d)
			DATADIR="${OPTARG}"
			;;
		a)
			ACTION="${OPTARG}"
			;;
		m)
			APP="${OPTARG}"
			;;
		v)
			ALFRED_NAME="${OPTARG}"
			;;
		\?)
			print "ERROR: ${OPTARG} is not a valid option"
			print "Usage: $0 -d <data dir> -a <action> -m <mopidy server:port> -v <alfred_name>"
			exit 1;;
	  esac
	done

function traceit
{
	datestr=$(date -u '+%Y-%m-%d %H:%M:%S');
	print "${datestr}|${1}";
}

function StartAppleScript
{
osascript <<EOT
try
	tell application "Spotify"

		set current_track_url to ""
		set old_player_state to ""

		repeat until application "Spotify" is not running
			set track_url to spotify url of current track

			if the player state is stopped then
				set player_state to "stopped"
				try
					if current track is not missing value then set player_state to "paused"
				end try
			else if the player state is paused then
				set player_state to "paused"
			else
				set player_state to "playing"
			end if

			if track_url ≠ current_track_url then
				set current_track_url to spotify url of current track

				tell application id "${ALFRED_NAME}"
					run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument "applescript_track $$"
				end tell
			end if

			if old_player_state ≠ "" and player_state ≠ old_player_state and player_state is "playing" then
				tell application id "${ALFRED_NAME}"
					run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument "applescript_state $$"
				end tell
			end if

			set old_player_state to player_state
			delay 3
		end repeat
	end tell
on error error_message
	return
end try
EOT
}

function StartMopidy
{
	result=""
	query=$(php -r '$foo = serialize(array("", "", "", "", "", "", "", "current_mopidy" /* other_action */, "", "", "", "", "", "", "", "" , "", "", "", "", "", ""));echo $foo;')
	current_track_url=""
	old_player_state=""
	player_state=""
	track_url=""

	until [ "${result}" == "mopidy_stopped" ]
	do
		result=$(php -f ./src/action.php -- "$query" "TRACK" "")

		track_url=$(echo "${result}" | awk -F '▹' '{print $5}')
		player_state=$(echo "${result}" | awk -F '▹' '{print $4}')

		if [ "${track_url}" != "${current_track_url}" ]
		then
			current_track_url=$(echo "${result}" | awk -F '▹' '{print $5}')
			cmd='tell application id "com.runningwithcrayons.Alfred-3" to run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument "mopidy_track"'
			if [ "${ALFRED_NAME}" == "com.runningwithcrayons.Alfred" ]
			then
				cmd='tell application id "com.runningwithcrayons.Alfred" to run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument "mopidy_track"'
			fi
			osascript -e "$cmd"

			old_player_state=${player_state}

			sleep 3
			continue
		fi

		if [ "${player_state}" != "${old_player_state}" ] && [ "${player_state}" == "playing" ]
		then
			cmd='tell application id "com.runningwithcrayons.Alfred-3" to run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument "mopidy_state"'
			if [ "${ALFRED_NAME}" == "com.runningwithcrayons.Alfred" ]
			then
				cmd='tell application id "com.runningwithcrayons.Alfred" to run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument "mopidy_state"'
			fi
			osascript -e "$cmd"	
		fi

		old_player_state=${player_state}

		sleep 3
	done
}

function StartSpotifyConnect
{
	result=""
	query=$(php -r '$foo = serialize(array("", "", "", "", "", "", "", "current_connect" /* other_action */, "", "", "", "", "", "", "", "" , "", "", "", "", "", ""));echo $foo;')
	current_track_url=""
	old_player_state=""
	player_state=""
	track_url=""

	until [ "${result}" == "connect_stopped" ]
	do
		result=$(php -f ./src/action.php -- "$query" "TRACK" "")

		track_url=$(echo "${result}" | awk -F '▹' '{print $5}')
		player_state=$(echo "${result}" | awk -F '▹' '{print $4}')

		if [ "${track_url}" != "${current_track_url}" ]
		then
			current_track_url=$(echo "${result}" | awk -F '▹' '{print $5}')
			cmd='tell application id "com.runningwithcrayons.Alfred-3" to run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument "connect_track"'
			if [ "${ALFRED_NAME}" == "com.runningwithcrayons.Alfred" ]
			then
				cmd='tell application id "com.runningwithcrayons.Alfred" to run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument "connect_track"'
			fi
			osascript -e "$cmd"

			old_player_state=${player_state}
			sleep 3
			continue		
		fi

		if [ "${player_state}" != "${old_player_state}" ] && [ "${player_state}" == "playing" ]
		then
			cmd='tell application id "com.runningwithcrayons.Alfred-3" to run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument "connect_state"'
			if [ "${ALFRED_NAME}" == "com.runningwithcrayons.Alfred" ]
			then
				cmd='tell application id "com.runningwithcrayons.Alfred" to run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument "connect_state"'
			fi
			osascript -e "$cmd"	
		fi

		old_player_state=${player_state}

		sleep 3
	done
}

if [ "${ACTION}" = "stop" ]
then
	for pid in $(ps -efx | grep "spotify_mini_player_notifications" | grep -v grep | awk '{print $2}')
	do
		if [ "$pid" != "" ]
		then
			# only work for applescript
			pkill -P $pid
			# needed for connect / mopidy
			kill -9 $pid
			if [ -f "${DATADIR}/spotify_mini_player_notifications.lock" ]
			then
				rm "${DATADIR}/spotify_mini_player_notifications.lock"
			fi
		fi
	done
	exit 0
fi

if [ -f "${DATADIR}/spotify_mini_player_notifications.lock" ]
then
	# the lock file already exists, so what to do?
	if [ "$(ps -p `cat "${DATADIR}/spotify_mini_player_notifications.lock"` | wc -l)" -gt 1 ]
	then
		# process is still running
		return 0
	else
		# process not running, but lock file not deleted?	
		rm "${DATADIR}/spotify_mini_player_notifications.lock"
		# Now go ahead
	fi
fi

echo $$ > "${DATADIR}/spotify_mini_player_notifications.lock"

# call to main function
if [ "${APP}" = "SPOTIFY" ]
then
	StartAppleScript
elif [ "${APP}" = "CONNECT" ]
then
	StartSpotifyConnect
else
	StartMopidy
fi


if [ -f "${DATADIR}/spotify_mini_player_notifications.lock" ]
then
	rm "${DATADIR}/spotify_mini_player_notifications.lock"
fi
