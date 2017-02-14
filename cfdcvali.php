<?php
//
// +---------------------------------------------------------------------------+
// | cfdcvali.php : Recibe un archivo XML y valida que cumpla con los requi-   |
// |               sitos del SAT,  certificado CSD autorizado,                 |
// |               estructura contra esquema, sello contra cadena original     |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2011  Fabrica de Jabon la Corona, SA de CV                  |
// +---------------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software               |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA|
// +---------------------------------------------------------------------------|
// | Autor: Fernando Ortiz <fortiz@lacorona.com.mx>                            |
// +---------------------------------------------------------------------------+
// | 22/feb/2011 Primera version 'publica' con validacion basicas CFD y CFDI.  |
// |                                                                           |
// | 17/mar/2011 Se le agraga el content-type, charset=utf-8 para que se vea   |
// |             bien los caracteres acentuados @erick                         |
// |                                                                           |
// |             se le agrega *_replace para quitar saltos de lineas al        |
// |             certificado @alberto850822                                    |
// |                                                                           |
// | 02/jun/2011 Se valida el sello del TFD                                    |
// |                                                                           |
// | 01/jul/2011 Elimina addenda cfdi: ...                                     |
// |                                                                           |
// | 06/sep/2012 Acepta cualquier complemento, versiones 2.0, 2.2, 3.0 y 3.2   |
// |                                                                           |
// | 12/feb/2014 Valida que exista CFDI en portal del SAT                      |
// |             ftp://ftp2.sat.gob.mx/asistencia_servicio_ftp/publicaciones/cfdi/WS_ConsultaCFDI.pdf
// |                                                                           |
// | 28/mar/2014 ftp2 del sat esta saturado, almacenar localmente certificados |
// |                                                                           |
// | 30/jul/2014 Se quita validacion de folios ya no esta en el ftp del SAT    |
// ftp://ftp2.sat.gob.mx/agti_servicio_ftp/verifica_comprobante_ftp/FoliosCFD.txt
// |                                                                           |
// | 19/sep/14 utf8_encode para usar el wwebservice del sat                    |
// |  gracias a Fernando Colin                                                 |
// |                                                                           |
// | 30/ene/15 Validacion de constancias de retenciones ademas de CFD          |
// |                                                                           |
// | 30/ene/15 Nueva URL para descarga de certificados del SAT (Rene Calderon) |
// |           https://rdc.sat.gob.mx/rccf/$p1/$p2/$p3/$p4/$p5/$no_cert.cer    |
// |                                                                           |
// | 17/mar/15 Nueva estructura de directorio para repositorio GIT             |
// |               xsd / xslt                                                  |
// |                                                                           |
// | 19/mar/15 Valida autoridad certificadora, para comprobar certificado      |
// |           sea emitido por el SAT                                          |
// +---------------------------------------------------------------------------+
//
?>
<HTML>
<HEAD>
<meta http-equiv="Expires" content="Mon, 26 Jul 1997 05:00:00 GMT">
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<TITLE>Validacion de documentos electronicos (XML) CFD/CFDI/Retenciones</TITLE>
<link rel="STYLESHEET" href="fortiz.css" media="screen" type="text/css">
<?php
// Es solo para llevar mi estadistica en Google Analytics, ustedes quitenlo ...
@include("urchin/corona.html");
?>
</HEAD>
<BODY>
<div align=center>
<H1>Validacion de Documentos Electronicos XML</H1>
<H2>CFD/CFDI/Retenciones</H2>
<br><hr><br>
<form method='post' enctype='multipart/form-data'>
 Archivo <input type='file' name='arch' size='60'>
 <INPUT TYPE="submit" VALUE="Valida" >
 <br><br><hr>
