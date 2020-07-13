sudo tcpdump -s0 -lv -n -i wlan0 not port 22 and not ip6 and not broadcast | awk '{ if (match($0, /^[0-9]/, _)) { printf (NR == 1 ? "%s " : "\n%s "), $0; fflush() } else { sub(/^\s+0x[0-9a-z]+:\s+/, " "); printf "%s", $0 } } END { print ""; fflush() }'

