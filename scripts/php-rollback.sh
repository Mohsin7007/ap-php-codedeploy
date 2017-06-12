#!/bin/bash
        ACTION="deploy"
        BACKUPLOCATION=/opt/tmp/BACKUP
        BACKUPDIR=`date +%Y%m%d%H%M%S`
        OLDBACKUPDIR=/opt/tmp/OLDBACKUP;
        DOCROOT=/var/www/html/sheroes
        S3BUCKET=lncwebsite
        logger "$DATE  Rolling backup the application";
	echo "Error in deployment.Rollback initiated...";
                                        #DEPLOYEDDIR=`ls $BACKUPLOCATION/`
                                        #cp -rf $BACKUPLOCATION/$DEPLOYEDDIR $DOCROOT
                                        cd  $DOCROOT ; tar  xvfz  $BACKUPLOCATION/sheroes-bkup*
                                        RETVAL1=$?
                                        if [ $RETVAL1 = 0 ]
                                        then
                                                echo "Roll back done successfully";
                                        else
                                                echo "Error in Rolling Back.";
                                	fi
