#!/usr/bin/env bash
rep_plugins=/var/www/html/plugins
cd /var/www/html/plugins/auto/
clear
for plugin in $(ls ./)
do
	last_version=$(ls $plugin | tail -n 1)
	echo "plugin : "${plugin}
	echo "last_version :" ${last_version}
	if [[ ! -d ${rep_plugins}/${plugin} ]];
		then
			cp -r ${rep_plugins}/auto/${plugin}/${last_version} ${rep_plugins}/${plugin}
			rm -rf ${rep_plugins}/${plugin}/install.log ${rep_plugins}/${plugin}/.ok ${rep_plugins}/${plugin}/svn.revision ${rep_plugins}/${plugin}/.git* ${rep_plugins}/${plugin}/*.md ${rep_plugins}/${plugin}/composer.*
			rm -rf ${rep_plugins}/auto/${plugin}
		else
			rm -rf ${rep_plugins}/${plugin}
			cp -r ${rep_plugins}/auto/${plugin}/${last_version} ${rep_plugins}/${plugin}
			rm -rf ${rep_plugins}/${plugin}/install.log ${rep_plugins}/${plugin}/.ok ${rep_plugins}/${plugin}/svn.revision ${rep_plugins}/${plugin}/.git* ${rep_plugins}/${plugin}/*.md ${rep_plugins}/${plugin}/composer.*
			rm -rf ${rep_plugins}/auto/${plugin}
	fi
done