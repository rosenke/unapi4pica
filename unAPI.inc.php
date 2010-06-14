<?php

/*
 *  Purpose
 *
 *  These objects and their respective functions follow
 *  - PicaRecord
 *  - Picappn
 *
 *  These functions follow
 *  - final_result
 *
 *  PHP5 and its curl module are recommended.
 */

/*
 *  Copyright
 *
 *  Copyright 2008 2009 Goetz Hatop <hatop@ub.uni-marburg.de>.
 *  Goetz Hatop's original version can be found at
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
 *  0.7
 */

/*
 *  Changelog
 *
 *  20100303:	Added check of $use_curl as additional switch for de-
 *		activating curl.
 *  20100219:	Added function "getCsv()" to class "Picappn".
 *		Renamend keys "RVK" to "rvk" and "DDC" to "ddc".
 *  20100131:	Added (e)Journals to "getBibTex()", "getOpenUrlKev()" and
 *		"getText()" in class "Picappn".
 *  20100127:	Added link in copyright.
 *  20100126:	Transfered "getArrayNice()" from class "Picappn" to
 *		"PicaRecord". Removed dead code. Added comments.
 *  20100125:	Added comments.
 *		Renamed "decodePica_array()" to "decodePicaArray()" in class
 *		"PicaRecord".
 *  20100113:	Added comments. Added further mappings in "getArrayNice()".
 *  20100112:	Added further mappings in "getArrayNice()".
 *		Extended types for monographies.
 *  20100111:	Added function "getOpenUrlKev()" to class "Picappn".
 *		Removed functions "getKeysText()" and "getKeysBibTex()" from
 *		class "Picappn".
 *  20100110:	Added input sanitising for $ppn.
 *  20100109:	Some style improvements, added comments.
 *  20091220:	Check whether curl-Module is available, if not use
 *		"file_get_contents()".
 *		Some minor bugfixes.
 *  20091215:	Added function "getJson()" to class "Picappn".
 *		Made ISBN in "getBibTex()" unique.
 *		Filtered some characters in "getArrayNice()".
 *  20091214:	Added function "convOutputNice()", "getArrayNice()",
 *              "getBibsonomy()", getKeysText()" and "getKeysBibTex()" to class
 *              "Picappn".
 *		Handled empty records for dc and rdf.
 *  20091213:	Added function "convOutput()" to class "Picappn".
 *		Defined text-format.
 *		Defined bibtex-format.
 *  20091211:	Added function "final_result()".
 *  20091210:	Put class "Picappn" to PicaRecord.php.
 *		Ordered functions alphabetically.
 *		Added functions "getArray()", "getPlain()" and  "getText()" to
 *              class "Picappn".
 *		Added functions "decodePicaArray()", "getArray()" and
 *		"getPlain()" to	class "PicaRecord".
 *  20091210:	Started with Goetz Hatop's version of 2009-12-08.
 */

/*
 *  Readme
 *
 *  If you are using PHP4 then search for "comment out if using PHP4" and
 *  comment it out, unClient.php won't work then.
 */

/*
 *  set some user-defineable variables
 */

//  URL-prefix for XML interface
$opac_url = 'http://example.com/DB=1/XML=1.0/PPN?PPN=';

//  URL-prefix for Bibsonomy's BibTeX import
$bibsonomy_url = 'http://www.bibsonomy.org/BibtexHandler?requTask=upload&selection=';

//  Regex for checking if input is PPN
$pattern_ppn = '/^[0-9]{8}[0-9Xx]$/';

//  Array for filtering extracted bibliographic data
$filter_nice = array(
		'{' => '',
		'}' => ''
		);

//  Hostname for generating rfr_id for OpenURL
$rfr_id_hostname= 'example.com';

/*
 *******************************************************************************
 *  DO NOT MESS BEHIND THIS LINE                                               *
 *******************************************************************************
 */

/*
 *  class PicaRecord
 *  represents a bibliographic data record from OCLC/PICA LBS, puts record to a
 *  XML structure or an array  
**/
class PicaRecord {
  //  15 dublin core elements http://dublincore.org/documents/dces/
  var $contributor, $coverage, $creator, $date, $description, $format;
  var $identifier, $language, $publisher, $related, $rights, $source;
  var $subject, $title, $type;
  //  1 for me
  var $signatur;

  /*
   *  constructor
   *  class PicaRecord
   *  PHP5 or later should use '__construct()'
  **/
  function PicaRecord() {
     //  default values
     $this->type = "Text";
     $this->rights = "--";
     $this->description = "";
  }

  /*
   *  private
   *  class PicaRecord
   *  make PICA data behave well and convert them to a XML structure
   *  Used by PicaRecord->getXmlData
  **/
  function decodePica($str) {
    $tag = "";
    $res = "";
    for ($i=0;$i<=strlen($str)-1;++$i) {
        $ch = substr($str,$i,1);
        switch (ord($ch)) {
          case 31: //information separator one
             $ch = substr($str,++$i,1); //new tag ahead
             if ($tag!="") {
                $res .= "</".$tag.">";
                $tag = $this->getTagName($ch);
                $res .= "<".$tag.">";
             } else {
                $tag = $this->getTagName($ch);
                $res .= "<".$tag.">";
             }
             break;

          case 30: //information separator two, another field to follow
             $res .= "</".$tag.">\n";
             $tag = "";
             break;

          case 226: //PICA two byte char accent like "é";
             $ch = substr($str,++$i,1); // read one char ahead
             $res .= $this->getCode2($ch);
             break;

          default:
             $res .= $this->getCode($ch);
             break;
        } //switch

    }
    $res .= "</".$tag.">";

    //  return result
    return $res;
  }

