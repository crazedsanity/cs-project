#!/bin/bash

PATTERN=$1


for F in `ls $PATTERN*.png`
do
	name=`echo "$F" | sed -e "s/.png$/.gif/"`
	echo -n $name
	convert $F $name
	echo -n ".."
	rm $F
	echo -n ".."
	svn add $name > /dev/null
	echo -n ".."
	svn rm $F > /dev/null
	echo -n ".."
	echo "   DONE"
done;
