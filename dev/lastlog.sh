#!/bin/bash

# colors
resetColor="\x1b[0m"
red="\x1b[31m"
green="\x1b[32m"
yellow="\x1b[33m"
blue="\x1b[34m"
pink="\x1b[35m"
cyan="\x1b[36m"
bold="\x1b[1m"
underline="\x1b[4m"
highlight="\x1b[7m"

# Shows the last entry of the developer critical log

# + coloring : date in green, exception message (if found) in red, line number (if found) in yellow

TODAY=`date +%F`

if [ ! -d "app/logs" ] ; then
    echo "You must be in the root folder of the project."
    exit 1
fi

if [ ! -f "app/logs/dev.critical-$TODAY.log" ] ; then
    echo "No critical failures logged today (app/logs/dev.critical-$TODAY.log not found)."
    exit 0
fi

echo -e $(cat app/logs/dev.critical-$TODAY.log | grep CRITICAL | tail -1 | sed 's/\\/\\\\/g' | sed "s+: \(\".*\"\) at +: ${red}\1${resetColor} at +g" | sed 's+ at \([^ ]* line [^ ]*\)+ at '"${yellow}"'\1'"${resetColor}"'+g' | sed 's+^\(\[....-..-.. ..:..:..\]\)+'"${green}"'\1'"${resetColor}"'+g')