  /*
   *  private
   *  class PicaRecord
   *  make PICA data behave well and put them to an array
   *  Used by PicaRecord->getArray
  **/
  function decodePicaArray($str) {
    $tag = "";
    $res = "";
    for ($i=0;$i<=strlen($str)-1;++$i) {
        $ch = substr($str,$i,1);
        switch (ord($ch)) {
          case 31: //information separator one
             $ch = substr($str,++$i,1); //new tag ahead
             $tag = $this->getTagName($ch);
             $res[$tag] = "";
             break;
          case 30: //information separator two, another field to follow
             break;
          case 226: //PICA two byte char accent like "é";
             $ch = substr($str,++$i,1); // read one char ahead
             $res[$tag] .= $this->getCode2($ch);
             break;
          default:
             $res[$tag] .= $this->getCode($ch);
             break;
        } //switch
    }

    //  return result
    return $res;
  }

  /*
   *  public
   *  class PicaRecord
   *  transform raw PICA data to array
   *  Used by Picappn->getArray
  **/
  function getArray($str) {
    //  PICA record separator is ascii record separator
    $lines = explode(chr(30), $str);
    $res = "";
    foreach ($lines as $line) {
      $in1 = ltrim($line);
      $ch1 = substr($in1,1,1);
      //  first char between 0-9
      if (ord($ch1) >47 && ord($ch1)<58) {
         $pos = strpos($in1, ' ');
         $key = substr($in1,0,$pos);
         $val = substr($in1,$pos+1);
         $val = $this->decodePicaArray($val);
         $pica_array[$key] = $val;
      } else {
        //  not valid
      }
    }
    $res = $pica_array;

    //  return result
    return $res;
  }

  /*
   *  public
   *  class PicaRecord
   *  return bibliographic data in array with speaking keys
   *  Used by Picappn->{getBibTex,getCsv,getOpenUrlKev,getText}
  **/
  function getArrayNice($array_raw) {
    //  if $array_raw is empty just return
    if (empty($array_raw)) return "";
    //  make variable name handier
    $record = $array_raw;

    //  get global, user-defineable variable $filter_nice
    global $filter_nice;

    //  type
    $res['type'] = strtr($record['002@']['x0'], $filter_nice);
    $res['_bibtex']['type'] = '';
    $res['_openurlkev']['type'] = 'rft.genre';
    $res['_text']['type'] = 'Art';
    //  type w/o kind of record
    $res['twokr'] = substr($res['type'], 0, 2);
    $res['_bibtex']['twokr'] = '';
    $res['_openurlkev']['twokr'] = '';
    $res['_text']['twokr'] = '';

    //  author
    $res['author'] = strtr($record['028A']['x8'], $filter_nice);
    $res['author'] = rtrim($res['author']);
    $res['_bibtex']['author'] = '  author';
    $res['_openurlkev']['author'] = 'rft.au';
    $res['_text']['author'] = 'Autor';
    //  editor
    $res['editor'] = strtr($record['028C']['x8'], $filter_nice);
    $res['editor'] = rtrim($res['editor']);
    $res['_bibtex']['editor'] = '  editor';
    $res['_openurlkev']['editor'] = 'rft.au';
    $res['_text']['editor'] = 'Herausgeber';

    //  title
    $res['title'] = strtr($record['021A']['a'], $filter_nice);
    $res['_bibtex']['title'] = '  title';
    $res['_openurlkev']['title'] = 'rft.title';
    $res['_text']['title'] = 'Titel';

    //  series
    if ((!empty($record['036F']['x8'])) || (!empty($record['036F']['l']))) {
      $res['series'] = strtr($record['036F']['x8'], $filter_nice)." ".
       strtr($record['036F']['l'], $filter_nice);
    }
    $res['_bibtex']['series'] = '  series';
    $res['_openurlkev']['series'] = 'rft.series';
    $res['_text']['series'] = 'Schriftenreihe';

    //  address, especially city
    $res['address'] = strtr($record['033A']['p'], $filter_nice);
    $res['_bibtex']['address'] = '  address';
    $res['_openurlkev']['address'] = 'rft.place';
    $res['_text']['address'] = 'Ort';
    //  publisher
    $res['publisher'] = strtr($record['033A']['n'], $filter_nice);
    $res['_bibtex']['publisher'] = '  publisher';
    $res['_openurlkev']['publisher'] = 'rft.pub';
    $res['_text']['publisher'] = 'Verlag';

    //  edition
    $res['edition'] = strtr($record['032@']['a'], $filter_nice);
    $res['_bibtex']['edition'] = '  edition';
    $res['_openurlkev']['edition'] = 'rft.edition';
    $res['_text']['edition'] = 'Auflage';
    //  year
    $res['year'] = strtr($record['011@']['a'], $filter_nice);
    $res['_bibtex']['year'] = '  year';
    $res['_openurlkev']['year'] = 'rft.date';
    $res['_text']['year'] = 'Jahr';
    //  Erscheinungsverlauf
    $res['everlauf'] = strtr($record['031@']['a'], $filter_nice);
    $res['_bibtex']['everlauf'] = '';
    $res['_openurlkev']['everlauf'] = '';
    $res['_text']['everlauf'] = 'Erscheinungsverlauf';

    //  pages
    $res['pages'] = strtr($record['034D']['a'], $filter_nice);
    $res['_bibtex']['pages'] = '';
    $res['_openurlkev']['pages'] = 'rft.pages';
    $res['_text']['pages'] = 'Umfang';

    //  ISBNs
    $res['isbn10'] = strtr($record['004A']['x0'], $filter_nice);
    $res['_bibtex']['isbn10'] = '  isbn';
    $res['_openurlkev']['isbn10'] = 'rft.isbn';
    $res['_text']['isbn10'] = 'ISBN-10';
    $res['isbn13'] = strtr($record['004A']['A'], $filter_nice);
    $res['_bibtex']['isbn13'] = '  isbn';
    $res['_openurlkev']['isbn13'] = 'rft.isbn';
    $res['_text']['isbn13'] = 'ISBN-13';

    //  ISSN
    $res['issn'] = strtr($record['005A']['x0'], $filter_nice);
    $res['_bibtex']['issn'] = '  issn';
    $res['_openurlkev']['issn'] = 'rft.issn';
    $res['_text']['issn'] = 'ISSN';

    //  DNB Number
    $res['dnb_no'] = strtr($record['006G']['x0'], $filter_nice);
    $res['_bibtex']['dnb_no'] = '';
    $res['_openurlkev']['dnb_no'] = '';
    $res['_text']['dnb_no'] = 'DNB-Nummer';

    //  OCLC Number
    $res['oclc_no'] = strtr($record['003O']['x0'], $filter_nice);
    $res['_bibtex']['oclc_no'] = '';
    $res['_openurlkev']['oclc_no'] = '';
    $res['_text']['oclc_no'] = 'OCLC-Nummer';

    //  Foreign Data Identification Number
    $res['fdin'] = strtr($record['007I']['x0'], $filter_nice);
    $res['_bibtex']['fdin'] = '';
    $res['_openurlkev']['fdin'] = '';
    $res['_text']['fdin'] = 'Fremddaten-Identifikationsnummer';

    //  ZDB identification number
    $res['zdb_id'] = strtr($record['007A']['x0'], $filter_nice);
    $res['_bibtex']['zdb_id'] = '';
    $res['_openurlkev']['zdb_id'] = '';
    $res['_text']['zdb_id'] = 'ZDB-ID';

    //  PPN
    $res['ppn'] = strtr($record['003@']['x0'], $filter_nice);
    $res['_bibtex']['ppn'] = '  note';
    $res['_openurlkev']['ppn'] = '';
    $res['_text']['ppn'] = 'PPN';

    //  Link
    $res['link'] = strtr($record['009Q']['u'], $filter_nice);
    $res['_bibtex']['link'] = 'URL';
    $res['_openurlkev']['link'] = '';
    $res['_text']['link'] = 'Link';

    //  Table of Contents
    $res['toc'] = strtr($record['009P']['u'], $filter_nice);
    $res['_bibtex']['toc'] = '';
    $res['_openurlkev']['toc'] = '';
    $res['_text']['toc'] = 'Inhaltsverzeichnis';

    //  DDC
    $res['ddc'] = strtr($record['045B']['a'], $filter_nice);
    $res['_bibtex']['ddc'] = '';
    $res['_openurlkev']['ddc'] = '';
    $res['_text']['ddc'] = 'DDC';

    //  RVK
    $res['rvk'] = strtr($record['045Z']['a'], $filter_nice);
    $res['_bibtex']['rvk'] = 'keywords';
    $res['_openurlkev']['rvk'] = '';
    $res['_text']['rvk'] = 'rvk';

    //  EZB link
    $res['ezb_link'] = strtr($record['209S/01']['u'], $filter_nice);
    $res['_bibtex']['ezb_link'] = '  url';
    $res['_openurlkev']['ezb_link'] = '';
    $res['_text']['ezb_link'] = 'EZB-Link';

    //  return result
    return $res;
  }

