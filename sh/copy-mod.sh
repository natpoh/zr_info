#!/bin/bash

# BACKUP_DIR
WORKSPACE="/var/www/"

DATACM="wp-content/plugins/critic_matic/"
DATAML="wp-content/plugins/movies_links/"

RWT="rwt/"
INFO="inforwt/"

cp -R -u $WORKSPACE$RWT$DATACM* $WORKSPACE$INFO$DATACM
cp -R -u $WORKSPACE$RWT$DATAML* $WORKSPACE$INFO$DATAML