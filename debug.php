<?php
require_once('workflows.php');

$w = new Workflows();


exec("mkdir -p ~/Downloads/spot_mini_debug");

$output = "DEBUG:";

//
// check for library update in progress
if (file_exists($w->data() . "/update_library_in_progress"))
{
	$output = $output . "Library update in progress: " . "the file" . $w->data() . "/update_library_in_progress is present\n";
}

if (!file_exists($w->home() . "/Spotify/spotify-app-miniplayer"))
{		
	echo "The directory" . $w->home() . "/Spotify/spotify-app-miniplayer is not present\n";
}
else
{
	copy_directory($w->home() . "/Spotify/spotify-app-miniplayer",$w->home() . "/Downloads/spot_mini_debug/spotify-app-miniplayer");
}


if(!file_exists($w->data() . "/settings.db"))
{
	$output = $output .  "The directory" . $w->data() . "/settings.db is not present\n";
}
else
{
	copy($w->data() . "/settings.db",$w->home() . "/Downloads/spot_mini_debug/settings.db");
}


if(!file_exists($w->data() . "/library.db"))
{
	$output = $output .  "The directory" . $w->data() . "/library.db is not present\n";
}
else
{
	copy($w->data() . "/library.db",$w->home() . "/Downloads/spot_mini_debug/library.db");
}

if(!file_exists( "./output.log"))
{
	$output = $output .  "The file output.log is not present\n";
}
else
{
	copy( "./output.log",$w->home() . "/Downloads/spot_mini_debug/output.log");
}

if(!file_exists( "./output_action.log"))
{
	$output = $output .  "The file output_action.log is not present\n";
}
else
{
	copy( "./output_action.log",$w->home() . "/Downloads/spot_mini_debug/output_action.log");
}


$output = $output . exec("uname -a");


file_put_contents($w->home() . "/Downloads/spot_mini_debug/debug.log",$output);

exec("tar cvfz ~/Downloads/spot_mini_debug.tgz ~/Downloads/spot_mini_debug/");

echo "tgz file is ready";

function copy_directory( $source, $destination ) {
        if ( is_dir( $source ) ) {
        @mkdir( $destination );
        $directory = dir( $source );
        while ( FALSE !== ( $readdirectory = $directory->read() ) ) {
            if ( $readdirectory == '.' || $readdirectory == '..' ) {
                continue;
            }
            $PathDir = $source . '/' . $readdirectory; 
            if ( is_dir( $PathDir ) ) {
                copy_directory( $PathDir, $destination . '/' . $readdirectory );
                continue;
            }
            copy( $PathDir, $destination . '/' . $readdirectory );
        }

        $directory->close();
        }else {
        copy( $source, $destination );
        }
    }

?>