  /*
   *  private
   *  class PicaRecord
   *  recode PICA charset
   *  Used by PicaRecord->{decodePica,decodePicaArray}
  **/
  function getCode($ch) {
     $xx = ord($ch);
     switch($xx) {
       //case 30: return "\n"; //information separator two
       //case 31: return "::"; //information separator one
       case 10: return "\n"; // ascii line feed
       // case 60: return "[";
       case 60: return "&lt;";
       // case 62: return "]";
       case 62: return "&gt;";
       case 64: return ""; // @ PICA no sort sign
       case 209: return "ä";
       case 210: return "ö";
       case 211: return "ü";
       case 216: return "ß";
       case 193: return "Ä";
       case 194: return "Ö";
       case 195: return "Ü";
       default:
          if ($xx<30 || $xx>128) {
             return "[".$xx."]";
          }
          return $ch;
     }
  }

  /*
   *  private
   *  class PicaRecord
   *  not complete set of double byte PICA characters started with
   *  an accent sign
   *  Used by PicaRecord->{decodePica,decodePicaArray}
  **/
  function getCode2($ch) {
     switch($ch) {
       case 'e' : return "é";
       case 'u' : return "ú";
       case 'a' : return "á";
       case 'o' : return "ó";
       case 'E' : return "É";
       case 'c' : return "ć";
       case 's' : return "ś";
       case 'z' : return "ś";
       default:
             return $ch;
     }
  }

  /*
   *  public
   *  class PicaRecord
   *  return simple Dublin Core record from PICA data
   *  Used by Picappn->getDublinCore
  **/
  function getDublinCore($str) {
    $res = $this->getXmlData($str); //parse dublin core

    if (empty($res)) return "";

    $res  = "<?xml version=\"1.0\" ?>\n";
    $res .="<record>\n";
    $res .= "<contributor>".$this->contributor."</contributor>\n";
    $res .= "<coverage>".$this->coverage."</coverage>\n";
    $res .= "<creator>".$this->creator."</creator>\n";
    $res .= "<date>".$this->date."</date>\n";
    $res .= "<description>".$this->description."</description>\n";
    $res .= "<format>".$this->format."</format>\n";
    $res .= "<identifier>".$this->identifier."</identifier>\n";
    $res .= "<language>".$this->language."</language>\n";
    $res .= "<publisher>".$this->publisher."</publisher>\n";
    $res .= "<related>".$this->related."</related>\n";
    $res .= "<rights>".$this->rights."</rights>\n";
    $res .= "<source>".$this->source."</source>\n";
    $res .= "<subject>".$this->subject."</subject>\n";
    $res .= "<title>".$this->title."</title>\n";
    $res .= "<type>".$this->type."</type>\n";
    $res .="</record>\n";

    //  return result
    return $res;
  }

  /*
   *  public
   *  class PicaRecord
   *  transform raw PICA plus to a more readable diagnostic format
   *  Used by Picappn->getPicaPlus
  **/
  function getPicaPlus($str) {
    $res = $this->transcribe_pica_utf8($str);
    $res = $this->transcribe_pica_rec($res);

    //  return result
    return $res;
  }