</FORM>
<a href=https://github.com/fortiz/sat>Codigo Fuente</a>
<?php
error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING|E_DEPRECATED));
if (trim($_FILES['arch']['name'])=="") die("no arch");
if ($_FILES['arch']['error']==1 || $_FILES['arch']['size']==0) {
    echo "<h1><red>NO SUBIO archivo, demasiado grande</red></h1>";
    die();
} 
$arch = $_FILES['arch']['tmp_name'];
$texto = file_get_contents($arch);
unlink($arch);
if( substr($texto, 0,3) == pack("CCC",0xef,0xbb,0xbf) ) { 
    $texto = substr($texto, 3); 
    echo "<h3>Tenia BOM, Eliminado</h3>";
} 
if (!mb_check_encoding($texto,"utf-8")) {
    echo "<h3>Error en XML, no esta en UTF-8!</h3>";
}
$nuevo = utf8_decode($texto);
if (mb_check_encoding($nuevo,"utf-8")) {
    echo "<h3>Sigue siendo utf8, usa decode</h3>";
    $texto = $nuevo;
}

///////////////////////////////////////////////////////////////////////////
// Quita Addenda solo valida fiscal
$texto = preg_replace('{<Addenda.*/Addenda>}is', '<Addenda/>', $texto);
$texto = preg_replace('{<cfdi:Addenda.*/cfdi:Addenda>}is', '<cfdi:Addenda/>', $texto);
///////////////////////////////////////////////////////////////////////////
// Para ver en la Pantalla el XML recibido  (sin addenda) 
/*
 * Paquetes para ver bonito el codigo en la pantalla
 * No son necesarios 'para validar'
 * solo son para 'ver bonito' lo que se valida
 *
 * Son gratuitos se obtienen de pear.php.net
 * http://pear.php.net/package/XML_Beautifier/
 * http://pear.php.net/package/Text_Highlighter/
 *
 * Alguna dependencia de pear se baja del mismo pear
 * http://pear.php.net/package/XML_Parser
 * */
require_once 'XML/Beautifier.php';
require_once 'Text/Highlighter.php';
$fmt = new XML_Beautifier();
$fmt->setOption("multilineTags", TRUE);
$paso = $fmt->formatString($texto);
if (substr($paso,0,10)!="XML_Parser") $texto=$paso; // XML correctamente formado
$hl =& Text_Highlighter::factory('XML',array('numbers'=>HL_NUMBERS_TABLE));
echo "<div style='height:300px; overflow:auto';";
echo $hl->highlight($texto);
echo "</div>";
/////////////////////////////////////////////////////////////////////////////

libxml_use_internal_errors(true);   // Gracias a Salim Giacoman
$xml = new DOMDocument();
$ok = $xml->loadXML($texto);
if (!$ok) {
   display_xml_errors(); 
   die();
}

if (strpos($texto,"cfdi:Comprobante")!==FALSE) {
    $tipo="cfdi";
} elseif (strpos($texto,"<Comprobante")!==FALSE) {
    $tipo="cfd";
} elseif (strpos($texto,"retenciones:Retenciones")!==FALSE) {
    $tipo="retenciones";
} else {
    die("Tipo de XML no identificado ....");
}

////////////////////////////////////////////////////////////////////////////
//   Con el arbol DOM buscamos los atributos
if ($tipo=="retenciones") {
    $root = $xml->getElementsByTagName('Retenciones')->item(0);
    $Version = $root->getAttribute("Version");
} else {
    $root = $xml->getElementsByTagName('Comprobante')->item(0);
    $version = $root->getAttribute("version");
    if ($version==null) $version = $root->getAttribute("Version");
}
$Receptor = $root->getElementsByTagName('Receptor')->item(0);
$Emisor = $root->getElementsByTagName('Emisor')->item(0);

$data['seri'] = $root->getAttribute("serie");
$data['fecha'] = $root->getAttribute("fecha");
$data['noap'] = $root->getAttribute("noAprobacion");
$data['anoa'] = $root->getAttribute("anoAprobacion");

