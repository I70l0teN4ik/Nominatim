<?php
/**
 * SPDX-License-Identifier: GPL-2.0-only
 *
 * This file is part of Nominatim. (https://nominatim.org)
 *
 * Copyright (C) 2022 by the Nominatim developer community.
 * For a full list of authors see the git log.
 */

require_once(CONST_LibDir.'/init-website.php');
require_once(CONST_LibDir.'/log.php');
require_once(CONST_LibDir.'/output.php');
ini_set('memory_limit', '200M');

$oParams = new Nominatim\ParameterParser();
$sOutputFormat = $oParams->getSet('format', array('json'), 'json');
set_exception_handler_by_format($sOutputFormat);

$oDB = new Nominatim\DB(CONST_Database_DSN);
$oDB->connect();

$sSQL = 'select placex.place_id, country_code,';
$sSQL .= " name->'name' as name, i.* from placex, import_polygon_delete i";
$sSQL .= ' where placex.osm_id = i.osm_id and placex.osm_type = i.osm_type';
$sSQL .= ' and placex.class = i.class and placex.type = i.type';
$aPolygons = $oDB->getAll($sSQL, null, 'Could not get list of deleted OSM elements.');

if (CONST_Debug) {
    var_dump($aPolygons);
    exit;
}

if ($sOutputFormat == 'json') {
    javascript_renderData($aPolygons);
}
