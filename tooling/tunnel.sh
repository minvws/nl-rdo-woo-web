#!/usr/bin/env bash

#
# Ignore this file. It's only used to create a tunnel to the deadcode server for development purposes.
#

ssh -L 5433:localhost:5432 -L 15673:localhost:15672 -L 9201:localhost:9200 -N -f -l woopie woopie.deadcode.nl
