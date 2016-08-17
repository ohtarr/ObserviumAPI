<?php

include("/opt/observium/includes/sql-config.inc.php");

include_once("/opt/observium/html/includes/functions.inc.php");

print_r($groups = get_type_groups('device'));

