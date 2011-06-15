Koana-Cassandra
===========

Cassandra support for Kohana 3.1.x

How to use
----------

First you need to download PHPCassa (https://github.com/thobbs/phpcassa) to your website root.

$this->cassandra = new Cassandra();
$this->ColumnFamily = $this->cassandra->selectColumnFamily('ColumnFamily');
