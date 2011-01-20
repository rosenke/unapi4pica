<?php

/*
 *  Purpose
 *
 *  This is a simple client to use unAPI.
 */

/*
 *  Copyright
 *
 *  Copyright 2008 2009 Goetz Hatop <hatop@ub.uni-marburg.de>.
 *  Goetz Hatop's original Version can be found at
 *  <ftp://ftp.ub.uni-marburg.de/pub/research/unapi.tar.gz>.
 *
 *  Copyright 2010 Stephan Rosenke <rosenke@ulb.tu-darmstadt.de> or
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
 *  0.1
 */

/*
 *  Changelog
 *
 *  20100127:	Added link in copyright.
 *  20100111:	Some style changes.
 */

//  TODO: Add comments

require('unAPI.inc.php');

$unapi = "http://example.com/unAPI.php";
$url = "";
$ppn = "";

function getData($ppn, $format) {
  global $url;
  global $unapi;
  $url = $unapi."?id=".$ppn."&format=".$format;
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  $res = curl_exec($ch);
  curl_close($ch);
  return $res;
}

/** experimantal 
function transform($str) {
  $dom = new DomDocument();
  $dom->loadXML($str);

  $xslDom = new DomDocument();
  $xslDom->load("autobib.d/verbatim.xsl");
  $xsl = new XsltProcessor();
  $xsl->importStylesheet($xslDom);

  $result = $xsl->transformToXML($dom);
  return $result;
}
*/

foreach(array_keys($_POST) as $k) {
   $$k = $_POST[$k];
   //print ": $k $_POST[$k]<br>\n";
}
foreach(array_keys($_GET) as $k) {
   $$k = $_GET[$k];
}

//foreach(array_keys($_SESSION) as $k) {
//   $$k = $_SESSION[$k];
//}

$PHP_SELF = $_SERVER['PHP_SELF'];
$prec = new PicaRecord();

$result ="";
$ppn = preg_replace('/\D/','', $ppn);

//some ppns to cicle through
$ppns = array('0194126234', '0180232991', '0179316613', '0092004970', 
              '0194091996', '0134175158 ', '0011969849', '0203850289', 
              '0205836976', '0110095081', '0013681125', '012809317X',
              '112131492','0032291078');
if (isset($test)) {
  $ppn = $ppns[$ppntest]; // "179316613";
  if ($ppntest++ > sizeof($ppns)) $ppntest=0;
} else {
  $ppntest = 0;
  //$ppn = $ppns[$ppntest]; // "179316613";
}
?>

<html>
<head>
<meta name="author" content="Goetz Hatop" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="description" CONTENT="Pica metadata support UB Marburg" />
<script type="text/javascript">
function toggle(src, targetId) {
  id = src.id;
  lastColon = id.lastIndexOf(':');
  if (lastColon == -1) {
    basePath = "";
  } else {
    basePath = id.substring(0, lastColon + 1);
  }
  fullTargetId = basePath + targetId;
  target = document.getElementById(fullTargetId);
  if (target.style.display == "none") {
    target.style.display = "block";
    src.isTargetHidden = false;
  } else {
    src.isTargetHidden = true;
    target.style.display = "none";
  }
  return false;
}
</script>
<style type="text/css">
     body { margin-top : 30px;
           margin-bottom : 30px;
           margin-left : 10%;
           margin-right : 10%;
           background-color : white;
    }
    th { line-height : 102%;
          font-family : Arial,Helvetica,sans serif;
          text-align: left;
          font-size: 90%;
    }
    p { font-family:Verdana,Arial,Helvetica,sans serif;
        margin-left: 5%;
        margin-right: 5%;
        text-align: justify
      }
    pre { margin-left: 5% }
    div { margin-left: 40px;
          margin-right: 60px;
          text-align: justify
        }
</style>
  <title>OCLC/Pica LBS unAPI test client</title>
</head>
<body>

 <h3 align="center"> OCLC/Pica LBS unAPI test client </h3>
