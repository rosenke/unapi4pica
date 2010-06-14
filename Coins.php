<?php

/*
 *  Purpose
 *
 *  Take an unique identifier as id-parameter and return bibliographic data
 *  as COinS wrapped in JavaScript.
 *  For the specification of COinS, see <http://ocoins.info/>.
 */

/*
 *  Copyright
 *
 *  Copyright 2008 2009 Goetz Hatop <hatop@ub.uni-marburg.de>.
 *
 *  Copyright 2009 2010 Stephan Rosenke <rosenke@ulb.tu-darmstadt.de> or
 *  <r01-551@r0s.de>. See also <http://r0s.de/unapi>.
 */

/*
 *  License
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 *  Version
 *
 *  0.5
 */

/*
 *  Changelog
 *
 *  20100502:	Corrected a comment.
 *  20100127:	Added link in copyright.
 *  20100111:	stripped Bibsonomy.php for use as COinS-Generator.
 */

/*
 *  Readme
 *
 *  For generating COinS using this script include it in a HTML-file by these
 *  lines:
 *  <script src="http://example.com/Coins.php?id=123" type="text/javascript"
 *   language="JavaScript"></script>
 */

/*
 *  set some user-defineable variables
 */

//  Default text for COinS
$text = '';

/*
 *
 *  DO NOT MESS BEHIND THIS LINE
 *
 */

//  include some objects and methods
require_once('unAPI.inc.php');

//  make some GET-variables a little bit handier
$id = $_GET['id'];

//  check if $id is not empty
if (!isset($id) || $id == '') {
  header('HTTP/1.0 406 Not Acceptable');
  echo "406: Not Acceptable";
  return;
}

//  instantiate new Picappn()
$pica = new Picappn();

//  set PPN as identifier
$pica->setPpn($id);

//  get OpenURL in KEV format
$res = $pica->getOpenUrlKev();

//  check if $res is not empty and return COinS with JavaScript
if (!isset($res) || $res == '') {
  header('HTTP/1.0 406 Not Acceptable');
  return;
} else {
  echo 'document.write("<span class=\"Z3988\" title=\"'.$res.'\">'.$text.
   '</span>")';
}

?>
