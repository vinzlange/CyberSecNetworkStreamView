#!/bin/bash
mysql -u pi -h localhost -e "use test; delete from testtabelle where (select count(*) from testtabelle) > 5000 order by id limit 100;"