<p>
<div>
  <form action="<?=$PHP_SELF?>" method="POST">
    <input type="hidden" name="ppntest" size="2" value="<?=$ppntest?>" />
    ppn: <input type="text" name="ppn" size="12" value="<?=$ppn?>" />
    <input type="submit" name="submit" value="Daten" />
    <input type="submit" name="test" value="test" />

    <select name="action">
    <option value="1" <?php if($action==1) echo "selected" ?>/> XML </option>
    <option value="2" <?php if($action==2) echo "selected" ?>/> Dublin Core </option>
    <option value="3" <?php if($action==3) echo "selected" ?>/> RDF </option>
    <option value="4" <?php if($action==4) echo "selected" ?>/> Pica Plus </option>
    </select>
  </form>
</div>
</p>

<div>
<a href="#" title="pica plus" onclick="toggle(this,'display')">
   <img src="cab_view.png" border="0" /></a>
   [<?=$ppntest ?>] <?=$ppn ?>
</div>
<div id="display" style="display:none">
<pre>
-------------------------------------------------
<?php 
  switch ( $action ) {
     case 1: //get pica+ as xml 
            $data = getData($ppn, "xml");
            $prec->readXmlData($data);
            //$result = transform($result);
            $result = htmlspecialchars($data);
            break;
     case 2: //get dublin core
            $data = getData($ppn, "dc");
            $prec->readDublinCore($data);
            $result = htmlspecialchars($data);
            break;
     case 3: //get dublin core rdf
            $data = getData($ppn, "rdf");
            $prec->readRDF_DC($data); //TODO
            $result = htmlspecialchars($data);
            break;
     case 4: //pica plus diagnostic format
            $data = getData($ppn, "picaplus");
            $result = $prec->readPicaPlus($data); //TODO: can't read this 
            break;
     default:
            break;
  }// switch action
  echo $result;
?>
-------------------------------------------------
</pre>
  <small><tt><a href="<?=$url?>"><?=$url?></a></tt></small>
</div>
<?php if(isset($action)): ?>

<!--
  http://www.kim-forum.org/material/pdf/uebersetzung_dcmes_20070822.pdf
  http://www.dublincore.org/documents/dces/
-->
</div>
<br/>
  <table title="Dublin Core" class="tab12">
   <tr><td title="DC:title">Titel:</td><td><?=$prec->title ?></td></tr>
   <tr><td title="DC:creator">Autor:</td><td><?=$prec->creator ?></td></tr>
   <tr><td title="DC:contributor">Herausgeber:</td>
       <td><?=$prec->contributor ?></td></tr>
   <!--
   <tr><td title="DC:coverage">Geltungsbereich:</td><td><?=$prec->coverage ?></td></tr>
   -->
   <tr><td title="DC:date">Erscheinungsjahr:</td><td><?=$prec->date ?></td></tr>
   <tr><td title="DC:description">Schlagwörter:</td><td>
       <?=$prec->description ?></td></tr>
   <tr><td title="DC:format">Format:</td><td><?=$prec->format ?></td></tr>
   <tr><td title="DC:identifier">ISBN:</td>
       <td><?=$prec->identifier ?></td></tr>
   <tr><td title="DC:language">Sprache:</td><td><?=$prec->language ?></td></tr>
   <tr><td title="DC:publisher">Verleger:</td>
       <td><?=$prec->publisher ?></td></tr>
   <tr><td title="DC:related">Link</td>
       <td><a href="<?=$prec->related ?>"><?=$prec->related ?></a></td></tr>
   <!--
   <tr><td title="DC:rights">Rechte:</td><td><?=$prec->rights ?></td></tr>
   -->
   <tr><td title="DC:source">Quelle:</td><td><?=$prec->source ?></td></tr>
   <tr><td title="DC:subject">Klassifikation:</td>
       <td><?=$prec->subject ?></td></tr>
   <tr><td title="DC:type">Typ:</td><td><?=$prec->type ?></td></tr>
  </table>
  <br/>
<?php endif; ?>
</body>
</html>

