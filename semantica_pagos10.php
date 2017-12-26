<?php
/****************************************************************************
 * semantica_pagos Valida la semantica del complemento de pagos version 1.0 *
 *                                                                          *
 ****************************************************************************/
error_reporting(E_ALL);
class Pagos10 {
    var $xml_cfd;
    var $con;
    var $codigo;
    var $status;
    var $cuenta = 1;
    public function valida($xml_cfd,$conn) {
        // {{{ valida : semantica_pagos Version 1.1
        $ok = true;
        $this->xml_cfd = $xml_cfd;
        $this->conn = $conn;
        $this->status = "CPR000 Inicia Validacion de semantica pagos 1.0";
        $this->codigo = "0 ".$this->status;
        $Comprobante = $this->xml_cfd->getElementsByTagName('Comprobante')->item(0);
        $Emisor = $this->xml_cfd->getElementsByTagName('Emisor')->item(0);
        $Receptor = $this->xml_cfd->getElementsByTagName('Receptor')->item(0);

        $pagos = $Comprobante->getElementsByTagName('Pagos')->item(0);
        $Complemento = $Comprobante->getElementsByTagName('Complemento')->item(0);
        $pago_version = $pagos->getAttribute("Version");
        $Pagos = $pagos->getElementsByTagName('Pago');
        $TipoDeComprobante = $Comprobante->getAttribute("TipoDeComprobante");
        $nb_Pagos = $Pagos->length;
        // }}}
	//CRP101
        if ($TipoDeComprobante!="P") {
            $this->status = "CRP101 El valor del campo TipoDeComprobante debe ser 'P'";
            $this->codigo = "101 ".$this->status;
            return false;
         }
         $SubTotal = $Comprobante->getAttribute("SubTotal");
         if($SubTotal!="0") {
            $this->status = "CRP102 El valor del campo SubTotal debe ser cero '0'.";
           $this->codigo = "102 ".$this->status;
            return false;
         }
         $Moneda = $Comprobante->getAttribute("Moneda");
         if ($Moneda!="XXX") {
             $this->status = "CRP103 El valor del campo Moneda debe ser 'XXX'.";
            $this->codigo = "103 ".$this->status;
             return false;
         }
         $FormaPago = $Comprobante->getAttribute("FormaPago");
         if ($FormaPago!=null) {
             $this->status = "CRP104 El campo FormaPago no se debe registrar en el CFDI.";
            $this->codigo = "104 ".$this->status;
             return false;
         }
         $MetodoPago = $Comprobante->getAttribute("MetodoPago");
         if ($MetodoPago!=null) {
             $this->status = "CRP105 El campo MetodoPago no se debe registrar en el CFDI.";
             $this->codigo = "105 ".$this->status;
             return false;
         }
         $CondicionesDePago = $Comprobante->getAttribute("CondicionesDePago");
         if ($CondicionesDePago!=null) {
             $this->status = "CRP106 El campo CondicionesDePago no se debe registrar en el CFDI.";
             $this->codigo = "106 ".$this->status;
             return false;
         }
         $Descuento = $Comprobante->getAttribute("Descuento");
         if ($Descuento!=null) {
             $this->status = "CRP107 El campo Descuento no se debe registrar en el CFDI.";
             $this->codigo = "107 ".$this->status;
             return false;
         }
         $TipoCambio = $Comprobante->getAttribute("TipoCambio");
         if ($TipoCambio!=null) {
             $this->status = "CRP108 El campo TipoCambio no se debe registrar en el CFDI.";
             $this->codigo = "108 ".$this->status;
             return false;
         }
         $Total = $Comprobante->getAttribute("Total");
         if ($Total!="0") {
             $this->status = "CRP109 El valor del campo Total debe ser cero '0'.";
             $this->codigo = "109 ".$this->status;
             return false;
         }
         $UsoCFDI = $Receptor->getAttribute("UsoCFDI");
         if ($UsoCFDI!="P01") {
             $this->status = "CRP110 El valor del campo UsoCFDI debe ser 'P01'.";
             $this->codigo = "110 ".$this->status;
             return false;
         }
         $Conceptos = $Comprobante->getElementsByTagName('Concepto');
         if ($Conceptos->length != 1) {
             $this->status = "CRP111 Solo debe existir un Concepto en el CFDI. ";
             $this->codigo = "111 ".$this->status;
             return false;
         }
         $Concepto = $Conceptos->item(0);
         $tiene = false;
         foreach ($Concepto->childNodes as $node) {
             if ($node->nodeType == XML_ELEMENT_NODE) {
                 $tiene=true;
             }
         }
         if ($tiene) {
             $this->status = "CRP112 No se deben registrar apartados dentro de Conceptos";
             $this->codigo = "112 ".$this->status;
             return false;
         }
         $ClaveProdServ = $Concepto->getAttribute("ClaveProdServ");
         if ($ClaveProdServ!="84111506") {
             $this->status = "CRP113 El valor del campo ClaveProdServ debe ser '84111506'.";
             $this->codigo = "113 ".$this->status;
             return false;
         }
         $NoIdentificacion = $Concepto->getAttribute("NoIdentificacion");
         if ($NoIdentificacion!=null) {
             $this->status = "CRP114 El campo NoIdentificacion no se debe registrar en el CFDI.";
             $this->codigo = "114 ".$this->status;
             return false;
         }
         $Cantidad = $Concepto->getAttribute("Cantidad");
         if ($Cantidad!="1") {
             $this->status = "CRP115 El valor del campo Cantidad debe ser '1'.";
             $this->codigo = "115 ".$this->status;
             return false;
         }
         $ClaveUnidad = $Concepto->getAttribute("ClaveUnidad");
         if ($ClaveUnidad!="ACT") {
             $this->status = "CRP116  El valor del campo ClaveUnidad debe ser 'ACT'.";
             $this->codigo = "116 ".$this->status;
             return false;
         }
         $Unidad = $Concepto->getAttribute("Unidad");
         if ($Unidad!=null) {
             $this->status = "CRP117 El campo Unidad no se debe registrar en el CFDI.";
             $this->codigo = "117 ".$this->status;
             return false;
         }
         $Descripcion = $Concepto->getAttribute("Descripcion");
         if ($Descripcion!="Pago") {
             $this->status = "CRP118 El valor del campo Descripcion debe ser 'Pago'.";
             $this->codigo = "118 ".$this->status;
             return false;
         }
         $ValorUnitario = $Concepto->getAttribute("ValorUnitario");
         if ($ValorUnitario!="0") {
             $this->status = "CRP119 El valor del campo ValorUnitario debe ser cero '0'.";
             $this->codigo = "119 ".$this->status;
             return false;
         }
         $Importe = $Concepto->getAttribute("Importe");
         if ($Importe!="0") {
             $this->status = "CRP120 El valor del campo Importe debe ser cero '0'.";
             $this->codigo = "120 ".$this->status;
             return false;
         }
         $Descuento = $Concepto->getAttribute("Descuento");
         if ($Descuento!=null) {
             $this->status = "CRP121 El campo Descuento no se debe registrar en el CFDI.";
             $this->codigo = "121 ".$this->status;
             return false;
         }
         $Impuestos = $Comprobante->getElementsByTagName('Impuestos');
         foreach ($Impuestos as $Impuesto) {
             if ($Impuesto->parentNode->nodeName=="cfdi:Comprobante") {
                 $this->status = "CRP122 No se debe registrar el apartado de Impuestos en el CFDI.";
                 $this->codigo = "122 ".$this->status;
                 return false;
             }
         }
         foreach ($Pagos as $node) {
            $FormaDePagoP = $node->getAttribute("FormaDePagoP");
            if ($FormaDePagoP == "99") {
               $this->status = "CRP201  El valor registrado debe ser diferente de 99.";
               $this->codigo = "201 ".$this->status;
               return false;
            }
           $MonedaP = $node->getAttribute("MonedaP");
           if ($MonedaP=="XXX") {
               $this->status = "CRP202 El campo MonedaP debe ser distinto de 'XXX'";
               $this->codigo = "202 ".$this->status;
               return false;
           }
           $TipoCambioP= $node->getAttribute("TipoCambioP");
           if ($MonedaP!="MXN") {
              if ($TipoCambioP==null || $TipoCambioP=="") {
                 $this->status = "CRP203 El campo TipoCambioP se debe registrar.";
                 $this->codigo = "203 ".$this->status;
                 return false;
              } 
           }
           if ($MonedaP=="MXN") {
              if ($TipoCambioP!=null) {
                 $this->status = "CRP204 El campo TipoCambioP no se debe registrar. ";
                 $this->codigo = "204 ".$this->status;
                 return false;
              } 
           }
           $c_Moneda = $this->Obten_Catalogo("c_Moneda", $MonedaP);
           $porc_moneda = (int)$c_Moneda["porcentaje"];
           $Confirmacion = $Comprobante->getAttribute("Confirmacion");
           /* TODO: Validar limites del tipo de cambio contra
           * lo publicado */
           $oficial = 20; // TODO: leer del lugar oficial
           $inf = $oficial * (1 - $porc_moneda/100);
           $sup = $oficial * (1 + $porc_moneda/100);
	   $req_conf = false;
           // echo "porc_moneda=$porc_moneda oficial=$oficial inf=$inf sup=$sup TipoCambioP=$TipoCambioP";
	   if ($MonedaP!="MXN"){
               if ($TipoCambioP < $inf || $TipoCambioP > $sup)  {
                   $req_conf = true;
                   if ($Confirmacion == null) {
                       $this->status = "CRP205 Cuando el valor del campo TipoCambioP se encuentre fuera de los limites establecidos, debe existir el campo Confirmacion";
                       $this->codigo = "205 ".$this->status;
                       return false;
                   }
               }
	   }
           $Monto = $node->getAttribute("Monto");
	   if ($Monto==0){
               $this->status = "CRP207 El valor del campo Monto no es mayor que cero '0'.";
               $this->codigo = "207 ".$this->status;
               return false;
           }
           $dec_moneda = (int)$c_Moneda["decimales"];
           $DoctosRelacionados = $node->getElementsByTagName('DoctoRelacionado');
	   $w_timppagado = 0;
	   foreach($DoctosRelacionados as $DoctoRelacionado){
              $ImpPagado = $DoctoRelacionado->getAttribute("ImpPagado");
              $MonedaDR = $DoctoRelacionado->getAttribute("MonedaDR");
	      $w_imppagado = $ImpPagado;
  	      if($MonedaDR != "MXN"){
                 $TipoCambioDR = $DoctoRelacionado->getAttribute("TipoCambioDR");
		 if($TipoCambioDR!=null){
                    $w_imppagado = $ImpPagado * $TipoCambioDR;
                    $w_imppagado = round($w_imppagado,$dec_moneda);
		 }
              }
    	      $w_timppagado += $w_imppagado;  
	   }
           if ($w_timppagado>$Monto) { 
               $this->status = "CRP206 La suma de los valores registrados en el campo ImpPagado de los apartados DoctoRelacionado no es menor o igual que el valor del campo Monto.";
               $this->codigo = "206 ".$this->status;
               return false;
           }
           $dec_monto = $this->cantidad_decimales($Monto);
           if ($dec_monto > $dec_moneda )  {
                 $this->status = "CRP208 El valor del campo Monto debe tener hasta la cantidad de decimales que soporte la moneda registrada en el campo MonedaP.";
                 $this->codigo = "208 ".$this->status;
                 return false;
           }
	   $w_montocambio = $Monto;
	   if ($MonedaP != "MXN") {//Considerando Tipo de Cambio Convertimos a Pesos
	       $w_montocambio = $Monto * $TipoCambioP; //Obtener TipoCambioP en MXN
	       $w_montocambio = round($w_montocambio,$dec_moneda);
	   } 
	   //$TopeMonto = $c_TipoDeComprobante["unidad"];
	   $TopeMonto = 20000000;//TODO ¿De cual campo del catalogo lo obtendremos?
           if ($w_montocambio>$TopeMonto){
               $req_conf = true;
               if ($Confirmacion == null) {
                   $this->status = "CRP209 Cuando el valor del campo Monto se encuentre fuera de los limites establecidos, debe existir el campo Confirmacion";
                   $this->codigo = "209 ".$this->status;
                   return false;
               }
           } 
           $RfcEmisorCtaOrd = $node->getAttribute("RfcEmisorCtaOrd");
	   if ($RfcEmisorCtaOrd !=null) {
	       if ($RfcEmisorCtaOrd != "XEXX010101000") {
                   $row= $this->lee_l_rfc($RfcEmisorCtaOrd);
                   if (sizeof($row)==0){
                       $this->status = "CRP210 El RFC del campo RfcEmisorCtaOrd no se encuentra en la lista de RFC.";
                       $this->codigo = "210 ".$this->status;
                       return false;
                   }
	       }
           }
	   $NomBancoOrdExt = $node->getAttribute("NomBancoOrdExt");
           if ($RfcEmisorCtaOrd == "XEXX010101000" ){
              if ($NomBancoOrdExt =="" || $NomBancoOrdExt == null) {
                       $this->status = "CRP211 El campo NomBancoOrdExt se debe registrar.";
                       $this->codigo = "211 ".$this->status;
                       return false;
	      }
	   } 
	   $CtaOrdenante = $node->getAttribute("CtaOrdenante");
	   $RfcEmisorCtaBen = $node->getAttribute("RfcEmisorCtaBen");
	   $CtaBeneficiario = $node->getAttribute("CtaBeneficiario");
           if ($FormaDePagoP != "02" && $FormaDePagoP != "03" && $FormaDePagoP != "04" && 
               $FormaDePagoP != "05" && $FormaDePagoP != "06" && $FormaDePagoP != "28" && 
               $FormaDePagoP != "29") {
              if ($CtaOrdenante != null) {
                  $this->status = "CRP212 El campo CtaOrdenante no se debe registrar.";
                  $this->codigo = "212 ".$this->status;
                  return false;
	      }
              if ($RfcEmisorCtaOrd != null) {
                  $this->status = "CRP238 El campo RfcEmisorCtaOrd no se debe registrar.";
                  $this->codigo = "238 ".$this->status;
                  return false;
	      }
	   } 
           $regFdeP = $this->Obten_Catalogo("c_FormaPago",$FormaDePagoP);
           $regex_ord = trim($regFdeP['regex_cp']);
           $regex_ben = trim($regFdeP['regex_taxid']);
           if ($CtaOrdenante !=null) {
               $aux = "/^$regex_ord$/A";
               $ok = preg_match($aux,$CtaOrdenante);
               if (!$ok) {
                   $this->status = "CRP213 El campo CtaOrdenante no cumple con el patron requerido.";
                   $this->codigo = "213 ".$this->status;
                   return false;
               }
	   }
           if ($FormaDePagoP != "02" && $FormaDePagoP != "03" && $FormaDePagoP != "04" && 
               $FormaDePagoP != "05" && $FormaDePagoP != "28" && 
               $FormaDePagoP != "29") {
              if ($RfcEmisorCtaBen != null) {
                  $this->status = "CRP214 El campo RfcEmisorCtaBen no se debe registrar.";
                  $this->codigo = "214 ".$this->status;
                  return false;
	      }
              if ($CtaBeneficiario != null) {
                  $this->status = "CRP215 El campo CtaBeneficiario no se debe registrar.";
                  $this->codigo = "215 ".$this->status;
                  return false;
	      }
	   } 
           $TipoCadPago = $node->getAttribute("TipoCadPago");
	   if ($FormaDePagoP != "03"){
               if ($TipoCadPago != null) {
                   $this->status = "CRP216 El campo TipoCadPago no se debe registrar. ";
                   $this->codigo = "216 ".$this->status;
                   return false;
	       }
	   }
	   foreach($DoctosRelacionados as $DoctoRelacionado){
              $MonedaDR = $DoctoRelacionado->getAttribute("MonedaDR");
	      if ($MonedaDR=="XXX") {
                 $this->status = "CRP217 El valor del campo MonedaDR debe ser distinto de 'XXX'";
                 $this->codigo = "217 ".$this->status;
                 return false;
              }
              $TipoCambioDR = $DoctoRelacionado->getAttribute("TipoCambioDR");
	      if ($MonedaDR != $MonedaP){
	          if ($TipoCambioDR == null || $TipoCambioDR == ""){
                      $this->status = "CRP218 El campo TipoCambioDR se debe registrar. ";
                      $this->codigo = "218 ".$this->status;
                      return false;
		  } 	 
	      }
	      if ($MonedaDR == $MonedaP){
	          if ($TipoCambioDR != null){
                      $this->status = "CRP219 El campo TipoCambioDR no se debe registrar.";
                      $this->codigo = "219 ".$this->status;
                      return false;
		  } 	 
	      }
	      if ($MonedaDR == "MXN" && $MonedaP != "MXN"){
	          if ($TipoCambioDR != "1"){
                      $this->status = "CRP220 El campo TipoCambioDR debe ser '1'.";
                      $this->codigo = "220 ".$this->status;
                      return false;
		  } 	 
	      }
              $ImpSaldoAnt = $DoctoRelacionado->getAttribute("ImpSaldoAnt");
	      if($ImpSaldoAnt != null){//TODO Si es opcional el campo primero valido que exista, despues > 0
		 if($ImpSaldoAnt<=0){
                    $this->status = "CRP221 El campo ImpSaldoAnt debe mayor a cero.";
                    $this->codigo = "221 ".$this->status;
                    return false;
		 }
	      }
	      $c_MonedaDR = $this->Obten_Catalogo("c_Moneda", $MonedaDR); 
              $dec_monedaDR = (int)$c_MonedaDR["decimales"];
	      if($ImpSaldoAnt!=null){
                 $dec_impsaldoant = $this->cantidad_decimales($ImpSaldoAnt);
                 if ($dec_impsaldoant > $dec_monedaDR )  {
                       $this->status = "CRP222 El valor del campo ImpSaldoAnt debe tener hasta la cantidad de decimales que soporte la moneda registrada en el campo MonedaDR.";
                       $this->codigo = "222 ".$this->status;
                       return false;
                 }
	      }
              $ImpPagado = $DoctoRelacionado->getAttribute("ImpPagado");
	      if($ImpPagado != null){
		 if($ImpPagado<=0){
                    $this->status = "CRP223 El campo ImpPagado debe mayor a cero.";
                    $this->codigo = "223 ".$this->status;
                    return false;
		 }
	      }
	      if($ImpPagado!=null){
                 $dec_imppagado = $this->cantidad_decimales($ImpPagado);
                 if ($dec_imppagado > $dec_monedaDR )  {
                       $this->status = "CRP224 El valor del campo ImpPagado debe tener hasta la cantidad de decimales que soporte la moneda registrada en el campo MonedaDR.";
                       $this->codigo = "224 ".$this->status;
                       return false;
                 }
	      }
              $ImpSaldoInsoluto = $DoctoRelacionado->getAttribute("ImpSaldoInsoluto");
	      if($ImpSaldoInsoluto!=null){
                 $dec_impsaldoinsoluto = $this->cantidad_decimales($ImpSaldoInsoluto);
                 if ($dec_impsaldoinsoluto > $dec_monedaDR )  {
                       $this->status = "CRP225 El valor del campo ImpSaldoInsoluto debe tener hasta la cantidad de decimales que soporte la moneda registrada en el campo MonedaDR.";
                       $this->codigo = "225 ".$this->status;
                       return false;
                 }
	      }
              $MetodoDePagoDR= $DoctoRelacionado->getAttribute("MetodoDePagoDR");
              $ImpSaldoAnt = $DoctoRelacionado->getAttribute("ImpSaldoAnt");
      	      if ($MetodoDePagoDR =="PPD"){
		  if($ImpSaldoAnt == null || $ImpSaldoAnt == ""){
                     $this->status = "CRP234 El campo ImpSaldoAnt se debe registrar.";
                     $this->codigo = "234 ".$this->status;
                     return false;
		  }	  
	      }
	      if($ImpPagado == null || $ImpPagado == ""){
		 if($DoctosRelacionados->length > 1 || 
		   ($DoctosRelacionados->length == 1 && $TipoCambioDR != "")){
                    $this->status = "CRP235 El campo ImpPagado se debe registrar. ";
                    $this->codigo = "235 ".$this->status;
                    return false;
		 }
	      }
	      if($ImpSaldoInsoluto != null){
		 // echo " saldoinsoluto = ".$ImpSaldoInsoluto." saldoant = ".$ImpSaldoAnt. " ImpPagado = ".$ImpPagado. " ImpSaldoInsoluto= ".$ImpSaldoInsoluto;
		 // echo "<br> uno = ".($ImpSaldoAnt - $ImpPagado). " dos === ".$ImpSaldoInsoluto;
		 if($ImpSaldoInsoluto<0 || (($ImpSaldoAnt - $ImpPagado != $ImpSaldoInsoluto))){
                    $this->status = "CRP226 El campo ImpSaldoInsoluto debe ser mayor o igual a cero y calcularse con la suma de los campos ImSaldoAnt menos el ImpPagado o el Monto";
                    $this->codigo = "226 ".$this->status;
                    return false;
		 }
	      }
              $NumParcialidad= $DoctoRelacionado->getAttribute("NumParcialidad");
      	      if ($MetodoDePagoDR=="PPD"){
		  if($NumParcialidad == null || $NumParcialidad == ""){
                     $this->status = "CRP233 El campo NumParcialidad se debe registrar.";
                     $this->codigo = "233 ".$this->status;
                     return false;
		  }	  
	      }
              $ImpSaldoInsoluto= $DoctoRelacionado->getAttribute("ImpSaldoInsoluto");
      	      if ($MetodoDePagoDR =="PPD"){
		  if($ImpSaldoInsoluto == null || $ImpSaldoInsoluto == ""){
                     $this->status = "CRP236 El campo ImpSaldoInsoluto se debe registrar.";
                     $this->codigo = "236 ".$this->status;
                     return false;
		  }	  
	      }

	   }//End ForEach DoctosRelacionados 
           $CertPago = $node->getAttribute("CertPago");
           if ($TipoCadPago != null) {
               if ($CertPago == null || $CertPago == "") {
                   $this->status = "CRP227 El campo CertPago se debe registrar.";
                   $this->codigo = "227 ".$this->status;
                   return false;
	       }
	   }
           if ($TipoCadPago == null || $TipoCadPago == "") {
               if ($CertPago != "") {
                   $this->status = "CRP228 El campo CertPago no se debe registrar.";
                   $this->codigo = "228 ".$this->status;
                   return false;
	       }
	   }
           $CadPago = $node->getAttribute("CadPago");
           if ($TipoCadPago != null) {
               if ($CadPago == null || $CadPago == "") {
                   $this->status = "CRP229 El campo CadPago se debe registrar.";
                   $this->codigo = "229 ".$this->status;
                   return false;
	       }
	   }
           if ($TipoCadPago == null || $TipoCadPago == "") {
               if ($CadPago != "") {
                   $this->status = "CRP230 El campo CadPago no se debe registrar.";
                   $this->codigo = "230 ".$this->status;
                   return false;
	       }
	   }
           $SelloPago = $node->getAttribute("SelloPago");
           if ($TipoCadPago != null) {
               if ($SelloPago == "" || $SelloPago == null) {
                   $this->status = "CRP231 El campo SelloPago se debe registrar.";
                   $this->codigo = "231 ".$this->status;
                   return false;
	       }
	   }
           if ($TipoCadPago == "" || $TipoCadPago == null) {
               if ($SelloPago != "") {
                   $this->status = "CRP232 El campo SelloPago no se debe registrar.";
                   $this->codigo = "232 ".$this->status;
                   return false;
	       }
	   }
           if ($CtaBeneficiario != null) {
               $aux = "/^$regex_ben$/A";
               $ok = preg_match($aux,$CtaBeneficiario);
               if (!$ok) {
                   $this->status = "CRP239 El campo CtaBeneficiario no cumple con el patron requerido.";
                   $this->codigo = "239 ".$this->status;
                   return false;
               }
	   }

	   
	 }//End ForEach Pagos 
         $Impuestos = $pagos->getElementsByTagName('Impuestos');
         if ($Impuestos->length > 0) {
             $this->status = "CRP237 No debe existir el apartado de Impuestos.";
             $this->codigo = "237 ".$this->status;
             return false;
         }
        $this->status = "CPR000 Validacion de semantica pagos correcta";
        $this->codigo = "0 ".$this->status;
        return $ok;
      }