  /*
   *  private
   *  class PicaRecord
   *  rename numeric tags not allowed in XML
   *  Used by PicaRecord->{decodePica,decodePicaArray}
  **/
  function getTagName($ch) {
     if (ord($ch) >47 && ord($ch)<58) {
        return "x".$ch;
     } else {
        return $ch;
     }
  }

  /*
   *  public
   *  class PicaRecord
   *  transform raw PICA data to valid XML
   *  Used by PicaRecord->getDublinCore,
   *  Picappn->{getDublinCoreRDF,getPlain,getXmlData}
  **/
  function getXmlData($str) {
    //  PICA record separator is ascii record separator
    $lines = explode(chr(30), $str);

    //  define $res
    $res = "";

    //  check each element of array
    foreach ($lines as $line) {
      $in1 = ltrim($line);
      $ch1 = substr($in1,1,1);
      //  first char between 0-9
      if (ord($ch1) >47 && ord($ch1)<58) {
         $pos = strpos($in1, ' ');
         $key = substr($in1,0,$pos);
         $val = substr($in1,$pos+1);
         $val = $this->decodePica($val);
         //$res .= "[$key] [$val]\n";
         $res .= "<field tag=\"$key\">$val</field>\n";
         //side effect to scan dublin core elements
         $this->readData($key,$val);
      } else {
        //not valid
      }
    }

    //  return result
    return $res;
  }

  /*
   *  private
   *  class PicaRecord
   *  read Dublin Core elements
   *  Used by PicaRecord->{getXmlData,readXmlData}
  **/
  function readData($key, $val) {
     //switch on first 4 letters to catch 041A/001
     switch (substr($key,0,4)) {
       case '028C': //Sonstige beteiligte bzw. verantwortliche Personen
            $this->contributor .= " ".$this->readTag($val, "x8");
            break;
       case '201@': //unkown
            // The spatial or temporal topic of the resource
            // $this->coverage = $this->readTag($val, "v");
            break;
       case '209S': //unkown. DOI ?
            //The spatial or temporal topic of the resource
            $this->identifier = $this->readTag($val, "u");
            break;
       case '028A': //creator
            $this->creator = $this->readTag($val, "x8");
            break;
       case '028B': //2. und weitere Verfasser
            // $this->creator .= " ".$this->readTag($val, "x8");
            break;
       case '011@': //date Erscheinungsjahr
            $this->date = $this->readTag($val, "a");
            break;
       case '004A': //ISBN
            $this->identifier = $this->readTag($val, "x0");
            break;
       case '009P': //Elektronische Adresse der Personen-Website
            //related : A related resource.
            $this->related = $this->readTag($val, "u");
            break;
       case '009Q': //Elektronische Adresse der Online-Ressource
            //related : A related resource.
            $this->related = $this->readTag($val, "u");
            break;
       case '034I': //format : file format or dimensions of the resource.
            $this->format .= " ".$this->readTag($val, "a");
            break;
       case '034D': //Seitenanzahl
            $this->format .= " ".$this->readTag($val, "a");
            break;
       case '036G': //Ungezählte Schriftenreihen
            $this->subject = $this->readTag($val, "a")." ";
            break;
       case '209A': //Signaturen
            $this->signatur = $this->readTag($val, "a");
            break;
       case '010@': //language
            $this->language = $this->readTag($val, "a");
            break;
       case '033A': //publisher
            $pub1 = $this->readTag($val, "p");
            $pub2 = $this->readTag($val, "n");
            $this->publisher = $pub1." [".$pub2."]";
            break;
       case '4201': //rights : Information about rights held in and over
            $this->rights = $this->readTag($val, "a");
            break;
       case '4219': //rights : Information about rights held in and over
            $this->rights = $this->readTag($val, "a");
            break;
       case '036D': //nicht dokumentiert: Sammelband Gesamtausgabe
            //  source : A related resource from which the described is derived.
            $this->source = $this->readTag($val, "x8")." ";
            break;
       case '045B': //DDC
            $this->subject = "DDC: ".$this->readTag($val, "a");
            break;
       case '045Z': //RVK
            $this->subject = $this->readTag($val, "a");
            break;
       case '041A': //unknown
            if ($this->description !== "") $this->description .= ", ";
            $this->description .= $this->readTag($val, "x8");
            break;
       case '044A': //unknown
            if ($this->description !== "") $this->description .= ", ";
            $this->description .= $this->readTag($val, "a");
            break;
       case '044N': //Maschinell erstellte Indexeintraege
            if ($this->description !== "") $this->description .= ", ";
            $this->description .= $this->readTag($val, "a");
            break;
       case '044K': //Einzelschlagwort
            if ($this->description !== "") $this->description .= ", ";
            $this->description .= $this->readTag($val, "x8");
            break;
       case '041A': //Blackwell subjects
            if ($this->description !== "") $this->description .= ", ";
            $this->description .= $this->readTag($val, "x8");
            break;
       case '021A': //Hauptsachtitel
            $this->title = $this->readTag($val, "a");
            $subtitle = $this->readTag($val, "d");
            if ($subtitle!="") $this->title .= ": ".$subtitle;
            break;
       case '016A': //Materialspezifische Codes für elektronische Ressourcen
            //  type The nature or genre of the resource.
            $this->type = $this->readTag($val, "a");
            break;
       default:
            break;
     }
     //return $res;
  }

  /*
   *  public
   *  class PicaRecord
   *  parse dublin core data
   *  Used by unClient.php
   *  **comment out if using PHP4**
  **/
  function readDublinCore($str) {
    $doc = new DomDocument();
    $doc -> loadXML($str);
    $xp = new DomXPath($doc);
    $this->contributor = $xp->query("/record/contributor")->item(0)->nodeValue;
    $this->coverage = $xp->query("/record/coverage")->item(0)->nodeValue;
    $this->creator = $xp->query("/record/creator")->item(0)->nodeValue;
    $this->date = $xp->query("/record/date")->item(0)->nodeValue;
    $this->description = $xp->query("/record/description")->item(0)->nodeValue;
    $this->format = $xp->query("/record/format")->item(0)->nodeValue;
    $this->identifier = $xp->query("/record/identifier")->item(0)->nodeValue;
    $this->language = $xp->query("/record/language")->item(0)->nodeValue;
    $this->publisher = $xp->query("/record/publisher")->item(0)->nodeValue;
    $this->related = $xp->query("/record/related")->item(0)->nodeValue;
    $this->rights = $xp->query("/record/rights")->item(0)->nodeValue;
    $this->source = $xp->query("/record/source")->item(0)->nodeValue;
    $this->subject = $xp->query("/record/subject")->item(0)->nodeValue;
    $this->title = $xp->query("/record/title")->item(0)->nodeValue;
    $this->type = $xp->query("/record/type")->item(0)->nodeValue;

    //  return result
    return $res;
  }

