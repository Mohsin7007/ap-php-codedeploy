#!/bin/bash
        yum install -y mailx httpd 
	service httpd start;
	chkconfig httpd on;
        MAIL="/bin/mail"
        ACTION="deploy"
        BACKUPLOCATION=/opt/tmp/BACKUP
        BACKUPDIR=`date +%Y%m%d%H%M%S`
        #OLDBACKUPDIR=/opt/tmp/OLDBACKUP;
        DOCROOT=/var/www/html/sheroes
        S3BUCKET=lncwebsite
        logger "$DATE Deploying the application";
        if [ $ACTION = "deploy" ]
                then
                        echo "Deploying the application.";
                        mkdir -p $BACKUPLOCATION;
                        #mkdir -p $OLDBACKUPDIR;
                        for zip in `ls $BACKUPLOCATION/*.gz`
                        do
                        aws s3 mv $zip  s3://$S3BUCKET/
                        done
                        cd $DOCROOT ;tar  cfz  $BACKUPLOCATION/sheroes-bkup-$BACKUPDIR.tar.gz ./ --exclude .git  ;
                        cd  $DOCROOT
  			git checkout app/webroot/img/campaigns/wbb.html
  			git pull --rebase --stat origin integration

                        RETVAL=$?
                        if [ $RETVAL = 0 ]
                        then
                                echo "Deployment done successfully" | $MAIL -s "Deployment done Successfull." anoopdewli@gmail.com ;
                        else
                                echo "Error in deployment.Rollback initiated..." | $MAIL -s "Deployment Failed." anoopdewli@gmail.com ;
                                      cd  $DOCROOT ; tar  xfz  $BACKUPLOCATION/sheroes-bkup*
                                      RETVAL1=$?
                                      if [ $RETVAL1 = 0 ]
                                      then
                                              echo "Roll back done successfully" | $MAIL -s "Rollbackup Successfull." anoopdewli@gmail.com ;
                                              exit 0;
                                      else
                                              echo "Error in Rolling Back." | $MAIL -s "Rollbackup Failid." anoopdewli@gmail.com ;
                                              exit 1000;
                                      fi
                fi
       fi
