<?php

if ( $argc < 3 ) {
    echo( "USE: exclusive.php <lockname> <command to run>\n" );
    exit;
}

$lock = $argv[ 1 ];

$cmd = array_slice( $argv, 2 );

$command = implode( ' ', $cmd );

$filename = "/tmp/$lock.lck";

$fp = fopen( $filename, "w+" );
if ( !$fp ) {
    die( 'Unable to create lock file' );
}


/* WARNING: LOCK_NB behaves tricky. true is still returned in case of wait but $l is set to 1 */

$r = flock( $fp, LOCK_EX | LOCK_NB, $l );

var_dump( $l );

if ( $r & !$l ) /* Lock successfull */ {
    system( $command, $res );
    flock( $fp, LOCK_UN ); // release the lock
    /* Unlink file just in case so we do not have problem runnin with different users */
    unlink( $filename );
    exit( $res );
} else {
    echo "Couldn't lock the file !";
}

fclose( $fp );

?>