  /*
   *  public
   *  class PicaRecord
   *  parse dublin core data
   *  experimental, does not work, so commented out
   *  Used by n/a
  **/
  /*
  function readRDF_DC($str) {
    $doc = new DomDocument();
    $doc -> loadXML($str);
    $xp = new DomXPath($doc);
    $this->contributor = $xp->query("/rdf/dc:contributor")->item(0)->nodeValue;
    $this->coverage = $xp->query("/rdf/dc:coverage")->item(0)->nodeValue;
    $this->creator = $xp->query("/rdf/dc:creator")->item(0)->nodeValue;
    $this->date = $xp->query("/rdf/dc:date")->item(0)->nodeValue;
    $this->description = $xp->query("/rdf/dc:description")->item(0)->nodeValue;
    $this->format = $xp->query("/rdf/dc:format")->item(0)->nodeValue;
    $this->identifier = $xp->query("/rdf/dc:identifier")->item(0)->nodeValue;
    $this->language = $xp->query("/rdf/dc:language")->item(0)->nodeValue;
    $this->publisher = $xp->query("/rdf/dc:publisher")->item(0)->nodeValue;
    $this->related = $xp->query("/rdf/dc:related")->item(0)->nodeValue;
    $this->rights = $xp->query("/rdf/dc:rights")->item(0)->nodeValue;
    $this->source = $xp->query("/rdf/dc:source")->item(0)->nodeValue;
    $this->subject = $xp->query("/rdf/dc:subject")->item(0)->nodeValue;
    $this->title = $xp->query("/rdf/dc:title")->item(0)->nodeValue;
    $this->type = $xp->query("/rdf/dc:type")->item(0)->nodeValue;

    //  return result
    return $res;
  }
  */

  /*
   *  private
   *  class PicaRecord
   *  read tag content from string
   *  Used by PicaRecord->readData
  **/
  function readTag($val, $tag) {
     $x = strpos($val,"<".$tag.">");
     $y = strpos($val,"</".$tag.">");
     if ($x===FALSE) return "";
     return substr($val,$x+2+strlen($tag),$y-$x-2-strlen($tag));
  }

  /*
   *  public
   *  class PicaRecord
   *  read XML data
   *  used by unClient.php
  **/
  function readXmlData($str) {
     $lines = explode("\n", $str);
     //$res = "";
     foreach ($lines as $line) {
       $line = trim($line);
       if ($line=="") continue;
       $x = strpos($line, "<field tag=");
       if ($x!==0) continue;
       $y = strpos($line, "\"",12);
       $key = substr($line, 12, $y-12);
       $z = strpos($line, "</field>");
       if ($z===FALSE) continue;
       $val = substr($line,$y+2, $z-$y-2);
       $this->readData($key, $val);
       //$res .= "[$key] [$val]\n";
     }
     //return $res;
  }

  /*
   *  private
   *  class PicaRecord
   *  transcribe separators to diagnostic format
   *  Used by PicaRecord->getPicaPlus
  **/
  function transcribe_pica_rec($str) {
     return strtr( $str, array(
       "\x1F" => "$", "\x1E" => "\n", "\x0A" => "\n"
     ));
  }

  /*
   *  private
   *  class PicaRecord
   *  PICA charset handling (we want utf8)
   *  Used by PicaRecord->getPicaPlus
  **/
  function transcribe_pica_utf8($str) {
     return strtr( $str, array(
       "\xD1" => "ä", "\xD2" => "ö", "\xD3" => "ü", "\xD8" => "ß",
       "\xC1" => "Ä", "\xC2" => "Ö", "\xC3" => "Ü"
     ));
  }
}

/*
 *  end class PicaRecord
**/

/*
 *  class Picappn
 *  get a PICA record via PICA XML web interface, format and return it.
 *  Supported formats:
 *  - Array:		Picappn->getArray
 *  - Bibsonomy:	Picappn->getBibsonomy
 *  - BibTex:		Picappn->getBibTex
 *  - CSV:		Picappn->getCsv
 *  - Dublin Core:	Picappn->getDublinCore
 *  - RDF:		Picappn->getDublinCoreRDF
 *  - JSON:		Picappn->getJson
 *  - OpenURL KEV:	Picappn->getOpenUrlKev
 *  - PICA Plus:	Picappn->getPicaPlus
 *  - Plain:		Picappn->getPlain
 *  - Text:		Picappn->getText
 *  - XML:		Picappn->getXmlData
**/
class Picappn {
  //  declare some attributes
  var $opac;
  var $url;
  var $ppn;
  var $myself;

  //  a picarecord
  var $prec;

  /*
   *  constructor
   *  class Picappn
   *  PHP5 or later should use '__construct()'
  **/
  function Picappn() {
    //  get global user-defineable $opac_url
    global $opac_url;

    //  preset some attributes
    $this->opac = $opac_url;
    $this->ppn = 0;

    //  instantiate new PicaRecord()
    $this->prec = new PicaRecord();
  }

