<?php

/*
 *  Purpose
 *
 *  This is an implementation of an unAPI for PICA-LBSs.
 *  It uses the XML-interface of the OPC4 for extracting bibliographic
 *  data.
 *  For the specification of unAPI, see <http://unapi.info/specs/>.
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
 *  20100219:	Added csv as format.
 *  20100127:	Added link in copyright.
 *  20100111:	Added openurl-kev as format.
 *  20100110:	Some style improvements, added comments, removed some old code.
 *  20100109:	Some style improvements, added comments.
 *  20091216:	Some minor style and other corrections.
 *  20091215:   Added JSON as format.
 *  20091214:   s/require/require_once/.
 *              Made last switch() more validator compliant.
 *  20091211:	Added HTTP-Response-Codes to some cases in the last switch.
 *		Corrected answer if format=''.
 *		Added BibTex as format.
 *  20091210:	Put class Picappn to PicaRecord.php.
 *		Inserted conditional for textual output of array.
 *		Ordered final switch() alphabetically.
 *		Added format "plain", renamed "picaplus" to "extpp".
 *		Put header() in the case-loops in final switch().
 *  20091210:	Started with Goetz Hatop's version of 2009-12-08.
 */


/*
 *
 *  DO NOT MESS BEHIND THIS LINE
 *
 */

//  include some objects and methods
require_once('unAPI.inc.php');

//  make some GET-variables a little bit handier
$format = $_GET['format'];
$id = $_GET['id'];

/*
 *  Output if no id-parameter or no parameter at all is given.
 *  Plain-format is not announced.
 */
$noparam = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<formats>
<format name=\"bibtex\" type=\"text/plain\" />
<format name=\"csv\" type=\"text/plain\" />
<format name=\"dc\" type=\"application/xml\" />
<format name=\"extpp\" type=\"application/xml\" />
<format name=\"json\" type=\"application/json\" />
<format name=\"openurl-kev\" type=\"text/plain\" />
<format name=\"rdf\" type=\"application/xml\" />
<format name=\"text\" type=\"text/plain\" />
<format name=\"xml\" type=\"application/xml\" />
</formats>
";

/*
 *  check if $id is not empty, if it's empty show
 *  available formats.
 */
if (!isset($id) || $id == '') {
  header('Content-type: application/xml');
  echo "$noparam";
  return;
}

//  instantiate new Picappn()
$pica = new Picappn();

//  set PPN as identifier
$pica->setPpn($id);

/*
 *  This is no part of the unAPI but for debugging.
 *  It is triggered by supplying "array" as $format and print_rs the
 *  pica-data as array.
 */
if ($format == "array") {
  echo "<head></head>\n<body>\n <pre>\n";
  print_r($pica->getArray());
  echo " </pre>\n</body>\n";
  die(0);
}

//  Final switch for printing result according to $format
switch ($format) {
  //  BibTeX
  case 'bibtex':
     $res = $pica->getBibTex();
     final_result($res, 'text/plain');
     break;
  //  CSV
  case 'csv':
     $res = $pica->getCsv();
     final_result($res, 'text/plain');
     break;
  //  Dublin Core
  case 'dc':
     $res = $pica->getDublinCore();
     final_result($res, 'application/xml');
     break;
  //  External PICA+
  case 'extpp':
     $res = $pica->getPicaPlus();
     final_result($res, 'application/xml');
     break;
  //  JSON
  case 'json':
     $res = $pica->getJson();
     final_result($res, 'application/json');
     break;
  //  OpenURL-KEV
  case 'openurl-kev':
     $res = $pica->getOpenUrlKev();
     final_result($res, 'text/plain');
     break;
  //  pseudo-XML
  case 'plain':
     $res = $pica->getPlain();
     break;
  //  RDF
  case 'rdf':
     $res = $pica->getDublinCoreRDF();
     final_result($res, 'application/xml');
     break;
  //  plain text
  case 'text':
     $res = $pica->getText();
     final_result($res, 'text/plain');
     break;
  //  XML
  case 'xml':
     $res = $pica->getXmlData();
     final_result($res, 'application/xml');
     break;
  //  if no $format is given but $id is set
  case '':
     header('HTTP/1.0 300 Multiple Choices');
     header('Content-type: application/xml');
     echo "$noparam";
     break;
  //  for all other values of $format
  default:
     header('HTTP/1.0 406 Not Acceptable');
     break;
}
?>