$data['tipo']=$tipo;
if ($tipo=="retenciones") {
    $rfc = $Emisor->getAttribute('RFCEmisor');
    $data['rfc'] = utf8_decode($rfc);
    $rfc = $Receptor->getAttribute('RFCRecep');
    $data['rfc_receptor'] = utf8_decode($rfc);
    $data['version'] = $root->getAttribute("Version");
    $data['no_cert'] = $root->getAttribute("NumCert");
    $data['cert'] = $root->getAttribute("Cert");
    $data['sell'] = $root->getAttribute("Sello");
    $Totales = $root->getElementsByTagName('Totales')->item(0);
    $data['total'] = $Totales->getAttribute("montoTotGrav");
} else {
    $data['version'] = $version;
    if ($version=="3.3") { // Mayusculas
        $data['total'] = $root->getAttribute('Total');
        $data['no_cert'] = $root->getAttribute('NoCertificado');
        $data['cert'] = $root->getAttribute('Certificado');
        $data['sell'] = $root->getAttribute('Sello');
        $rfc = $Emisor->getAttribute('Rfc');
        $data['rfc'] = utf8_decode($rfc);
        $rfc = $Receptor->getAttribute('Rfc');
        $data['rfc_receptor'] = utf8_decode($rfc);
    } else { // NO es 3.3, es 3.2 o anterior minusculas
        $data['total'] = $root->getAttribute('total');
        $data['no_cert'] = $root->getAttribute('noCertificado');
        $data['cert'] = $root->getAttribute('certificado');
        $data['sell'] = $root->getAttribute('sello');
        $rfc = $Emisor->getAttribute('rfc');
        $data['rfc'] = utf8_decode($rfc);
        $rfc = $Receptor->getAttribute('rfc');
        $data['rfc_receptor'] = utf8_decode($rfc);
    } // version 3.3
} // Retencion o CFDI

$TFD = $root->getElementsByTagName('TimbreFiscalDigital')->item(0);
if ($TFD!=null) {
    $data['sellocfd'] = $TFD->getAttribute("selloCFD");
    $data['sellosat'] = $TFD->getAttribute("selloSAT");
    $data['no_cert_sat'] = $TFD->getAttribute("noCertificadoSAT");
    $data['uuid'] = $TFD->getAttribute("UUID");
} else {
    $data['sellocfd'] = null;
    $data['sellosat'] = null;
    $data['no_cert_sat'] = null;
    $data['uuid'] = null;
}
//   Valores guardados en un arreglo para ser usado por las funciones
/////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////
//  Conexion a la base de datos para leer la lista de CSD
//  autorizados por el SAT
//
//  http://www.lacorona.com.mx/fortiz/sat/valida.php  para ver estas tablas
//
//  myconn es una conexion a MI BASE DE DATOS
//    yo uso adodb http://phplens.com/lens/adodb/docs-adodb.htm
//    pero ya los parametros de conexion a mi base de datos no te digo ;)
require_once "myconn/myconn.inc.php";
$conn=myconn();
////////////////////////////////////////////////////////////////////////////

valida_certificado();
valida_xsd();
if ($data['tipo']=="cfdi") { // por lo pronto semantica solo para CFDI
    valida_semantica();
}
valida_sello();
if ($data['sellosat']!="") {
   valida_sello_tfd();
   if ($data['tipo']=="cfdi") {
        valida_en_sat(); // Por lo pronto retenciones no se valida en SAT
   }
}

