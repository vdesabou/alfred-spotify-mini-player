#!/bin/ksh

DATADIR=""
ACTION=""

while getopts ':d:a:' arguments
	do
	  case ${arguments} in
		d)
			DATADIR="${OPTARG}"
			;;
		a)
			ACTION="${OPTARG}"
			;;
	   \?)
			print "ERROR: ${OPTARG} is not a valid option"
			print "Usage: $0 -d <data dir> -a <action>"
			exit 1;;
	  esac
	done


function traceit
{
	datestr=$(date '+%Y/%m/%d %H:%M:%S');
	print "${datestr} : ${1}";
}

function Start
{
osascript <<EOT
try
	tell application "Spotify"

		set current_track_url to null
		set old_player_state to null

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

				tell application "Alfred 2"
					run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument track_url
				end tell
			end if

			if player_state ≠ old_player_state and player_state is "playing" then
				tell application "Alfred 2"
					run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument player_state
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


if [ "${ACTION}" = "stop" ]
then
	for pid in $(ps -efx | grep "spotify_mini_player_notifications" | grep -v grep | awk '{print $2}')
	do
		if [ "$pid" != "" ]
		then
			pkill -P $pid
			traceit "INFO: killed PID $pid"
			if [ -f "${DATADIR}/spotify_mini_player_notifications.lock" ]
			then
				rm "${DATADIR}/spotify_mini_player_notifications.lock"
			fi
		fi
	done
	return 0
fi

if [ -f "${DATADIR}/spotify_mini_player_notifications.lock" ]
then
	# the lock file already exists, so what to do?
	if [ "$(ps -p `cat "${DATADIR}/spotify_mini_player_notifications.lock"` | wc -l)" -gt 1 ]
	then
		# process is still running
		# traceit "INFO: Already running: process `cat "${DATADIR}/spotify_mini_player_notifications.lock"`, `date`"
		return 0
	else
		# process not running, but lock file not deleted?

		traceit "INFO: orphan lock file warning, process spotify_mini_player_notifications not running."
		rm "${DATADIR}/spotify_mini_player_notifications.lock"
		traceit "INFO: Lock file deleted. `date`"

		# Now go ahead
	fi
fi

traceit "INFO: creating lock file . `date`"
echo $$ > "${DATADIR}/spotify_mini_player_notifications.lock"

# call to main function
Start

if [ -f "${DATADIR}/spotify_mini_player_notifications.lock" ]
then
	rm "${DATADIR}/spotify_mini_player_notifications.lock"
fi
