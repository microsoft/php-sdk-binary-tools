SSH_DIR="/local/sites/snaps.php.net/www/win32"

cd $SSH_DIR
for VER in "5.2" "5.3" "6.0"; do
	for i in "php$VER-win32-2*.zip" "php$VER-dbgpack-win32-2*.zip" "pecl$VER-win32-2*.zip" "php$VER-win32-installer-2*.msi"; do
		list=`ls -r $i 2>/dev/null`
		cd $SSH_DIR
		count=0
		for j in $list; do
			let count=count+1
			if [ "$count" -gt "5" ]; then
				rm -f $j
			fi
		done
	done
done