// {{{ Valida certificado
//
//  ftp://ftp2.sat.gob.mx/agti_servicio_ftp/verifica_comprobante_ftp/CSD.txt
//
//      Table "public.cfdcsd"
//      Column      |            Type             | Modifiers 
//------------------+-----------------------------+-----------
// no_serie         | character(20)               | 
// fec_inicial_cert | timestamp without time zone | 
// fec_final_cert   | timestamp without time zone | 
// rfc              | character(13)               | 
// edo_certificado  | character(1)                | 
//    Indexes:
// "i11_39773_10052" UNIQUE, btree (no_serie)
// "cfdcsd_rfc" btree (rfc, fec_inicial_cert)
//
function valida_certificado() {
global $data,$conn;
$qry = "select * from cfdcsd where RFC='".$data['rfc']."' and 
                                   no_serie='".$data['no_cert']."'";
$rowcsd = $conn->getrow($qry);
if (trim($rowcsd['rfc'])==$data['rfc'] && 
    trim($rowcsd['no_serie'])==$data['no_cert']) {
    $fini=$rowcsd['fec_inicial_cert'];
    $ffin=$rowcsd['fec_final_cert'];
    echo "<h3>Certificado valido del $fini al $ffin</h3>";
} else {
    if (strlen($data['rfc'])==13) {
        echo "<h3><font color=orange>CSD no encontrado, pero puede ser FIEL ....</font></h3>";
    } else { 
        echo "<h3>Certificado no autorizado en CSD.txt</h3>";
    }
}
echo "<hr>";
}
// }}} 
// {{{ Valida_XSD
function valida_xsd() {
    /*
     * Todos los archivos que se requieren para hacer la validacion
     * fueron descargados del portal del SAT pero los tengo localmente
     * almacenados en mi maquina para que las validaciones sean mas rapidas.
     * Ademas el archivo prinicpal cfdv32.xsd esta 'un poco' modifcado para
     * que importe los complementos
     *
     * */
global $data, $xml,$texto;
libxml_use_internal_errors(true);   // Gracias a Salim Giacoman
if ($data['tipo']=="retenciones") {
    switch ($data['version']) {
      case "1.0":
        echo "Version 1.0 Retenciones<br>";
        $ok = $xml->schemaValidate("xsd/retencionpagov1.xsd");
        break;
      default:
        $ok = false;
        echo "Version invalida $tipo ".$data['version']."<br>";
    }
} else {
    switch ($data['version']) {
      case "2.0":
        echo "Version 2.0 CFD<br>";
        $ok = $xml->schemaValidate("xsd/cfdv2complemento.xsd");
        break;
      case "2.2":
        echo "Version 2.2 CFD<br>";
        $ok = $xml->schemaValidate("xsd/cfdv22complemento.xsd");
        break;
      case "3.0":
        echo "Version 3.0 (CFDI)<br>";
        $ok = $xml->schemaValidate("xsd/cfdv3complemento.xsd");
        break;
      case "3.2":
        echo "Version 3.2 CFDI<br>";
        $ok = $xml->schemaValidate("xsd/cfdv32.xsd");
        break;
      case "3.3":
        echo "Version 3.3 CFDI<br>";
        $ok = $xml->schemaValidate("xsd/cfdv33.xsd");
        break;
      default:
        $ok = false;
        echo "Version invalida $tipo ".$data['version']."<br>";
    }
}
if ($ok) {
    echo "<h3>Esquema valido</h3>";
} else {
    echo "<h3>Estructura contra esquema incorrecta</h3>";
    display_xml_errors(); 
}
echo "<hr>";
}
// }}} Valida XSD
// {{{ Valida_semantica
function valida_semantica() {
    /* La sintaxis se valida contra el XSD, pero para algunos complementos
     * dieron varias reglas de validacion adicionales, a esas le llame
     *
     *  Reglas de semantica
     *        ine
     *        cce
     *        nomina
     *        cfdi 3.3
     * */
    global $texto,$data;
     if (strpos($texto,"ine:INE")!==FALSE) {
         semantica_ine();
     }
     if (strpos($texto,"cce:ComercioExterior")!==FALSE) {
         semantica_cce();
     }
     if (strpos($texto,"cce11:ComercioExterior")!==FALSE) {
         semantica_cce11();
     }
     if (strpos($texto,"nomina12:Nomina")!==FALSE) {
         semantica_nomi12();
     }
     if (strpos($texto,"cfdi:Comprobante")!==FALSE && $data['version']=="3.3") {
         semantica_cfdi();
     }
}
// }}} Valida semantica
// {{{ Valida semantica ine
function semantica_ine() {
    global $xml, $conn;
    echo "<h2>Semantica INE</h2>";
    require_once("semantica_ine.php");
    $ine = new Ine();
    $ine->valida($xml,$conn);
    echo "<h2>$ine->status</h2>";
}
// }}} 
// {{{ Valida semantica cce
function semantica_cce() {
    global $xml, $conn;
    echo "<h2>Semantica CCE</h2>";
    require_once("semantica_cce.php");
    $cce = new Cce();
    $cce->valida($xml,$conn);
    echo "<h2>$cce->codigo</h2>";
    echo "<hr/>";
}
// }}} Valida semantica cce
// {{{ Valida semantica cce11
function semantica_cce11() {
    global $xml, $conn;
    echo "<h2>Semantica CCE Version 1.1</h2>";
    require_once("semantica_cce11.php");
    $cce = new Cce();
    $cce->valida($xml,$conn);
    echo "<h2>$cce->codigo</h2>";
    echo "<hr/>";
}
// }}} Valida semantica cce
// {{{ Valida semantica nomi12
function semantica_nomi12() {
    global $xml, $conn;
    echo "<h2>Semantica NOMINA 12</h2>";
    require_once("semantica_nomi12.php");
    $nomi = new Nomi12();
    $nomi->valida($xml,$conn);
    echo "<h2>$nomi->codigo</h2>";
    echo "<hr/>";
}
// }}} Valida semantica nomi12
// {{{ Valida semantica cfdi
function semantica_cfdi() {
    global $xml, $conn;
    echo "<h2>Semantica CFDI 3.3</h2>";
    require_once("semantica_cfdi.php");
    $sem = new Sem_CFDI();
    $sem->valida($xml,$conn);
    echo "<h2>$sem->codigo</h2>";
    echo "<hr/>";
}
// }}} Valida semantica nomi12
// {{{ Valida Sello
function valida_sello() {
    /*
     * Todos los archivos que se requieren para generar la cadena original
     * fueron descargados del portal del SAT pero los tengo localmente
     * almacenados en mi maquina para que el proceso sea mas rapido.
     *
     * Todos los archivos estan modificacion por el numero de version 2 a 1,
     * para que no mande warning PHP
     *
     * */
global $data, $xml;

$xsl = new DOMDocument;
if ($data['tipo']=="retenciones") {
    switch ($data['version']) {
      case "1.0":
          $xsl->load('xslt/retenciones.xslt');
          $algo =OPENSSL_ALGO_SHA1;
          break;
      default:
          echo "version incorrecta ".$data['tipo']." ".$data['version']."\n";
          break;
    }
} else {
    switch ($data['version']) {
      case "2.0":
          $xsl->load('xslt/cadenaoriginal_2_0.xslt');
          if (substr($data['fecha'],0,4)<2011) {
              echo "md5 \n";
              $algo = OPENSSL_ALGO_MD5;
          } else {
              echo "sha1 \n";
              $algo =OPENSSL_ALGO_SHA1;
          }
          break;
      case "2.2":
          echo "2.2\n";
          $xsl->load('xslt/cadenaoriginal_2_2.xslt');
          echo "sha1 \n";
          $algo = OPENSSL_ALGO_SHA1;
          break;
      case "3.0":
          $xsl->load('xslt/cadenaoriginal_3_0.xslt');
          if (substr($data['fecha'],0,4)<2011) {
              echo "md5 \n";
              $algo = OPENSSL_ALGO_MD5;
          } else {
              echo "sha1 \n";
              $algo =OPENSSL_ALGO_SHA1;
          }
          break;
      case "3.2":
          echo "3.2\n";
          $xsl->load('xslt/cadenaoriginal_3_2.xslt');
          echo "sha1 \n";
          $algo = OPENSSL_ALGO_SHA1;
          break;
      case "3.3":
          echo "3.3\n";
          $xsl->load('xslt/cadenaoriginal_3_3.xslt');
          echo "sha1 \n";
          $algo = OPENSSL_ALGO_SHA1;
          break;
      default:
          echo "version incorrecta ".$data['tipo']." ".$data['version']."\n";
          break;
    }
}

$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl); 
$cadena = $proc->transformToXML($xml);
echo "Cadena Original<br><p align=left>$cadena</p><br>";
if ($algo==OPENSSL_ALGO_SHA1) {
    $sha1=sha1($cadena);
    echo "hash sha1=$sha1<br>";
} else {
    $md5=md5($cadena);
    echo "hash md5=$md5<br>";
}