    // {{{ Checa_Catalogo
    private function Checa_Catalogo($catalogo,$llave,$prm1="",$prm2="",$prm3="") {
        $ok = true;
        $rs = $this->Obten_catalogo($catalogo,$llave,$prm1,$prm2,$prm3);
        if ($rs===FALSE) return false;
        if (sizeof($rs)==0) return false;
        return $ok;
    }
    // }}}
    // {{{ Obten_Catalogo
    private function Obten_Catalogo($catalogo,$llave,$prm1="",$prm2="",$prm3="") {
        $rs = false;
        $cata = $this->conn->qstr($catalogo);
        $l = $this->conn->qstr($llave);
        $qry = "select * from pac_Catalogos where cata_cata = $cata and cata_llave = $l";
        if ($prm1!="") {
            $p = $this->conn->qstr($prm1);
            $qry .= " and cata_prm1 = $p";
        }
        if ($prm2!="") {
            $p = $this->conn->qstr($prm2);
            $qry .= " and cata_prm2 = $p";
        }
        if ($prm3!="") {
            $p = $this->conn->qstr($prm3);
            $qry .= " and cataprm3 = $p";
        }
        $rs = $this->conn->getrow($qry);
        return $rs;
    }
    // }}}
    // {{{ Cuenta_Catalogo
    private function Cuenta_Catalogo($catalogo,$prm1) {
        $cant = 0;
        $cata = $this->conn->qstr($catalogo);
        $p = $this->conn->qstr($prm1);
        $qry = "select count(*) from pac_Catalogos where cata_cata = $cata and cata_prm1 = $p";
        $cant = $this->conn->getone($qry);
        return $cant;
    }
    // }}}
    // {{{ lee_l_rfc
    private function lee_l_rfc($rfc) {
        if ($this->cuenta === FALSE) $this->cuenta_l_rfc();
        if ($this->cuenta > 0) {
            $l = $this->conn->qstr($rfc);
            $qry = "select * from pac_l_rfc where rfc_rfc = $l";
            $row= $this->conn->GetRow($qry);

        } else { // NO hay registros de RFC
            // No valida hasta que el SAT publica lista
            $row = array("rfc_rfc"=>$rfc,
                         "rfc_sncf"=>"  ",
                         "rfc_sub"=>"  ");
        }
        return $row;
    }
    // }}}
    // {{{ cantidad_decimales
    private function cantidad_decimales($impo) {
        @list($ent,$dec) = @explode(".",$impo);
        return strlen($dec);
    }
    // }}}
    // {{{ cuenta_l_rfc
    private function cuenta_l_rfc() {
        // $cant= $this->conn->GetOne("select count(*) from pac_l_rfc");
        // $this->cuenta = $cant;
        $this->cuenta = 1; // Siempre hay registros
    }
    // }}}
    // {{{ Checa_Confirmacion($Confirmacion)
    private function Checa_Confirmacion($Confirmacion) {
        $rs = false;
        $num = $this->conn->qstr($Confirmacion);
        $qry = "select * from pac_confirmacion where llave = $num";
        $rs = $this->conn->getrow($qry);
        if ($rs===FALSE) return 0;
        if (sizeof($rs)==0) return 0;
        $uuid = trim($rs['uuid']);
        if (strlen($uuid)>10) return 2;
        return 1;
    }
    // }}}

}
