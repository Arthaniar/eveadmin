#!/bin/bash
# EveAdmin Bash update script
DBUSER=$1
DATABASE=$2
PASSWORD=$3

TABLES=( "dgmAttributeCategories dgmAttributeTypes dgmEffects dgmTypeAttributes dgmTypeEffects invCategories invGroups invItems invMarketGroups invMetaGroups invMetaTypes invTypes mapRegions mapSolarSystemJumps mapSolarSystems staStations" )

for TABLE in $TABLES; do
	wget -q "https://www.fuzzwork.co.uk/dump/latest/${TABLE}.sql.bz2"
	bunzip2 "${TABLE}.sql.bz2"
	/bin/rm -f "${TABLE}.sql.bz2"
	mysql -u "$DBUSER" -p"$PASSWORD" "$DATABASE" < "${TABLE}.sql"
	echo "Table ${TABLE} updated successfully"
	/bin/rm -f "${TABLE}.sql"
done 
exit 0
