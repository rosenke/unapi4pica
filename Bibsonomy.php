<?php

/*
 *  Purpose
 *
 *  Take an unique identifier as id-parameter and redirect client
 *  to Bibsonomy with BibTeXed bibliographic data.
 */

/*
 *  Copyright
 *
 *  Copyright 2008 2009 Goetz Hatop <hatop@ub.uni-marburg.de>.
 *  Goetz Hatop's original Version can be found at
 *  <ftp://ftp.ub.uni-marburg.de/pub/research/unapi.tar.gz>.
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
 *  20100127:	Added link in copyright.
 *  20100110:	Some style improvements, added comments.
 *  20100109:	Some style improvements, added comments.
 *  20091214:	stripped unAPI.php for use as Bibsonomy-forwarder.
 */

/*
 *  Readme
 *
 *  For linking to Bibsonomy include in a HTML-file a line like this:
 *  <a href="http://example.com/Bibsonomy.php?id=123">Bibsonomy</a>
 */

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

//  get URL for redirecting to Bibsonomy
$res = $pica->getBibsonomy();

//  check if $res is not empty and do redirect
if (!isset($res) || $res == '') {
  header('HTTP/1.0 406 Not Acceptable');
  echo "406: Not Acceptable";
  return;
} else {
  header("Location: $res");
}

?>
