#!/bin/ksh

set -e

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

		repeat until application "Spotify" is not running
			set track_url to spotify url of current track

			if track_url ≠ current_track_url then
				set current_track_url to spotify url of current track
				tell application "Alfred 2"
					run trigger "display_current_track_notification" in workflow "com.vdesabou.spotify.mini.player" with argument track_url
				end tell
			end if

			delay 5
		end repeat
	end tell
on error error_message
	return
end try
EOT
}


if [ "${ACTION}" = "stop" ]
then
	traceit "STOP"
	pid=$(ps -efx | grep "spotify_mini_player_notifications" | grep -v grep | awk '{print $2}')
	if [ "$pid" != "" ]
	then
		kill "$pid"
		traceit "INFO: spotify_mini_player_notifications killed $pid"
	fi
	exit 0
else
	if [ -f "${DATADIR}/spotify_mini_player_notifications.lock" ]; then
		# the lock file already exists, so what to do?
		if [ "$(ps -p `cat "${DATADIR}/spotify_mini_player_notifications.lock"` | wc -l)" -gt 1 ]; then
			# process is still running
			traceit "INFO: Already running: process `cat "${DATADIR}/spotify_mini_player_notifications.lock"`, `date`"
			exit 0
		else
			# process not running, but lock file not deleted?

			traceit "INFO: orphan lock file warning, process spotify_mini_player_notifications not running."
			rm "${DATADIR}/spotify_mini_player_notifications.lock"
			traceit "INFO: Lock file deleted. `date`"

			# Now go ahead
		fi
	fi

	echo $$ > "${DATADIR}/spotify_mini_player_notifications.lock"

	# call to main function
	Start

	rm "${DATADIR}/spotify_mini_player_notifications.lock"
fi

exit 0
