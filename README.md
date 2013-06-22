metagist-api
============

RPC library for metagist and its workers. The library provides interfaces for both 
metagist.org and remote workers which scan packages for information. 

Uses Guzzle, Monolog and JMS Serializer.

Server API:
-----------

* GET package (string author, string name): Returns package info in JSON format.
* POST pushInfo (string author, string name, MetaInfo info): Update package info. Requires OAuth.

Worker API:
-----------

* POST scan (string author, string name): Triggers information retrieval (async).