<?php
$m_rfc = $_POST['m_rfc'];
$datos = $_POST['datos'];
$m_rfc=trim(strtoupper($m_rfc));
?>
<html>
<head>
<title>Consulta RFC en lista Oficial l_rfc</title>
<link rel="STYLESHEET" href="../fortiz.css" media="screen" type="text/css">
<meta http-equiv="Pragma" content="no-cache/cache">
<meta http-equiv="Cache-Control" content="no-cache">
<SCRIPT language="JavaScript">
function m_consulta(){
   if(document.forma.m_rfc.value == "") {
      alert("Teclee rfc");
      document.forma.m_rfc.focus();
      return;
   }
    if(document.forma.m_rfc.length < 8) {
      alert("debe teclear minimo 8 caracteres para la busqueda");
      document.forma.m_rfc.focus();
      return;
   }
   document.forma.datos.value="SI";
   document.forma.submit();
}
</SCRIPT>
</head>
<body>
<div align=center>
<H2>Consulta RFC en lista oficial SAT l_rfc </H2>
<form name=forma method=post >
<hr>
<table border=0 align=center >
    <tr><th align="right">Minimo 8 letras iniclaes del RFC a buscar
        <td><input type="text" 
                   style='font-weight:bold; text-align:left; text-transform:uppercase'
                   name="m_rfc" 
                   value="<?=$m_rfc?>" 
                   size="10" maxlength="10">

         <td align=center>
            <INPUT TYPE="button" VALUE="Consulta" onclick="m_consulta();">
            <INPUT TYPE="hidden" name="datos" value="NO">
</table>
</form>
<hr>
<?php
if($datos=="") die();
require_once "myconn/myconn.inc.php";
$conn=myconn();
?>
    <table border=1 align=center >
        <tr><th>RFC 
            <th>Subcontratacion
            <th>SNCF
<?php
$m_rfc = $conn->qstr($m_rfc."%");
$qry="SELECT DISTINCT rfc_rfc, rfc_sub, rfc_sncf
            FROM pac_l_rfc 
            WHERE rfc_rfc LIKE $m_rfc 
            ORDER BY 1  
            LIMIT 50";
$rs = $conn->execute($qry);
foreach ($rs as $row) {
    echo '<tr>';
    echo '<td align=center>';
    echo $row["rfc_rfc"];
    echo '<td align=center>';
    echo $row["rfc_sub"];
    echo '<td align=center>';
    echo $row["rfc_sncf"];
} 
?>
</body>
</html>