  /*
   *  public
   *  class Picappn
   *  convert for output using raw array data
   *  Used by Picappn->{getBibTex,getText}
  **/
  function convOutput($array,$text,$separator,$category,$subfield,$suffix) {
    //  check if $array not empty, otherwise just return
    if (empty($array)) return "";

    //  check if $subfield is given, otherwise just return category
    if (!empty($subfield)) {
      if (empty($array[$category][$subfield])) return "";
        $res = $text.$separator.$array[$category][$subfield].$suffix;
    } else {
      if (empty($array[$category])) return "";
        $res = $text.$separator.$array[$category].$suffix;
    }

    //  return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  convert for output using nice array data
   *  Used by Picappn->{getBibTex,getOpenUrlKev,getText}
  **/
  function convOutputNice($array_nice,$array_text,$key,$prefix,$suffix) {
    //  check if $array_nice not empty, otherwise just return
    if (empty($array_nice)) return "";
    //  check if $array_nice[$key] not empty, otherwise just return
    if (empty($array_nice[$key])) return "";

    //  build result
    $res = $array_text[$key].$prefix.$array_nice[$key].$suffix;

    //  return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  return record as Array
   *  Used by Picappn->{getBibTex,getCsv,getJson,getOpenUrlKev,getText}
  **/
  function getArray() {
    //  check if $this->ppn is not the preset value else return emptily
    if ($this->ppn == 0) return "";

    //  build result
    $res = $this->getData($this->ppn);
    $res = $this->prec->getArray($res);

    //  return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  return BibTeX record prefixed with $bibsonomy_url from PICA data.
   *  Used by Bibsonomy.php
  **/
  function getBibsonomy() {
    //  check if $this->ppn is not the preset value else return emptily
    if ($this->ppn == 0) return "";

    //  get BibTeX-Record and URL-encode it
    $res = $this->getBibTex();
    $res = urlencode($res);

    /*
     *  prefix url-encoded BibTeX-Record and prefix it with global,
     *  user-defineable $bibsonomy_url
    **/
    global $bibsonomy_url;
    $res = $bibsonomy_url.$res;

    //  return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  Return BibTex record from pica data
   *  Used by unAPI.php and Picappn->getBibsonomy
  **/
  function getBibTex() {
    //  check if $this->ppn is not the preset value else return emptily
    if ($this->ppn == 0) return "";

    //  get pica data as array
    $record_raw = $this->getArray($this->ppn);
    //  transform raw to nice array
    $record_nice = $this->prec->getArrayNice($record_raw);
    //  get keys name from array
    $keys_nice = $record_nice['_bibtex'];
    //  declare $res
    $res = "";

    //  make sure there is only one ISBN, prefer isbn13 to isbn10
    if (!empty($record_nice['isbn13'])) unset($record_nice['isbn10']);

    /*
     *  check type and choose right BibTeX-type
     *  "book" works quite well, "journal" not so
    **/
    if (preg_match('/^[AO][aeEfF]$/',$record_nice['twokr'])) {
      //  type: book
      $res = "@book {";

      //  create identifier
      $res .= $this->convOutput($record_raw,"","",'003@','x0',"");
      $res .= $this->convOutput($record_raw,"","",'011@','a',",\n");

      //  set the field which are fetched: author et al.
      $i = array('author', 'editor', 'title', 'series', 'address', 'publisher',
        'edition', 'year', 'isbn10', 'isbn13', 'link');

      //  fetch them from array, format and output them
      foreach ($i as $j) {
        $res .= $this->convOutputNice($record_nice,$keys_nice,$j," = {","},\n");
      }

      //  get call number, format and output it
      $res .= $this->convOutput($record_raw,"  note"," = {","209A/01",'a',"}");
    } elseif (preg_match('/^[AO]b$/',$record_nice['twokr'])) {
      //  type: journal
      $res = "@misc {";

      //  create identifier
      $res .= $this->convOutput($record_raw,"","",'003@','x0',"");
      $res .= $this->convOutput($record_raw,"","",'011@','a',",\n");

      //  set the field which are fetched: title et al.
      $i = array('title', 'address', 'publisher', 'issn', 'ezb_link');

      //  fetch them from array, format and output them
      foreach ($i as $j) {
        $res .= $this->convOutputNice($record_nice,$keys_nice,$j," = {","},\n");
      }

      //  get call number, format and output it
      $res .= $this->convOutput($record_raw,"  note"," = {","209A/01",'a',"}");
    }

    //  stripping et al.
    if (isset($res)) {
      //  check if last characters of $res are ",\n" and trim them
      $res = rtrim($res, ",\n");
      //  trailing element
      $res .= "\n}";
    }

    //  return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  Return CSV record from pica data
   *  Used by unAPI.php
  **/
  function getCsv() {
    //  check if $this->ppn is not the preset value else return emptily
    if ($this->ppn == 0) return "";

    //  get pica data as array
    $record_raw = $this->getArray($this->ppn);
    //  transform raw to nice array
    $record_nice = $this->prec->getArrayNice($record_raw);
    //  declare $res
    $res = '';

    //  set categories to used for CSV
    $i = array('ppn', 'author', 'editor', 'title', 'address', 'year', 'isbn10',
     'isbn13', 'issn', 'dnb_no', 'oclc_no', 'fdin', 'zdb_id', 'rvk');

    //  fetch them from array, format and output them
    foreach ($i as $j) {
      $res .= '"'.$record_nice["$j"].'"|'; 
    }

    //  stripping et al.
    if (isset($res)) {
      //  check if last characters of $res are ",\n" and trim them
      $res = rtrim($res, "|");
    }

    //  return result
    return $res;
  }

  /*
   *  private
   *  class Picappn
   *  get data via PICA XML interface
   *  Used by Picappn->{getArray,getDublinCore,getDublinCoreRDF,getPicaPlus,getPlain,getXmlData}
  **/
  function getData($ppn) {
    // get global $use_curl as switch for the following conditional
    global $use_curl;

    //  check whether curl-Module is available, if not available use fallback
    if (function_exists('curl_init') && $use_curl != 'no') {
      $ch = curl_init("$this->url");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch, CURLOPT_COOKIE, session_name().'='.session_id() ); 
      //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1 ); 
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $res = curl_exec($ch);
      curl_close($ch);
    } else {
      $res = file_get_contents("$this->url");
    }

    // return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  return simple dublin core record data from pica record
   *  Used by unAPI.php
  **/
  function getDublinCore() {
    //  retrieve data
    $res = $this->getData($this->ppn);

    //  format data
    $res = $this->prec->getDublinCore($res);

    //  return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  return dublin core rdf data from pica record
   *  Used by unAPI.php
  **/
  function getDublinCoreRDF() {
    //  retrieve data
    $res = $this->getData($this->ppn);

    //  put it to some XML structure
    $res = $this->prec->getXmlData($res); //parses dublin core

    //  check if $this->ppn is not the preset value else return emptily
    if (empty($res)) return "";

    //  format
    $res  = "<?xml version=\"1.0\"?>\n";
    $res .= "<!DOCTYPE rdf:RDF PUBLIC ";
    $res .= "\"-//DUBLIN CORE//DCMES DTD 2002/07/31//EN\"\n";
    $res .= "\"http://dublincore.org/documents/2002/07/31/";
    $res .= "dcmes-xml/dcmes-xml-dtd.dtd\">\n";
    $res .= "<rdf:RDF xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"
             xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n";
    $res .= "<rdf:Description rdf:about=\""
            .$this->myself."?ppn=".$this->ppn."&amp;format=rdf"."\">\n";
    $res .= "<dc:contributor>".$this->prec->contributor."</dc:contributor>\n";
    $res .= "<dc:coverage>".$this->prec->coverage."</dc:coverage>\n";
    $res .= "<dc:creator>".$this->prec->creator."</dc:creator>\n";
    $res .= "<dc:date>".$this->prec->date."</dc:date>\n";
    $res .= "<dc:description>".$this->prec->description."</dc:description>\n";
    $res .= "<dc:format>".$this->prec->format."</dc:format>\n";
    $res .= "<dc:identifier>".$this->prec->identifier."</dc:identifier>\n";
    $res .= "<dc:language>".$this->prec->language."</dc:language>\n";
    $res .= "<dc:publisher>".$this->prec->publisher."</dc:publisher>\n";
    $res .= "<dc:related>".$this->prec->related."</dc:related>\n";
    $res .= "<dc:rights>".$this->prec->rights."</dc:rights>\n";
    $res .= "<dc:source>".$this->prec->source."</dc:source>\n";
    $res .= "<dc:subject>".$this->prec->subject."</dc:subject>\n";
    $res .= "<dc:title>".$this->prec->title."</dc:title>\n";
    $res .= "<dc:type>".$this->prec->type."</dc:type>\n";
    $res .= "</rdf:Description>\n";
    $res .= "</rdf:RDF>\n";

    //  return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  return JSON record from pica data
   *  Used by unAPI.php
  **/
  function getJson() {
    //  check if $this->ppn is not the preset value else return emptily
    if ($this->ppn == 0) return "";

    //  get PICA record as array
    $res = $this->getArray();
    //  encode array as JSON, requirements: PHP 5 >= 5.2.0, PECL json >= 1.2.0
    $res = json_encode($res);

    //  return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  return OpenURL KEV record from pica data
   *  Used by unAPI.php and Coins.php
  **/
  function getOpenUrlKev() {
    //  check if $this->ppn is not the preset value else return emptily
    if ($this->ppn == 0) return "";

    //  get pica data as array
    $record_raw = $this->getArray($this->ppn);
    //  transform raw to nice array
    $record_nice = $this->prec->getArrayNice($record_raw);
    //  get keys' name from array
    $keys_nice = $record_nice['_openurlkev'];
    //  declare $res
    $res = "";

    //  get global, user-defineable variable $rfr_id_hostname
    global $rfr_id_hostname;

    //  set immutable context objects for OpenURL
    //  version of the ContextObject
    $res .= 'ctx_ver=Z39.88-2004';
    //  referrer identifier
    $res .= '&amp;rfr_id='.urlencode('info:sid/'.$rfr_id_hostname.':generator');

    //  make sure there is only one ISBN, prefer isbn13 to isbn10
    if (!empty($record_nice['isbn13'])) unset($record_nice['isbn10']);

    //  check type and choose right OpenURL-type
    if (preg_match('/^[AO][aeEfF]$/',$record_nice['twokr'])) {
      //  make $keys_nice['title'] more standard compliant
      $keys_nice['title'] = 'rft.btitle';

      //  type: book
      $res .= '&amp;rft_val_fmt='.urlencode('info:ofi/fmt:kev:mtx:book');
      $res .= '&amp;rft.genre=book&amp;';

      //  set the field which are fetched: author et al.
      $i = array('author', 'editor', 'title', 'series', 'address', 'publisher',
        'edition', 'year', 'isbn10', 'isbn13', 'pages');

      //  fetch them from array, format, url-encode and output them
      foreach ($i as $j) {
        $record_nice[$j] = urlencode($record_nice[$j]);
        $res .= $this->convOutputNice($record_nice,$keys_nice,$j,"=","&amp;");
      }
    } elseif (preg_match('/^[AO]b$/',$record_nice['twokr'])) {
      //  type: journal/article
      $res .= '&amp;rft_val_fmt='.urlencode('info:ofi/fmt:kev:mtx:journal');
      $res .= '&amp;rft.genre=article&amp;';

      //  set the field which are fetched: author et al.
      $i = array('title', 'address', 'publisher', 'issn');

      //  fetch them from array, format, url-encode and output them
      foreach ($i as $j) {
        $record_nice[$j] = urlencode($record_nice[$j]);
        $res .= $this->convOutputNice($record_nice,$keys_nice,$j,"=","&amp;");
      }
    }

    //  strip trailing "&amp;" if necessary
    if (isset($res)) {
      $res = rtrim($res, "&amp;");
    }

    //  return result
    return $res;
  }

  /* 
   *  public
   *  class Picappn
   *  return formatted PICA Plus record
   *  Used by unAPI.php
  **/
  function getPicaPlus() {
    //  check if $this->ppn is not the preset value else return emptily
    if ($this->ppn == 0) return "";

    //  retrieve record
    $res = $this->getData($this->ppn);

    //  format
    $res = $this->prec->getPicaPlus($res);

    //  set prefix
    $head = "<?xml version='1.0' encoding='UTF-8'?>\n";
    $head .= "<record url=\"$this->url\">\n";

    //  set suffix
    $tail = "</record>\n";

    //  return result
    return $head.$res.$tail;
  }

  /*
   *  public
   *  class Picappn
   *  return formatted plain (not well formed XML) record
   *  Used by unAPI.php
  **/
  function getPlain() {
    //  check if $this->ppn is not the preset value else return emptily
    if ($this->ppn == 0) return "";

    //  retrieve record
    $res = $this->getData($this->ppn);

    //  put to some XML structure
    $res = $this->prec->getXmlData($res);

    //  return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  Return simple text record from pica data
   *  Used by unAPI.php
  **/
  function getText() {
    //  check if $this->ppn is not the preset value else return emptily
    if ($this->ppn == 0) return "";

    //  get pica data as array
    $record_raw = $this->getArray($this->ppn);
    //  transform raw to nice array
    $record_nice = $this->prec->getArrayNice($record_raw);
    //  get keys' names from array
    $keys_nice = $record_nice['_text'];
    //  declare $res
    $res = "";

    //  check type and choose right type
    if (preg_match('/^[AO][aeEfF]$/',$record_nice['twokr'])) {
      //  type: book
      $res .= $keys_nice['type'].":\t\tMonographie\n";

      //  set the field which are fetched: author et al.
      $i = array('author', 'editor', 'title', 'series', 'address', 'publisher',
        'edition', 'year', 'isbn10', 'isbn13', 'pages', 'rvk', 'ddc', 'link',
        'toc', 'ppn', 'dnb_no', 'oclc_no', 'fdin');

      //  fetch them from array, format and output them
      foreach ($i as $j) {
        $res .= $this->convOutputNice($record_nice,$keys_nice,$j,":\t\t","\n");
      }

      //  call number
      $i = 1;
      $j = 0;
      while ( $i <= 99 ) {
        if (($i <= 9) && ($j == 0)) {
          $j = "0".$i;
        } elseif (($i > 9) && ($j == 0)) {
          $j = $i;
        }
        $res .= $this->convOutput($record_raw,"Signatur",":\t","209A/$j",'a',"\n");
        $i++;
        if ($i <= 9 ) {
          $j = "0".$i;
        } else {
          $j = $i;
        }
        if (empty($record_raw["209A/$j"]['a'])) break;
      }
    } elseif (preg_match('/^[AO]b$/',$record_nice['twokr'])) {
      //  type: journal
      $res .= $keys_nice['type'].":\t\tZeitschrift\n";

      //  set the field which are fetched: title et al.
      $i = array('title', 'address', 'publisher', 'issn', 'everlauf', 'rvk',
        'zdb_id', 'ppn', 'ezb_link');

      //  fetch them from array, format and output them
      foreach ($i as $j) {
        $res .= $this->convOutputNice($record_nice,$keys_nice,$j,":\t\t","\n");
      }

      //  call number
      $i = 1;
      $j = 0;
      while ( $i <= 99 ) {
        if (($i <= 9) && ($j == 0)) {
          $j = "0".$i;
        } elseif (($i > 9) && ($j == 0)) {
          $j = $i;
        }
        $res .= $this->convOutput($record_raw,"Signatur",":\t","209A/$j",'a',"\n");
        $i++;
        if ($i <= 9 ) {
          $j = "0".$i;
        } else {
          $j = $i;
        }
        if (empty($record_raw["209A/$j"]['a'])) break;
      }
    }

    //  return result
    return $res;
  }

  /*
   *  public
   *  class Picappn
   *  return XML formatted PICA record
   *  Used by unAPI.php
  **/
  function getXmlData() {
    //  check if $this->ppn is not the preset value else return emptily
    if ($this->ppn == 0) return "";

    //  retrieve record
    $res = $this->getData($this->ppn);

    //  put it to some XML structure
    $res = $this->prec->getXmlData($res);

    //  define prefix
    $head = "<?xml version='1.0' ?>\n";
    //$head .= "<record>\n";
    //$head .= "<record url=\"$this->opac\">\n";
    //$head .= "<record url=\"$this->url\">\n";
    //$head .= "<record url=\"".htmlspecialchars($this->url)."\">\n";
    //$head .= "<record url=\"".$this->opac.$this->ppn."\">\n";
    $head .= "<record url=\"".htmlentities($this->url)."\">\n";

    //  define suffix
    $tail = "</record>\n";

    //  return result
    return $head.$res.$tail;
  }

  /*  public
   *  class Picappn
   *  set attribute Picappn->opac
   *  Used by n/a
  **/
  function setOpac($opac) {
    $this->opac = $opac;
  }

  /*  public
   *  class Picappn
   *  set PPN, the unAPI identifier
   *  Used by Bibsonomy.php, Coins.php and unAPI.php
  **/
  function setPpn($ppn) {
    //  get global, user-defineable $pattern_ppn
    global $pattern_ppn;

    /*  
     *  for input sanitising - check if $ppn can be matched against a regex
     *  which defines allowed input, if correct set some attributes
    **/
    if (preg_match($pattern_ppn,$ppn)) {
      $this->ppn = $ppn;
      $this->url = $this->opac.$ppn."&PLAIN=ON";
    } else {
      //  for debugging only
      //echo foo;
    }
    $this->myself = $_SERVER['PHP_SELF'];
  }
}

/*
 *  end class Picappn
**/

/* 
 *  function final_result
 *  Return result or 404
 *  Used by unAPI.php
**/
function final_result($res, $content_type) {
  /*  if $res is not empty, return it, otherwise act accordingly to unAPI
   *  specification
  **/
  if (!empty($res)) {
    //  send header
    header("Content-type: $content_type");
    //  return result
    echo $res;
  } else {
    header('HTTP/1.0 404 Not Found');
  }
}

/*
 *  end function final_result
**/

?>