if (!mb_check_encoding($cadena,"utf-8")) {
    echo "<h3>Error no esta en UTF-8!</h3>";
}

/* 
 * El domicilio es opcional, pero si no lo ponemos el xslt del SAT genera 
 * doble pip en el pais ..., dice que el sello es correcto pero los PACs 
 * que validan bien lo rechazan ... 
 * */
$doble = preg_match('/.\|\|./',$cadena);
if ($doble===1) {
    echo "<h3><font color=red>La cadena tiene doble pipes en medio ...</font></h3>";
}
// Primer certificado (o unico) del emisor
// Los demas certificados es del PAC, Timbre, etc.
$pem = (sizeof($data['cert'])<=1) ? $data['cert'] : $data['cert'][0];

$pem = preg_replace("/[\n|\r|\n\r]/", '', $pem);
$pem = preg_replace('/\s\s+/', '', $pem); 
// Si no incluye el certificado bajarlo del FTP del sat ....
if (strlen($pem)==0) {
    echo "No incluye certificado interno, descargarlo del FTP del sat ...<br>";

    $pem=get_sat_cert($data['no_cert']);

}

$cert = "-----BEGIN CERTIFICATE-----\n".chunk_split($pem,64)."-----END CERTIFICATE-----\n";
$pubkeyid = openssl_get_publickey(openssl_x509_read($cert));
if (!$pubkeyid) {
    echo "Certificado interno Incorrecto, descargarlo del FTP del sat ...<br>";
    $pem=get_sat_cert($data['no_cert']);
    $cert = "-----BEGIN CERTIFICATE-----\n".chunk_split($pem,64)."-----END CERTIFICATE-----\n";
    $pubkeyid = openssl_get_publickey(openssl_x509_read($cert));

}
valida_ca(openssl_x509_read($cert));
$ok = openssl_verify($cadena, 
                     base64_decode($data['sell']), 
                     $pubkeyid, 
                     $algo);
