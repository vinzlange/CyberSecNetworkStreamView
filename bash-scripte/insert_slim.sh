#!/bin/bash
while read time ip a tos a tll a id a offset a flags a proto a a length_tmp src a dst a a rest
do

if [ -n "$time" ] #abfangen von leeren Input
then 
	#wenn über 5000 Datensätze lösche die ältesten 100
	mysql -u pi -h localhost -e "use test; delete from connections where (select count(*) from connections) > 10000 order by id limit 100;"

	length=$(echo "${length_tmp%?}")

	#Abfragen für jedes Protokol
	if [ $proto = "UDP" ]
	then	
		src_tmp=$(echo "$src" | rev | cut -d"." -f2- | rev)
		src_port=$(echo "$src" | cut -d"." -f5- )
		dst_tmp=$(echo "$dst" | rev | cut -d"." -f2- | rev)
		dst_port_tmp=$(echo "$dst" | cut -d"." -f5- )
		dst_port=$(echo "${dst_port_tmp%?}")
		mysql -u pi -h localhost -e "use test; INSERT INTO connections (id, time, src, src_port, dst, dst_port, proto, length) VALUES (NULL, \"$time\", INET6_ATON(\"$src_tmp\"), \"$src_port\", INET6_ATON(\"$dst_tmp\"), \"$dst_port\", \"$proto\", \"$length\");"
    if [ $src_port = 53 ]; then
      url_tmp=$(echo "$rest" | cut -d" " -f1)
      url=$(echo "${url_tmp%?}")
      ip="1"
      for((i=1; i<8; i++))
      do
          case $i in
          1)
            ip=$(echo "$rest" | rev | cut -d"A" -f1 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          2)
            ip=$(echo "$rest" | rev | cut -d"A" -f2 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          3)
            ip=$(echo "$rest" | rev | cut -d"A" -f3 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          4)
            ip=$(echo "$rest" | rev | cut -d"A" -f4 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          5)
            ip=$(echo "$rest" | rev | cut -d"A" -f5 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          6)
            ip=$(echo "$rest" | rev | cut -d"A" -f6 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          7)
            ip=$(echo "$rest" | rev | cut -d"A" -f7 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;

          esac
          if [ "$ip" != "" ]; then
            mysql -u pi -h localhost -e "use test; INSERT INTO dns (id, client, server, ip, url) VALUES (NULL, INET6_ATON(\"$dst_tmp\"), INET6_ATON(\"$src_tmp\"), INET6_ATON(\"$ip\"), \"$url\");"
          fi
      done
    fi
	elif [ $proto = "TCP" ]
	then 	
		src_tmp=$(echo "$src" | rev | cut -d"." -f2- | rev)
		src_port=$(echo "$src" | cut -d"." -f5- )
		dst_tmp=$(echo "$dst" | rev | cut -d"." -f2- | rev)
		dst_port_tmp=$(echo "$dst" | cut -d"." -f5- )
		dst_port=$(echo "${dst_port_tmp%?}")
		mysql -u pi -h localhost -e "use test; INSERT INTO connections (id, time, src, src_port, dst, dst_port, proto, length) VALUES (NULL, \"$time\", INET6_ATON(\"$src_tmp\"), \"$src_port\", INET6_ATON(\"$dst_tmp\"), \"$dst_port\", \"$proto\", \"$length\");"


    if [ $src_port = 53 ]; then
      url_tmp=$(echo "$rest" | cut -d" " -f1)
      url=$(echo "${url_tmp%?}")
      ip="1"
      for((i=1; i<8; i++))
      do
          case $i in
          1)
            ip=$(echo "$rest" | rev | cut -d"A" -f1 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          2)
            ip=$(echo "$rest" | rev | cut -d"A" -f2 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          3)
            ip=$(echo "$rest" | rev | cut -d"A" -f3 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          4)
            ip=$(echo "$rest" | rev | cut -d"A" -f4 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          5)
            ip=$(echo "$rest" | rev | cut -d"A" -f5 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          6)
            ip=$(echo "$rest" | rev | cut -d"A" -f6 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;
          7)
            ip=$(echo "$rest" | rev | cut -d"A" -f7 | rev | cut -d" " -f2 | cut -d"," -f1)
            ;;

          esac
          if [ "$ip" != "" ]; then
            mysql -u pi -h localhost -e "use test; INSERT INTO dns (id, client, server, ip, url) VALUES (NULL, INET6_ATON(\"$dst_tmp\"), INET6_ATON(\"$src_tmp\"), INET6_ATON(\"$ip\"), \"$url\");"
          fi
      done
    fi


	elif [ $proto = "x" ]
	then 
		echo "x"

	elif [ $proto = "x" ]
	then 
		echo "x"

	else
		 echo "missing:"
		 echo -e "\e[33m $time $ip $tos $tll $id $offset $flags $proto $length $src $dst $rest\e[0m "

	fi
#echo ""
fi

done < "${1:-/dev/stdin}"