if ($ok == 1) {
    echo "<h3>Sello ok</h3>";
} else {
    echo "<h3>Sello incorrecto</h3>";
    while ($msg = openssl_error_string())
        echo $msg. "\n";
}
openssl_free_key($pubkeyid);
echo "<hr>";
$paso = openssl_x509_parse($cert);
$serial = convierte($paso['serialNumber']);
if ($serial!=$data['no_cert']) {
    echo "Serie reportada ".$data['no_cert']." serie usada $serial<br>";
}

}
// }}} Valida Sello
// {{{ Valida CA (Autoridad Certificadora)
function valida_ca($pubkeyid) {
    $ca = array(__DIR__."/raiz/");
    $ok = openssl_x509_checkpurpose($pubkeyid, X509_PURPOSE_ANY, $ca);
    if ($ok) {
        echo "<h3>Certificado emitido por el SAT</h3>";
    } else {
        echo "<h3>Certificado apocrifo, No es del SAT</h3>";
    }
}
// }}} Valida Sello
// {{{ Valida Sello TFD
function valida_sello_tfd() {
global $data, $texto;

if ($data['sell'] != $data['sellocfd']) {
    echo "<h3>sello Comprobante diferente que sello TFD!, manipulado?</h3>";
}

// Quita la parte del CFDI
$texto_tfd = preg_replace('{<cfdi:Comprobante.*<tfd:}is', '<tfd:', $texto);
$texto_tfd = preg_replace('{<retenciones:Retenciones.*<tfd:}is', '<tfd:', $texto_tfd);
$texto_tfd = trim(preg_replace('{/>.*$}is', '/>', $texto_tfd));
// Si no tiene el namespace definido, se agrega
if (strpos($texto_tfd,"xmlns:tfd")===FALSE) {

    $texto_tfd = substr($texto_tfd,0,-2).' xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" />';
}
// echo htmlspecialchars($texto_tfd);
// Solo se quedo el tfd:
$xml_tfd = new DOMDocument();
$ok = $xml_tfd->loadXML($texto_tfd);

$xsl = new DOMDocument;
$xsl->load('xslt/cadenaoriginal_TFD_1_0.xslt');
$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl); 

$cadena = $proc->transformToXML($xml_tfd);
echo "Cadena Original TFD<br><p align=left>$cadena</p><br>";

if (!mb_check_encoding($cadena,"utf-8")) {
    echo "<h3>Error no esta en UTF-8!</h3>";
}

// Certificado del PAC
$pem=get_sat_cert($data['no_cert_sat']);

$cert = "-----BEGIN CERTIFICATE-----\n".chunk_split($pem,64)."-----END CERTIFICATE-----\n";
// file_put_contents("/tmp/llave.cer.pem",$cert);
$pubkeyid = openssl_get_publickey(openssl_x509_read($cert));
$ok = openssl_verify($cadena, 
                     base64_decode($data['sellosat']), 
                     $pubkeyid, 
                     OPENSSL_ALGO_SHA1);
if ($ok == 1) {
    echo "<h3>Sello TFD ok</h3>";
} else {
    echo "<h3>Sello TFD incorrecto</h3>";
    while ($msg = openssl_error_string())
        echo $msg. "\n";
}
openssl_free_key($pubkeyid);
echo "<hr>";

}
// }}} Valida Sello
// {{{ Valida este XML en el servidor del SAT 
// ftp://ftp2.sat.gob.mx/asistencia_servicio_ftp/publicaciones/cfdi/WS_ConsultaCFDI.pdf
function valida_en_sat() {
    global $data;
    $url = "https://consultaqr.facturaelectronica.sat.gob.mx/consultacfdiservice.svc?wsdl";
    $soapclient = new SoapClient($url);
    $rfc_emisor = utf8_encode($data['rfc']);
    $rfc_receptor = utf8_encode($data['rfc_receptor']);
    $impo = (double)$data['total'];
    $impo=sprintf("%.6f", $impo);
    $impo = str_pad($impo,17,"0",STR_PAD_LEFT);
    $uuid = strtoupper($data['uuid']);
    $factura = "?re=$rfc_emisor&rr=$rfc_receptor&tt=$impo&id=$uuid";
    echo "<h3>$factura</h3>";
    $prm = array('expresionImpresa'=>$factura);
    $buscar=$soapclient->Consulta($prm);
    echo "<h3>El portal del SAT reporta</h3>";
    echo "El codigo: ".$buscar->ConsultaResult->CodigoEstatus."<br>";
    echo "El estado: ".$buscar->ConsultaResult->Estado."<br>";

}
// }}}
// {{{ Lee del FTP del SAT la llave Publica (Certificado) del CSD
//
//  Table "public.cfdcert"
//    Column    |            Type             |   Modifiers   
// -------------+-----------------------------+---------------
//  no_serie    | character(20)               | not null
//  certificado | text                        | 
//  descarga    | timestamp without time zone | default now()
//  usado       | timestamp without time zone | default now()
//        Indexes:
//  "cfdcert_pkey" PRIMARY KEY, btree (no_serie)
//
function get_sat_cert($no_cert) {
    global $conn;
    $llave = $conn->qstr($no_cert); // Evita SQL Injection ...
    $pem = $conn->getone("select certificado from cfdcert where no_serie = $llave");
    if (strlen($pem)>30) {
        // Si ya esta guardado regresalo y actualiza fecha
        $conn->execute("update cfdcert set usado = current_timestamp where no_serie = $llave");
        echo "Certificado de cache<br>";
    } else {
        // No esta en la tabla descarga del SAT
        $pem=""; $der="";
        $p1=substr($no_cert,0,6);
        $p2=substr($no_cert,6,6);
        $p3=substr($no_cert,12,2);
        $p4=substr($no_cert,14,2);
        $p5=substr($no_cert,16,2);
        $path1 = "ftp://ftp2.sat.gob.mx/certificados/FEA/$p1/$p2/$p3/$p4/$p5/$no_cert.cer";
        // Nuevo servidor mas rapido (menos conocido) (Gracias Rene)
        $path2 = "https://rdc.sat.gob.mx/rccf/$p1/$p2/$p3/$p4/$p5/$no_cert.cer";
        // Realiza 5 intentos para descargar el certificado
        // Gracias Rene Calderon
        //
        // Se ignora el ceftificado del servidor rdc del sat
        $arrContextOptions=array(
                "ssl"=>array(
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                    ),
                );
        $context = stream_context_create($arrContextOptions);
        $done = false;
        $x = 0;
        while ( ! $done ){
            //echo "intento: $x<br>";
            // Alterna servidor en cada intento ....
            $path = (($x%2)==0) ? $path1 : $path2;
            $der = file_get_contents("$path",false,$context);
            echo "Lee del SAT $path<br>";
            if ($der){
                $done = true;  
            } else {
                usleep (100000);
            }
            if ( $x == 5 ) $done = true;
            $x++;
        }
        $pem = base64_encode($der);
        if (strlen($pem)>30) {
            // Almacena en tabla para la siguiente
            $conn->execute("insert into cfdcert (no_serie,certificado)
                values ($llave,'$pem')");
        }
    }
    return $pem;
}
// }}}
// {{{ Convierte EL numero de serie del SAT a formato humano
function convierte($dec) {
    $hex=bcdechex($dec);
    $ser="";
    for ($i=1; $i<strlen($hex); $i=$i+2) {
        $ser.=substr($hex,$i,1);
    }
    return $ser;
}
// }}} Convierte
// {{{ bcdechex   :  como dechex pero para numeros de precision ilimitada
function bcdechex($dec) {
    $last = bcmod($dec, 16);
    $remain = bcdiv(bcsub($dec, $last), 16);
    if($remain == 0) {
        return dechex($last);
    } else {
        return bcdechex($remain).dechex($last);
    }
}
// }}} bcdechex
// {{{ display_xml_errors
function display_xml_errors() {
    global $texto;
    $lineas = explode("\n", $texto);
    $errors = libxml_get_errors();

    echo "<pre>";
    foreach ($errors as $error) {
        echo display_xml_error($error, $lineas);
    }
    echo "</pre>";

    libxml_clear_errors();
}
/// }}}}
// {{{ display_xml_error
function display_xml_error($error, $lineas) {
    $return  = htmlspecialchars($lineas[$error->line - 1]) . "\n";
    $return .= str_repeat('-', $error->column) . "^\n";

    switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= "Warning $error->code: ";
            break;
         case LIBXML_ERR_ERROR:
            $return .= "Error $error->code: ";
            break;
        case LIBXML_ERR_FATAL:
            $return .= "Fatal Error $error->code: ";
            break;
    }

    $return .= trim($error->message) .
               "\n  Linea: $error->line" .
               "\n  Columna: $error->column";
    echo "$return\n\n--------------------------------------------\n\n";
}
/// }}}}
?>
</div>
</BODY>
</HTML>
