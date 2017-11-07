<?php
/*****************************************************************************
 * semanitica_nomi12.php Validacion de complemento de nomina version 1.2
 *
 * 1/nov/2016 Version inicial
 *
 * 23/dic/2016 Validacion de igualdad con abs() > 0.001 por error
 *             de precision. gracias acanas@validacfd
 *
 * 24/mar/2017 Se permite RFC generico extranjero en nodo subcontratacion
 *             RFCLabora                                     
 *
 *****************************************************************************/
error_reporting(E_ALL);
class Nomi12 {
    var $xml_cfd;
    var $con;
    var $codigo="";
    var $status="";
    var $mensaje="";
    var $cuenta=true; // Cantidad de registros en pac_l_rfc
    // {{{ valida : semantica_nomi12
    public function valida($xml_cfd,$conn) {
        $ok = true;
        $error = false;
        $this->xml_cfd = $xml_cfd;
        $this->conn = $conn;
        $this->status = "";
        $this->codigo = "";
        /// Verifica sea version 1.2
        $Comprobante = $this->xml_cfd->getElementsByTagName('Comprobante')->item(0);
        $version = $Comprobante->getAttribute("version");
        if ($version==null) $version = $Comprobante->getAttribute("Version");
        // $nomi = $Comprobante->getElementsByTagName('Nomina')->item(0);
        $nomi = $this->xml_cfd->getElementsByTagName('Nomina')->item(0);
        $nombre = $nomi->parentNode->nodeName;
        if ($nombre != "cfdi:Complemento") {
            $this->status = ";NOM150 El nodo Nomina no se puede utilizar dentro del elemento ComplementoConcepto.";
            $this->codigo .= "; NOM150";
            $error=true;
        }
        $Version = $nomi->getAttribute("Version");
        if ($Version != "1.2") {
            $this->status = "NOM000 Solo valida Version 1.2";
            $this->codigo = 0;
            return true; // Correcta
        }
        // }}}
        // {{{ Atributos generales de Nomina, lee otros nodos
        $NumDiasPagados = $nomi->getAttribute("NumDiasPagados");
        $TotalPercepciones = $nomi->getAttribute("TotalPercepciones");
        $TotalOtrosPagos = $nomi->getAttribute("TotalOtrosPagos");
        $TotalDeducciones = $nomi->getAttribute("TotalDeducciones");
        if ($TotalPercepciones=="" && $TotalOtrosPagos=="") {
            $this->status .= ";NOM151 El nodo Nomina no tiene TotalPercepciones y/o TotalOtrosPagos.";
            $this->codigo .= "; NOM151";
            $error=true;
        }
        $n_Emisores = $nomi->getElementsByTagName('Emisor');
        if ($n_Emisores->length == 0) {
            $n_Emisor = null;
            $n_E_Curp=null;
            $n_E_RegistroPatronal=null;
            $n_E_RfcPatronOrigen = null;
        } else {
            $n_Emisor = $n_Emisores->item(0);
            $n_E_Curp = $n_Emisor->getAttribute("Curp");
            $n_E_RegistroPatronal = $n_Emisor->getAttribute("RegistroPatronal");
            $n_E_RfcPatronOrigen = $n_Emisor->getAttribute("RfcPatronOrigen");
        }
        $n_Receptor = $nomi->getElementsByTagName('Receptor')->item(0);
        $subs = $n_Receptor->getElementsByTagName('SubContratacion');
        $n_subs = $subs->length;
        $TipoContrato = $n_Receptor->getAttribute("TipoContrato");
        $TipoJornada = $n_Receptor->getAttribute("TipoJornada");
        $NumSeguridadSocial = $n_Receptor->getAttribute("NumSeguridadSocial");
        $FechaInicioRelLaboral = $n_Receptor->getAttribute("FechaInicioRelLaboral");
        $Antiguedad = $n_Receptor->getAttribute(utf8_encode("Antigüedad"));
        $TipoRegimen = $n_Receptor->getAttribute("TipoRegimen");
        $RiesgoPuesto = $n_Receptor->getAttribute("RiesgoPuesto");
        $PeriodicidadPago = $n_Receptor->getAttribute("PeriodicidadPago");
        $Banco = $n_Receptor->getAttribute("Banco");
        $CuentaBancaria = $n_Receptor->getAttribute("CuentaBancaria");
        $SalarioBaseCotApor = $n_Receptor->getAttribute("SalarioBaseCotApor");
        $SalarioDiarioIntegrado = $n_Receptor->getAttribute("SalarioDiarioIntegrado");
        $ClaveEntFed = $n_Receptor->getAttribute("ClaveEntFed");
        // }}}
        if ($version == "3.2") {
            // {{{ Validaciones para  CFDI 3.2
            $fecha = $Comprobante->getAttribute("fecha");
            $regex = "(20[1-9][0-9])-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])T(([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9])";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$fecha);
            if (!$ok) {
                $this->status = "NOM101 El atributo fecha no cumple con el patrón requerido.";
                $this->codigo = "101 ".$this->status;
                return false;
            }
            $metodoDePago = $Comprobante->getAttribute("metodoDePago");
            if ($metodoDePago != "NA") {
                $this->status = 'NOM102 El atributo metodoDePago debe tener el valor "NA".';
                $this->codigo = "102 ".$this->status;
                return false;
            }
            $noCertificado = $Comprobante->getAttribute("noCertificado");
            $regex = "[0-9]{20}";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$noCertificado);
            if (!$ok) {
                $this->status = "NOM103 El atributo noCertificado no cumple con el patrón requerido.";
                $this->codigo = "103 ".$this->status;
                return false;
            }
            $Moneda = $Comprobante->getAttribute("Moneda");
            if ($Moneda != "MXN") {
                $this->status = "NOM104 El atributo Moneda debe tener el valor MXN.";
                $this->codigo = "104 ".$this->status;
                return false;
            }
            $TipoCambio = $Comprobante->getAttribute("TipoCambio");
            if ($TipoCambio!="" && $TipoCambio!=="1") {
                $this->status = 'NOM105 El atributo TipoCambio no tiene el valor = "1".';
                $this->codigo = "105 ".$this->status;
                return false;
            }
            $subTotal = (double)$Comprobante->getAttribute("subTotal");
            $aux = (double)$TotalPercepciones+(double)$TotalOtrosPagos;
            if (abs($subTotal-$aux)>0.001) {
                $this->status = "NOM106 El valor del atributo subTotal no coincide con la suma de Nomina12:TotalPercepciones más Nomina12:TotalOtrosPagos.";
                $this->codigo = "106 ".$this->status;
                return false;
            }
            $descuento = (double)$Comprobante->getAttribute("descuento");
            $aux = (double)$TotalDeducciones;
            if (abs($descuento-$aux)>0.001) {
                $this->status = "NOM107 El valor de descuento no es igual a Nomina12:TotalDeducciones.";
                $this->codigo = "107 ".$this->status;
                return false;
            }
            $total = $Comprobante->getAttribute("total");
            $regex = "[0-9]{1,18}(.[0-9]{1,2})?";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$total);
            if (!$ok) {
                $this->status = "NOM108 El atributo total no cumple con el patrón requerido.";
                $this->codigo = "108 ".$this->status;
                return false;
            }
            $total = (double)$total;
            $a_total = (double)$TotalPercepciones + (double)$TotalOtrosPagos - (double)$TotalDeducciones;
            if (abs($total-$a_total)>0.001) {
                $this->status = "NOM109 El valor del atributo total no coincide con la suma Nomina12:TotalPercepciones más Nomina12:TotalOtrosPagos menos Nomina12:TotalDeducciones.";
                $this->codigo = "109 ".$this->status;
                return false;
            }
            $tipoDeComprobante = $Comprobante->getAttribute("tipoDeComprobante");
            if ($tipoDeComprobante!="egreso") {
                $this->status = 'NOM110 El atributo tipoDeComprobante no tiene el valor = "egreso".';
                $this->codigo = "110 ".$this->status;
                return false;
            }
            $LugarExpedicion = $Comprobante->getAttribute("LugarExpedicion");
            $ok = $this->Checa_Catalogo("c_CP", $LugarExpedicion);
            if (!$ok) {
                $this->status = "NOM111 El valor del atributo LugarExpedicion no cumple con un valor del catálogo c_CodigoPostal.";
                $this->codigo = "111 ".$this->status;
                return false;
            }
            $motivoDescuento = $Comprobante->getAttribute("motivoDescuento");
            $NumCtaPago = $Comprobante->getAttribute("NumCtaPago");
            $condicionesDePago = $Comprobante->getAttribute("condicionesDePago");
            $SerieFolioFiscalOrig = $Comprobante->getAttribute("SerieFolioFiscalOrig");
            $FechaFolioFiscalOrig = $Comprobante->getAttribute("FechaFolioFiscalOrig");
            $MontoFolioFiscalOrig = $Comprobante->getAttribute("MontoFolioFiscalOrig");
            if ($motivoDescuento!="") {
                $this->status = "NOM112 El atributo motivoDescuento no debe existir.";
                $this->codigo = "112 ".$this->status;
                return false;
            }
            if ($NumCtaPago!="") {
                $this->status = "NOM112 El atributo NumCtaPago no debe existir.";
                $this->codigo = "112 ".$this->status;
                return false;
            }
            if ($condicionesDePago!="") {
                $this->status = "NOM112 El atributo condicionesDePago no debe existir.";
                $this->codigo = "112 ".$this->status;
                return false;
            }
            if ($SerieFolioFiscalOrig!="") {
                $this->status = "NOM112 El atributo SerieFolioFiscalOrig no debe existir.";
                $this->codigo = "112 ".$this->status;
                return false;
            }
            if ($FechaFolioFiscalOrig!="") {
                $this->status = "NOM112 El atributo FechaFolioFiscalOrig no debe existir.";
                $this->codigo = "112 ".$this->status;
                return false;
            }
            if ($MontoFolioFiscalOrig!="") {
                $this->status = "NOM112 El atributo MontoFolioFiscalOrig no debe existir.";
                $this->codigo = "112 ".$this->status;
                return false;
            }
            $Emisor = $Comprobante->getElementsByTagName('Emisor')->item(0);
            $rfcEmisor = $Emisor->getAttribute("rfc");
            if (strlen($rfcEmisor)==12 && $n_E_Curp!="") {
                $this->status = "NOM113 El atributo Nomina12:Emisor:Curp. no aplica para persona moral.";
                $this->codigo = "113 ".$this->status;
                return false;
            }
            if (strlen($rfcEmisor)==13 && $n_E_Curp=="") {
                $this->status = "NOM114 El atributo Nomina12:Emisor:Curp. Debe aplicar para persona física.";
                $this->codigo = "114 ".$this->status;
                return false;
            }
            $l_rfc_emisor= $this->lee_l_rfc($rfcEmisor);
            if (sizeof($l_rfc_emisor)==0) {
                $this->status = "NOM225 Error No clasificado: No existe registro Emisor ($rfcEmisor) en l_rfc.";
                $this->codigo = "225 ".$this->status;
                return false;
            }
            if ($l_rfc_emisor["rfc_sub"]=="si" && $n_subs==0) {
                $this->status = "NOM115 El nodo Subcontratacion se debe registrar.";
                $this->codigo = "115 ".$this->status;
                return false;
            }
            $lista = $Emisor->getElementsByTagName('DomicilioFiscal');
            if ($lista->length != 0) {
                $this->status = "NOM116 El elemento DomicilioFiscal no debe existir.";
                $this->codigo = "116 ".$this->status;
                return false;
            }
            $lista = $Emisor->getElementsByTagName('ExpedidoEn');
            if ($lista->length != 0) {
                $this->status = "NOM116 El elemento ExpedidoEn no debe de existir.";
                $this->codigo = "116 ".$this->status;
                return false;
            }
            $lista = $Emisor->getElementsByTagName('RegimenFiscal');
            if ($lista->length != 1) {
                $this->status = "NOM117 Solo debe existir un solo nodo RegimenFiscal.";
                $this->codigo = "117 ".$this->status;
                return false;
            }
            $RegimenFiscal = $lista->item(0);
            $Regimen=$RegimenFiscal->getAttribute("Regimen");
            $ok = $this->Checa_Catalogo("c_RegimenFiscal", $Regimen);
            if (!$ok) {
                $this->status = "NOM118 El atributo Regimen no cumple con un valor del catalogo c_RegimenFiscal.";
                $this->codigo = "118 ".$this->status;
                return false;
            }
            if (strlen($rfcEmisor)==13) { // Fisica
                $ok = $this->Checa_Catalogo("c_RegimenFisica", $Regimen);
                if (!$ok) {
                    $this->status = "NOM120 El atributo Regimen no cumple con un valor de acuerdo al tipo de persona física.";
                    $this->codigo = "120 ".$this->status;
                    return false;
                }
            } else { // Moral
                $ok = $this->Checa_Catalogo("c_RegimenMoral", $Regimen);
                if (!$ok) {
                    $this->status = "NOM119 El atributo Regimen no cumple con un valor de acuerdo al tipo de persona moral.";
                    $this->codigo = "119 ".$this->status;
                    return false;
                }
            }
            $Receptor = $Comprobante->getElementsByTagName('Receptor')->item(0);
            $rfcReceptor = $Receptor->getAttribute("rfc");
            if (strlen($rfcReceptor)!=13) {
                $this->status = "NOM121 El atributo cfdi:Comprobante.Receptor.rfc debe ser persona física (13 caracteres).";
                $this->codigo = "121 ".$this->status;
                return false;
            }
            $row= $this->lee_l_rfc($rfcReceptor);
            if (sizeof($row)==0) {
                $this->status = "NOM122 El atributo cfdi:Comprobante.Receptor.rfc no es válido según la lista de RFC inscritos no cancelados en el SAT (l_RFC).";
                $this->codigo = "122 ".$this->status;
                return false;
            }
            $lista = $Receptor->getElementsByTagName('Domicilio');
            if ($lista->length != 0) {
                $this->status = "NOM123 El nodo Domicilio no debe existir.";
                $this->codigo = "123 ".$this->status;
                return false;
            }
            $Conceptos = $Comprobante->getElementsByTagName('Concepto');
            if ($Conceptos->length != 1) {
                $this->status = "NOM124 El nodo concepto solo debe existir uno, sin elementos hijo.";
                $this->codigo = "124 ".$this->status;
                return false;
            }
            $Concepto = $Conceptos->item(0);
            if ($Concepto->hasChildNodes()) {
                $this->status = "NOM124 El nodo concepto solo debe existir uno, sin elementos hijo.";
                $this->codigo = "124 ".$this->status;
                return false;
            }
            $noIdentificacion = $Concepto->getAttribute("noIdentificacion");
            if ($noIdentificacion!="") {
                $this->status = "NOM125 El atributo noIdentificacion no debe existir.";
                $this->codigo = "125 ".$this->status;
                return false;
            }
            $cantidad = $Concepto->getAttribute("cantidad");
            if ($cantidad!=="1") {
                $this->status = 'NOM126 El atributo cfdi:Comprobante.Conceptos.Concepto.cantidad no tiene el valor = "1".';
                $this->codigo = "126 ".$this->status;
                return false;
            }
            $unidad = $Concepto->getAttribute("unidad");
            if ($unidad!="ACT") {
                $this->status = 'NOM127 El atributo cfdi:Comprobante.Conceptos.Concepto.unidad no tiene el valor = "ACT".';
                $this->codigo = "127 ".$this->status;
                return false;
            }
            $descripcion = $Concepto->getAttribute("descripcion");
            if ($descripcion!=utf8_encode("Pago de nómina")) {
                $this->status = 'NOM128 El atributo cfdi:Comprobante:Conceptos.Concepto.descripcion no tiene el valor "Pago de nómina".';
                $this->codigo = "128 ".$this->status;
                return false;
            }
            $a_total = (double)$TotalPercepciones + (double)$TotalOtrosPagos;
            $valorUnitario = (double)$Concepto->getAttribute("valorUnitario");
            if (abs($a_total-$valorUnitario)>0.001) {
                $this->status = "NOM129 El valor del atributo.cfdi:Comprobante.Conceptos.Concepto.valorUnitario no coincide con la suma TotalPercepciones más TotalOtrosPagos.";
                $this->codigo = "129 ".$this->status;
                return false;
            }
            $importe = (double)$Concepto->getAttribute("importe");
            if (abs($a_total-$importe)>0.001) {
                $this->status = "NOM130 El valor del atributo.cfdi:Comprobante.Conceptos.Concepto.Importe no coincide con la suma TotalPercepciones más TotalOtrosPagos.";
                $this->codigo = "130 ".$this->status;
                return false;
            }
            $Impuestos = $Comprobante->getElementsByTagName('Impuestos')->item(0);
            $nodo = $Impuestos->childNodes->item(1);
            if ($nodo != NULL) {
                $name=$nodo->nodeName;
                $this->status = "NOM131 El nodo cfdi:Comprobante.Impuestos no cumple la estructura.";
                $this->codigo = "131 ".$this->status;
                return false;
            }
            if ($Impuestos->hasAttributes())  {
                $this->status = "NOM131 El nodo cfdi:Comprobante.Impuestos no cumple la estructura.";
                $this->codigo = "131 ".$this->status;
                return false;
            } // }}}
        } // fin de 3.2
        if ($version == "3.3") {
            // {{{ Validaciones para  CFDI 3.3
            $fecha = $Comprobante->getAttribute("Fecha");
            $Moneda = $Comprobante->getAttribute("Moneda");
            if ($Moneda != "MXN") {
                $this->status .= '; NOM132 El atributo Moneda, debe tener el valor "MXN".';
                $this->codigo .= "; NOM132";
                $error=true;
            }
            $FormaPago = $Comprobante->getAttribute("FormaPago");
            if ($FormaPago != "99") {
                $this->status .= "; NOM133 El atributo FormaPago no tiene el valor =  99.";
                $this->codigo .= "; NOM133";
                $error=true;
            }
            $TipoDeComprobante = $Comprobante->getAttribute("TipoDeComprobante");
            if ($TipoDeComprobante!="N") {
                $this->status .= "; NOM134 El atributo TipoDeComprobante no tiene el valor =  N.";
                $this->codigo .= "; NOM134";
                $error=true;
            }
            $Emisor = $Comprobante->getElementsByTagName('Emisor')->item(0);
            $rfcEmisor = $Emisor->getAttribute("Rfc");
            if (strlen($rfcEmisor)==12 && $n_E_Curp!="") {
                $this->status .= "; NOM135 El atributo Nomina12:Emisor:Curp. no aplica para persona moral.";
                $this->codigo .= "; NOM135";
                $error=true;
            }
            if (strlen($rfcEmisor)==13 && $n_E_Curp=="") {
                $this->status .= "; NOM136 El atributo Nomina12:Emisor:Curp. Debe aplicar para persona fisica.";
                $this->codigo .= "; NOM136";
                $error=true;
            }
            $l_rfc_emisor= $this->lee_l_rfc($rfcEmisor);
            if (sizeof($l_rfc_emisor)==0) {
                $this->status .= "; NOM225 Error No clasificado: No existe registro Emisor ($rfcEmisor) en l_rfc.";
                $this->codigo .= "; NOM225";
                $error=true;
            }
            $Receptor = $Comprobante->getElementsByTagName('Receptor')->item(0);
            $rfcReceptor = $Receptor->getAttribute("Rfc");
            if (strlen($rfcReceptor)!=13) {
                $this->status .= "; NOM137 El atributo Comprobante.Receptor.rfc, debe ser de longitud 13.";
                $this->codigo .= "; NOM137";
                $error=true;
            }
            $row= $this->lee_l_rfc($rfcReceptor);
            if (sizeof($row)==0) {
                $this->status .= "; NOM138 El atributo cfdi:Comprobante.Receptor.rfc no es válido según la lista de RFC inscritos no cancelados en el SAT (l_RFC).";
                $this->codigo .= "; NOM138";
                $error=true;
            }
            $Conceptos = $Comprobante->getElementsByTagName('Concepto');
            if ($Conceptos->length != 1) {
                $this->status .= "; NOM139 El nodo Comprobante.Conceptos.Concepto, Solo puede registrarse un nodo concepto, sin elementos hijo.";
                $this->codigo .= "; NOM139";
                $error=true;
            }
            $Concepto = $Conceptos->item(0);
            if ($Concepto->hasChildNodes()) {
                $this->status .= "; NOM139 El nodo Comprobante.Conceptos.Concepto, Solo puede registrarse un nodo concepto, sin elementos hijo.";
                $this->codigo .= "; NOM139";
                $error=true;
            }
            $ClaveProdServ = $Concepto->getAttribute("ClaveProdServ");
            if ($ClaveProdServ!="84111505") {
                $this->status .= '; NOM140 El atributo Comprobante.Conceptos.Concepto,CkaveProdServ no tiene el valor "84111505".';
                $this->codigo .= "; NOM140";
                $error=true;
            }
            $NoIdentificacion = $Concepto->getAttribute("NoIdentificacion");
            if ($NoIdentificacion!="") {
                $this->status .= "; NOM141 El atributo NoIdentificacion no debe existir.";
                $this->codigo .= "; NOM141";
                $error=true;
            }
            $Cantidad = $Concepto->getAttribute("Cantidad");
            if ($Cantidad!="1") {
                $this->status .= '; NOM142 El atributo cfdi:Comprobante:Conceptos.Concepto.Cantidad no tiene el valor = "1".';
                $this->codigo .= "; NOM142";
                $error=true;
            }
            $ClaveUnidad = $Concepto->getAttribute("ClaveUnidad");
            if ($ClaveUnidad!="ACT") {
                $this->status .= '; NOM143 El atributo cfdi:Comprobante:Conceptos.Concepto.ClaveUnidad no tiene el valor = "ACT".';
                $this->codigo .= "; NOM143 ";
                $error=true;
            }
            $Unidad = $Concepto->getAttribute("Unidad");
            if ($Unidad!=null) {
                $this->status .= "; NOM144 El atributo cfdi:Comprobante:Conceptos.Concepto.Unidad no debe existir.";
                $this->codigo .= "; NOM144";
                $error=true;
            }
            $Descripcion = $Concepto->getAttribute("Descripcion");
            if ($Descripcion!=utf8_encode("Pago de nómina")) {
                $this->status .= "; NOM145 El atributo cfdi:Comprobante:Conceptos.Concepto.Descripcion no tiene el valor 'Pago de nómina'.";
                $this->codigo .= "; NOM145";
                $error=true;
            }
            $a_total = (double)$TotalPercepciones + (double)$TotalOtrosPagos;
            $ValorUnitario = (double)$Concepto->getAttribute("ValorUnitario");
            if (abs($a_total-$ValorUnitario)>0.001) {
                $this->status .= "; NOM146 El valor del atributo Comprobante.Conceptos.Concepto,ValorUnitario no coincide con la suma TotalPercepciones más TotalOtrosPagos.";
                $this->codigo .= "; NOM146";
                $error=true;
            }
            $Importe = (double)$Concepto->getAttribute("Importe");
            if (abs($a_total-$Importe)>0.001) {
                $this->status .= "; NOM147 El valor del atributo Comprobante.Conceptos.Concepto,importe no coincide con la suma TotalPercepciones más TotalOtrosPagos.";
                $this->codigo .= "; NOM147";
                $error=true;
            }
            $c_Descuento = (double)$Concepto->getAttribute("Descuento");
            if (abs($c_Descuento-$TotalDeducciones)>0.001) {
                $this->status .= "; NOM148 El valor del atributo Comprobante.Conceptos.Concepto,Descuento no es igual a el valor del campo Nomina12:TotalDeducciones.";
                $this->codigo .= "; NOM148";
                $error=true;
            }
            $Impuestos = $Comprobante->getElementsByTagName('Impuestos');
            if ($Impuestos->length > 0) {
                $this->status .= "; NOM149 El nodo Comprobante.Impuestos, no debe existir.";
                $this->codigo .= "; NOM149";
                $error=true;
            } // }}}
        } // fin de 3.3
        // {{{ Validacion de Complemento de NOmina
        $TipoNomina = $nomi->getAttribute("TipoNomina");
        $ok = $this->Checa_Catalogo("c_TipoNomina",$TipoNomina);
        if (!$ok) {
            $this->status .= ": NOM152 El valor del atributo Nomina.TipoNomina no cumple con un valor del catálogo c_TipoNomina.";
            $this->codigo .= "; NOM152";
            $error=true;
        }
        $a_pago = (int)$PeriodicidadPago;
        if ($TipoNomina=="E") {
           if ($a_pago != 99) {
                $this->status .= "; NOM154 El valor del atributo tipo de periodicidad no es 99.";
                $this->codigo .= "; NOM154";
                $error=true;
           }
        } 
        if ($TipoNomina=="O") {
           if ($a_pago < 1 || $a_pago > 9) {
               $this->status .= "; NOM153 El valor del atributo tipo de periodicidad no se encuentra entre 01 al 09.";
               $this->codigo .= "; NOM153";
               $error=true;
           }
        }
        $FechaInicialPago = new Datetime($nomi->getAttribute("FechaInicialPago"));
        $FechaFinalPago = new Datetime($nomi->getAttribute("FechaFinalPago"));
        if ($FechaInicialPago > $FechaFinalPago) {
            $this->status .= "; NOM155 El valor del atributo FechaInicialPago no es menor o igual al valor del atributo FechaFinalPago.";
            $this->codigo .= "; NOM155";
            $error=true;
        }
        if ((double)$NumDiasPagados<0.001) {
            $this->status .= "; NOM227 El valor del atributo Nomina.NumDiasPagados no cumple con el valor minimo permitido.";
            $this->codigo .= "; NOM227";
            $error=true;
        }
        if ((double)$NumDiasPagados>36160.000) {
            $this->status .= "; NOM227 El valor del atributo Nomina.NumDiasPagados no cumple con el valor maximo permitido.";
            $this->codigo .= "; NOM227";
            $error=true;
        }
        $percs = $nomi->getElementsByTagName('Percepciones');
        if ($percs->length > 0) { // SI hay percepciones
            $Percepciones = $percs->item(0);
            $TotalSueldos = $Percepciones->getAttribute("TotalSueldos");
            $TotalSeparacionIndemnizacion = $Percepciones->getAttribute("TotalSeparacionIndemnizacion");
            $TotalJubilacionPensionRetiro = $Percepciones->getAttribute("TotalJubilacionPensionRetiro");
            $a_total = (double)$TotalSueldos + 
                       (double)$TotalSeparacionIndemnizacion + 
                       (double)$TotalJubilacionPensionRetiro;
            if (abs($TotalPercepciones-$a_total)>0.001) {
                $this->status .= "; NOM157 El valor del atributo Nomina.TotalPercepciones no coincide con la suma TotalSueldos más TotalSeparacionIndemnizacion más TotalJubilacionPensionRetiro del  nodo Percepciones.";
                $this->codigo .= "; NOM157";
                $error=true;
            }
        } else { // No hay percepciones
            if ($TotalPercepciones != null) {
                $this->status .= "; NOM156 El atributo Nomina.TotalPercepciones, no debe existir.";
                $this->codigo .= "; NOM156";
                $error=true;
            }
        }
        $deducs = $nomi->getElementsByTagName('Deducciones');
        if ($deducs->length > 0) { // SI hay deducciones
            $Deducciones = $deducs->item(0);
            $TotalOtrasDeducciones = $Deducciones->getAttribute("TotalOtrasDeducciones");
            $TotalImpuestosRetenidos = $Deducciones->getAttribute("TotalImpuestosRetenidos");
            $a_total = (double)$TotalOtrasDeducciones + (double)$TotalImpuestosRetenidos;
            if (abs($TotalDeducciones-$a_total)>0.001) {
                $this->status .= "; NOM159 El valor del atributo Nomina.TotalDeducciones no coincide con la suma de los atributos TotalOtrasDeducciones más TotalImpuestosRetenidos del elemento Deducciones.";
                $this->codigo .= "; NOM159";
                $error=true;
            }
        } else { // No Hay Deducciones
            if ($TotalDeducciones != null) {
                $this->status .= "; NOM158 El atributo Nomina.TotalDeducciones, no debe existir.";
                $this->codigo .= "; NOM158";
                $error=true;
            }
        }
        $otros = $nomi->getElementsByTagName('OtrosPagos');
        if ($otros->length > 0) { // SI hay Otros Pagos
            $OtrosPagos = $otros->item(0);
            $l_OtroPago = $OtrosPagos->getElementsByTagName('OtroPago');
            $nb_OtroPago = $l_OtroPago->length;
            $suma=0;
            for ($i=0; $i<$nb_OtroPago; $i++) {
                $OtroPago=$l_OtroPago->item($i);
                $suma += (double)$OtroPago->getAttribute("Importe");
            }
            if (abs((double)$TotalOtrosPagos-$suma)>0.001) {
                $this->status .= "; NOM160 El valor del atributo Nomina.TotalOtrosPagos no está registrado o  no coincide con la suma de los atributos Importe de los nodos nomina12:OtrosPagos:OtroPago.";
                $this->codigo .= "; NOM160";
                $error=true;
            }
        } else { // NO hay otros pagos
            if ($TotalOtrosPagos != null) {
                $this->status .= "; NOM160 El valor del atributo Nomina.TotalOtrosPagos no está registrado o  no coincide con la suma de los atributos Importe de los nodos nomina12:OtrosPagos:OtroPago.";
                $this->codigo .= "; NOM160";
                $error=true;
            }
        } // }}}
        // {{{ Validacion de Emisor
        if ($n_E_RfcPatronOrigen != "") {
            $row= $this->lee_l_rfc($n_E_RfcPatronOrigen);
            if (sizeof($row)==0) {
                $this->status .= "; NOM161 El atributo Nomina.Emisor.RfcPatronOrigen no está inscrito en el SAT (l_RFC).";
                $this->codigo .= "; NOM161";
                $error=true;
            }
        }
        if ($n_Emisores->length > 0) { // SI hay Emisor
            $sncf = $n_Emisor->getElementsByTagName('EntidadSNCF');
            if ($sncf->length > 0) { // SI hay Entidad SNCF
                if ($l_rfc_emisor["rfc_sncf"]!="si") {
                    $this->status .= "; NOM166 El nodo Nomina.Emisor.EntidadSNCF no debe existir.";
                    $this->codigo .= "; NOM166";
                    $error=true;
                }
                $EntidadSNCF = $sncf->item(0);
                $OrigenRecurso = $EntidadSNCF->getAttribute("OrigenRecurso");
                $ok = $this->Checa_Catalogo("c_OrigenRecurso",$OrigenRecurso);
                if (!$ok) {
                    $this->status .= "; NOM167 El valor del atributo Nomina.Emisor.EntidadSNCF.OrigenRecurso no cumple con un valor del catálogo c_OrigenRecurso.";
                    $this->codigo .= "; NOM167";
                    $error=true;
                }
                $MontoRecursoPropio = $EntidadSNCF->getAttribute("MontoRecursoPropio");
                if ($OrigenRecurso=="IM") { // Ingresos Mixtos
                    if ($MontoRecursoPropio == null) {
                        $this->status .= "; NOM168 El atributo Nomina.Emisor.EntidadSNCF.MontoRecursoPropio debe existir.";
                        $this->codigo .= "; NOM168";
                        $error=true;
                    }
                    $a_total = (double)$TotalPercepciones + (double)$TotalOtrosPagos;
                    if ((double)$MontoRecursoPropio > $a_total) {
                        $this->status .= "; NOM170 El valor del atributo Nomina.Emisor.EntidadSNCF.MontoRecursoPropio no es menor a la suma de los valores de los atributos TotalPercepciones y TotalOtrosPagos. ";
                        $this->codigo .= "; NOM170";
                        $error=true;
                    }
                } else { // NO es IM
                    if ($MontoRecursoPropio != null) {
                        $this->status .= "; NOM169 El atributo Nomina.Emisor.EntidadSNCF.MontoRecursoPropio no debe existir.";
                        $this->codigo .= "; NOM169";
                        $error=true;
                    }
                }// IM
            } else { // no hay nodo hijo sncf
                if ($l_rfc_emisor["rfc_sncf"]=="si") {
                    $this->status .= "; NOM165 El nodo Nomina.Emisor.EntidadSNCF debe existir.";
                    $this->codigo .= "; NOM165";
                    $error=true;
                }
            } // sncf
        } //  hay emisor
        if ($n_E_RegistroPatronal == null) {
            /*
                Si no hay patronal no se valida estos
             */
        } else { // SI si hay registro patronal debe de estar los demas
            if ($NumSeguridadSocial == null || 
                $FechaInicioRelLaboral == null || $Antiguedad == null ||
                $RiesgoPuesto == null || $SalarioDiarioIntegrado == null) {
                $l = "";
                if ($NumSeguridadSocial==null) $l .= "NumSeguridadSocial ";
                if ($FechaInicioRelLaboral==null) $l .= "FechaInicioRelLaboral ";
                if ($Antiguedad==null) $l .= "Antigüedad ";
                if ($RiesgoPuesto==null) $l .= "RiesgoPuesto ";
                if ($SalarioDiarioIntegrado==null) $l .= "SalarioDiarioIntegrado ";
                $l = trim($l);
                $this->status .= "; NOM164 El(Los) atributo(s) $l debe(n) existir.";
                $this->codigo .= "; NOM164";
                $error=true;
            }
        }
        //
        if ((int)$TipoContrato >= 1 && (int)$TipoContrato <= 8) {
            if ($n_E_RegistroPatronal == null) {
                $this->status .= "; NOM162 El atributo Nomina.Emisor.RegistroPatronal se debe registrar.";
                $this->codigo .= "; NOM162";
                $error=true;
            }
        } else {
            if ($n_E_RegistroPatronal != null) {
                $this->status .= "; NOM163 El atributo Nomina.Emisor.RegistroPatronal  no se debe registrar.";
                $this->codigo .= "; NOM163";
                $error=true;
            }
        }
        // }}}
        // {{{ Valida Receptor
        $ok = $this->Checa_Catalogo("c_TipoContrato",$TipoContrato);
        if (!$ok) {
            $this->status .= "; NOM171 El valor del atributo Nomina.Receptor.TipoContrato no cumple con un valor del catálogo c_TipoContrato.";
            $this->codigo .= "; NOM171";
            $error=true;
        }
        if ($TipoJornada != null) {
            $ok = $this->Checa_Catalogo("c_TipoJornada",$TipoJornada);
            if (!$ok) {
                $this->status .= "; NOM172 El valor del atributo Nomina.Receptor.TipoJornada no cumple con un valor del catálogo c_TipoJornada.";
                $this->codigo .= "; NOM172";
                $error=true;
            }
        }
        $a_FechaInicioRelLaboral = new Datetime($FechaInicioRelLaboral);
        if ($FechaInicioRelLaboral != null) {
            if ($a_FechaInicioRelLaboral > $FechaFinalPago) {
                $this->status .= "; NOM173 El valor del atributo Nomina.Receptor.FechaInicioRelLaboral no es menor o igual al atributo a FechaFinalPago.";
                $this->codigo .= "; NOM173";
                $error=true;
            }
        }
        // Validar antiguedad
        if ($Antiguedad != null) {
            $regex = "P([1-9][0-9]{0,3})W";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$Antiguedad,$matches);
            if ($ok) {
                $dias = date_diff($FechaFinalPago, $a_FechaInicioRelLaboral);
                $dias = $dias->format("%a");
                $dias += 1;
                $semanas = floor($dias / 7);
                if ($semanas==0) $semanas=1;
                $aux2 = $matches[1];
                if ($aux2 > $semanas) {
                    $this->status .= "; NOM174 El valor numérico del atributo Nomina.Receptor.Antigüedad no es menor o igual al cociente de (la suma del número de días transcurridos entre la FechaInicioRelLaboral y la FechaFinalPago más uno) dividido entre siete.";
                    $this->codigo .= "; NOM174";
                    $error=true;
                }
            } else {
                $int_diff = date_diff($FechaFinalPago, $a_FechaInicioRelLaboral);
                $a_diff = "P";
                if ($int_diff->y>0) $a_diff .= $int_diff->y."Y";
                if ($int_diff->m>0) $a_diff .= $int_diff->m."M";
                $a_diff .= $int_diff->d."D";
                if ($a_diff != $Antiguedad) {
                    $this->status .= "; NOM175 El valor del atributo Nomina.Receptor.Antigüedad. no cumple con el número de años, meses y días transcurridos entre la FechaInicioRelLaboral y la FechaFinalPago.";
                    $this->codigo .= "; NOM175";
                    $error=true;
                }
            }
        }
        $ok = $this->Checa_Catalogo("c_TipoRegimen",$TipoRegimen);
        if (!$ok) {
            $this->status .= "; NOM176 El valor del atributo Nomina.Receptor.TipoRegimen no cumple con un valor del catálogo c_TipoRegimen.";
            $this->codigo .= "; NOM176";
            $error=true;
        }
        $TipoContrato = (int)$TipoContrato;
        $TipoRegimen = (int)$TipoRegimen;
        if ($TipoContrato >= 1 && $TipoContrato <= 8) {
            if ($TipoRegimen==2 || $TipoRegimen==3 || $TipoRegimen==4) {
                // OK
            } else {
                $this->status .= "; NOM177 El atributo Nomina.Receptor.TipoRegimen no es 02, 03 ó 04.";
                $this->codigo .= "; NOM177";
                $error=true;
            }
        }
        if ($TipoContrato >= 9) {
            if ($TipoRegimen>=5 && $TipoRegimen<=99) {
                // OK
            } else {
                $this->status .= "; NOM178 El atributo Nomina.Receptor.TipoRegimen no está entre 05 a 99.";
                $this->codigo .= "; NOM178";
                $error=true;
            }
        }
        $ok = $this->Checa_Catalogo("c_RiesgoPuesto",$RiesgoPuesto);
        if (!$ok) {
            $this->status .= "; NOM179 El valor del atributo Nomina.Receptor.RiesgoPuesto no cumple con un valor del catálogo c_RiesgoPuesto.";
            $this->codigo .= "; NOM179";
            $error=true;
        }
        $ok = $this->Checa_Catalogo("c_PeriodicidadPago",$PeriodicidadPago);
        if (!$ok) {
            $this->status .= "; NOM180 El valor del atributo Nomina.Receptor.PeriodicidadPago no cumple con un valor del catálogo c_PeriodicidadPago.";
            $this->codigo .= "; NOM180";
            $error=true;
        }
        if ($Banco != NULL) {
            $ok = $this->Checa_Catalogo("c_Banco",$Banco);
            if (!$ok) {
                $this->status .= "; NOM181 El valor del atributo Nomina.Receptor.Banco no cumple con un valor del catálogo c_Banco.";
                $this->codigo .= "; NOM181";
                $error=true;
            }
        }
        if ($CuentaBancaria != null) {
            $largo = strlen($CuentaBancaria);
            if ($largo==18) { // CLABE
                if ($Banco != NULL) {
                    $this->status .= "; NOM183 El atributo Banco no debe existir.";
                    $this->codigo .= "; NOM183";
                    $error=true;
                }
                $ok = $this->Valida_CLABE($CuentaBancaria);
                if (!$ok) {
                    $this->status .= "; NOM184 El dígito de control del atributo CLABE no es correcto.";
                    $this->codigo .= "; NOM184";
                    $error=true;
                }
            } elseif ($largo==16 || $largo==11 || $largo==10) { // debito, cuenta, tele
                if ($Banco == NULL) {
                    $this->status .= "; NOM185 El atributo Banco debe existir.";
                    $this->codigo .= "; NOM185";
                    $error=true;
                }
            } else {
                $this->status .= "; NOM182 El atributo CuentaBancaria no cumple con la longitud de 10, 11, 16 ó 18 posiciones.";
                $this->codigo .= "; NOM182";
                $error=true;
            }
        }
        $ok = $this->Checa_Catalogo("c_Estado",$ClaveEntFed,"MEX");
        if (!$ok) {
            $this->status .= "; NOM186 El valor del atributo ClaveEntFed no cumple con un valor del catálogo c_Estado.";
            $this->codigo .= "; NOM186";
            $error=true;
        }
        if ($n_subs>0) {
            $suma=0;
            for ($i=0; $i<$n_subs; $i++) {
                $SubContratacion = $subs->item($i);
                $RfcLabora = $SubContratacion->getAttribute("RfcLabora");
                if ($RfcLabora != "XEXX010101000") {
                    // 24/mar/2017 se permite el RFC generico extranjero
                    $row= $this->lee_l_rfc($RfcLabora);
                    if (sizeof($row)==0) {
                        $this->status .= "; NOM187 El valor del atributo Nomina.Receptor.SubContratacion.RfcLabora no está en la lista de RFC (l_RFC).";
                        $this->codigo .= "; NOM187";
                        $error=true;
                    }
                }
                $suma += (double)$SubContratacion->getAttribute("PorcentajeTiempo");
            }
            if (abs($suma-100)>0.001) {
                $this->status .= "; NOM188 La suma de los valores registrados en el atributo Nomina.Receptor.SubContratacion.PorcentajeTiempo no es igual a 100.";
                $this->codigo .= "; NOM188";
                $error=true;
            }
        }
        // }}} FIN de Receptor
        // {{{ Inicia Percepciones
        $t_sueldo=0;$t_separacion=0;$t_jubilacion=0;$t_gravado=0;$t_exento=0;
        $t_incapacidad=0;
        $hay_jubilacion=false; $hay_sueldo=false; $hay_separacion=false;
        $clave_39=false;$clave_44=false; $hay_14=false; 
        if ($percs->length > 0) { // SI hay percepciones
            $Percepciones = $percs->item(0);
            $l_Percepcion = $Percepciones->getElementsByTagName('Percepcion');
            $TotalSueldos = $Percepciones->getAttribute('TotalSueldos');
            $TotalSeparacionIndemnizacion = $Percepciones->getAttribute('TotalSeparacionIndemnizacion');
            $TotalJubilacionPensionRetiro = $Percepciones->getAttribute('TotalJubilacionPensionRetiro');
            $TotalGravado = $Percepciones->getAttribute('TotalGravado');
            $TotalExento = $Percepciones->getAttribute('TotalExento');
            $nb_Percepcion = $l_Percepcion->length;
            for ($i=0; $i<$nb_Percepcion; $i++) {
                $Percepcion = $l_Percepcion->item($i);
                $TipoPercepcion = $Percepcion->getAttribute("TipoPercepcion");
                $ok = $this->Checa_Catalogo("c_TipoPercepcion",$TipoPercepcion);
                if (!$ok) {
                    $this->status .= "; NOM196 El valor del atributo Nomina.Percepciones.Percepcion.TipoPercepcion no cumple con un valor del catálogo c_TipoPercepcion.";
                    $this->codigo .= "; NOM196";
                    $error=true;
                }
                $ImporteGravado = (double)$Percepcion->getAttribute("ImporteGravado");
                $ImporteExento = (double)$Percepcion->getAttribute("ImporteExento");
                $t_gravado += $ImporteGravado;
                $t_exento += $ImporteExento;
                if ($TipoPercepcion=="022"||$TipoPercepcion=="023"||
                    $TipoPercepcion=="025") {
                    $t_separacion += $ImporteGravado + $ImporteExento;
                    $hay_separacion=true;
                } elseif ($TipoPercepcion=="039") {
                    $t_jubilacion += $ImporteGravado + $ImporteExento;
                    $hay_jubilacion=true; $clave_39=true;
                } elseif ($TipoPercepcion=="044") {
                    $t_jubilacion += $ImporteGravado + $ImporteExento;
                    $hay_jubilacion=true; $clave_44=true;
                } else {
                    $t_sueldo += $ImporteGravado + $ImporteExento;
                    $hay_sueldo=true;
                    if ($TipoPercepcion=="014") {
                        $hay_14=true;
                        $t_incapacidad += $ImporteGravado + $ImporteExento;
                    }
                }
                $l_AccionesOTitulos = $Percepcion->getElementsByTagName('AccionesOTitulos');
                $largo = $l_AccionesOTitulos->length;
                if ($largo==0) { // NO Hay NODO acciones
                    if ($TipoPercepcion=="045") {
                        $this->status .= "; NOM202 El elemento AccionesOTitulos debe existir. Ya que la clave expresada en el atributo TipoPercepcion es 045.";
                        $this->codigo .= "; NOM202";
                        $error=true;
                    }
                } else { // SI Hay NODO acciones
                    if ($TipoPercepcion!="045") {
                        $this->status .= "; NOM203 El elemento AccionesOTitulos no debe existir. Ya que la clave expresada en el atributo TipoPercepcion no es 045.";
                        $this->codigo .= "; NOM203";
                        $error=true;
                    }
                }
                $l_HorasExtra = $Percepcion->getElementsByTagName('HorasExtra');
                $largo = $l_HorasExtra->length;
                if ($largo==0) { // NO Hay NODO HorasExtra
                    if ($TipoPercepcion=="019") {
                        $this->status .= "; NOM204 El elemento HorasExtra, debe existir. Ya que la clave expresada en el atributo TipoPercepcion es 019.";
                        $this->codigo .= "; NOM204";
                        $error=true;
                    }
                } else { // SI Hay NODO HorasExtra
                    if ($TipoPercepcion!="019") {
                        $this->status .= "; NOM205 El elemento HorasExtra, no debe existir. Ya que la clave expresada en el atributo TipoPercepcion no es 019.";
                        $this->codigo .= "; NOM205";
                        $error=true;
                    }
                    foreach ($l_HorasExtra as $HorasExtra) {
                        $TipoHoras = $HorasExtra->getAttribute("TipoHoras");
                        $ok = $this->Checa_Catalogo("c_TipoHoras",$TipoHoras);
                        if (!$ok) {
                            $this->status .= "; NOM208 El valor del atributo Nomina.Percepciones.Percepcon.HorasExtra.TipoHoras no cumple con un valor del catálogo c_TipoHoras.";
                            $this->codigo .= "; NOM208";
                            $error=true;
                        }
                    }
                }
            } // for percs
            $a_suma1 = (double)$TotalSueldos+
                       (double)$TotalSeparacionIndemnizacion+
                       (double)$TotalJubilacionPensionRetiro;
            $a_suma2 = (double)$TotalGravado+(double)$TotalExento;
            if (abs($a_suma1-$a_suma2)>0.001) {
                $this->status .= "; NOM189 La suma de los valores de los atributos TotalSueldos más TotalSeparacionIndemnizacion más TotalJubilacionPensionRetiro no es igual a la suma de los valores de los atributos TotalGravado más TotalExento.";
                $this->codigo .= "; NOM189";
                $error=true;
            }
            if (abs((double)$TotalGravado-$t_gravado)>0.001) {
                $this->status .= "; NOM193 El valor del atributo Nomina.Percepciones.TotalGravado, no es igual a la suma de los atributos ImporteGravado de los nodos Percepcion.";
                $this->codigo .= "; NOM193";
                $error=true;
            }
            if (abs((double)$TotalExento-$t_exento)>0.001) {
                $this->status .= "; NOM194 El valor del atributo Nomina.Percepciones.TotalExento, no es igual a la suma de los atributos ImporteExento de los nodos Percepcion.";
                $this->codigo .= "; NOM194";
                $error=true;
            }
            if ($TotalSueldos != null && 
                abs((double)$TotalSueldos-$t_sueldo)>0.001) {
                $this->status .= "; NOM190 El valor del atributo Nomina.Percepciones.TotalSueldos , no es igual a la suma de los atributos ImporteGravado e ImporteExento donde la clave expresada en el atributo TipoPercepcion es distinta de 022 Prima por Antigüedad, 023 Pagos por separación, 025 Indemnizaciones, 039 Jubilaciones, pensiones o haberes de retiro en una exhibición y 044 Jubilaciones, pensiones o haberes de retiro en parcialidades.";
                $this->codigo .= "; NOM190";
                $error=true;
            }
            if ((double)$TotalSeparacionIndemnizacion != $t_separacion) {
                $this->status .= "; NOM191 El valor del atributo Nomina.Percepciones.TotalSeparacionIndemnizacion, no es igual a la suma de los atributos ImporteGravado e ImporteExento donde la clave en el atributo TipoPercepcion es igual a 022 Prima por Antigüedad, 023 Pagos por separación ó 025 Indemnizaciones.";
                $this->codigo .= "; NOM191";
                $error=true;
            }
            /* TODO : Falta ejemplo NO se como hacer que llegue por aqui */
            if (abs((double)$TotalJubilacionPensionRetiro-$t_jubilacion)>0.001) {
                $this->status .= "; NOM192 El valor del atributo Nomina.Percepciones.TotalJubilacionPensionRetiro, no es igual a la suma de los atributos ImporteGravado e importeExento donde la clave expresada en el atributo TipoPercepcion es igual a 039(Jubilaciones, pensiones o haberes de retiro en una exhibición)  ó 044 (Jubilaciones, pensiones o haberes de retiro en parcialidades).";
                $this->codigo .= "; NOM192";
                $error=true;
            }
            /* TODO : Falta ejemplo NO se como hacer que llegue por aqui */
            if ($hay_sueldo && $TotalSueldos == null) {
                $this->status .= "; NOM197 TotalSueldos, debe existir. Ya que la clave expresada en TipoPercepcion es distinta de 022, 023, 025, 039 y 044.";
                $this->codigo .= "; NOM197";
                $error=true;
            }
            if ($hay_separacion && $TotalSeparacionIndemnizacion == null) {
                $this->status .= "; NOM198 TotalSeparacionIndemnizacion y el elemento SeparacionIndemnizacion, debe existir. Ya que la clave expresada en TipoPercepcion es 022 ó 023 ó 025.";
                $this->codigo .= "; NOM198";
                $error=true;
            }
            if (!$hay_separacion && $TotalSeparacionIndemnizacion != null) {
                $this->status .= "; NOM198 TotalSeparacionIndemnizacion y el elemento SeparacionIndemnizacion, debe existir. Ya que la clave expresada en TipoPercepcion es 022 ó 023 ó 025.";
                $this->codigo .= "; NOM198";
                $error=true;
            }
            if ($hay_jubilacion && $TotalJubilacionPensionRetiro == null) {
                $this->status .= "; NOM199 TotalJubilacionPensionRetiro y el elemento JubilacionPensionRetiro debe existir,  ya que la clave expresada en el atributo TipoPercepcion es 039 ó 044.";
                $this->codigo .= "; NOM199";
                $error=true;
            }
            if (!$hay_jubilacion && $TotalJubilacionPensionRetiro != null) {
                $this->status .= "; NOM199 TotalJubilacionPensionRetiro y el elemento JubilacionPensionRetiro debe existir,  ya que la clave expresada en el atributo TipoPercepcion es 039 ó 044.";
                $this->codigo .= "; NOM199";
                $error=true;
            }
            if ($t_gravado + $t_exento <= 0) {
                $this->status .= "; NOM195 La suma de los importes de los atributos ImporteGravado e ImporteExento no es mayor que cero.";
                $this->codigo .= "; NOM195";
                $error=true;
            }
            $l_JubilacionPensionRetiro = $Percepciones->getElementsByTagName('JubilacionPensionRetiro');
            $largo = $l_JubilacionPensionRetiro->length;
            if ($largo==0) { //Si no hay nodo jubilacion
                if ($hay_jubilacion) {
                    $this->status .= "; NOM199 TotalJubilacionPensionRetiro y el elemento JubilacionPensionRetiro debe existir,  ya que la clave expresada en el atributo TipoPercepcion es 039 ó 044.";
                    $this->codigo .= "; NOM199";
                    $error=true;
                }
            } else { // SI hay nodo jubilacion
                $JubilacionPensionRetiro = $l_JubilacionPensionRetiro->item(0);
                $TotalUnaExhibicion = $JubilacionPensionRetiro->getAttribute("TotalUnaExhibicion");
                $TotalParcialidad = $JubilacionPensionRetiro->getAttribute("TotalParcialidad");
                $MontoDiario = $JubilacionPensionRetiro->getAttribute("MontoDiario");
                $IngresoAcumulable = $JubilacionPensionRetiro->getAttribute("IngresoAcumulable");
                $IngresoNoAcumulable = $JubilacionPensionRetiro->getAttribute("IngresoNoAcumulable");
                if ($TotalUnaExhibicion!=null &&
                    ($TotalParcialidad!=null || $MontoDiario!=null) ) {
                    $this->status .= "; NOM209 Los atributos MontoDiario y TotalParcialidad no deben de existir, ya que existe valor en TotalUnaExhibicion.";
                    $this->codigo .= "; NOM209";
                    $error=true;
                    }
                if ($TotalParcialidad!=null &&
                    ($TotalUnaExhibicion!=null || $MontoDiario==null) ) {
                    $this->status .= "; NOM210 El atributo MontoDiario debe existir y el atributo TotalUnaExhibicion no debe existir, ya que Nomina.Percepciones.JubilacionPensionRetiro.TotalParcialidad tiene valor.";
                    $this->codigo .= "; NOM210";
                    $error=true;
                    }
                if (!$hay_jubilacion) {
                    $this->status .= "; NOM199 El elemento JubilacionPensionRetiro  no debe existir,  ya que la clave expresada en el atributo TipoPercepcion no es 039 ó 044.";
                    $this->codigo .= "; NOM199";
                    $error=true;
                }
                if ($clave_39 && ($TotalUnaExhibicion==null || 
                                  $TotalParcialidad!=null || 
                                  $MontoDiario!=null) ) {
                    $this->status .= "; NOM200 TotalUnaExhibicion debe existir y no deben existir TotalParcialidad, MontoDiario. Ya que la clave expresada en el atributo TipoPercepcion es 039.";
                    $this->codigo .= "; NOM200";
                    $error=true;
                }
                if ($clave_44 && ($TotalUnaExhibicion!=null || 
                                  $TotalParcialidad==null || 
                                  $MontoDiario==null) ) {
                    $this->status .= "; NOM201 TotalUnaExhibicion no debe existir y deben existir TotalParcialidad, MontoDiario. Ya que la clave expresada en el atributo TipoPercepcion es 044.";
                    $this->codigo .= "; NOM201";
                    $error=true;
                }
            }
            $l_SeparacionIndemnizacion = $Percepciones->getElementsByTagName('SeparacionIndemnizacion');
            $largo = $l_SeparacionIndemnizacion->length;
            if ($largo==0 && $hay_separacion) { //Si no hay nodo separacion
                $this->status .= "; NOM198 TotalSeparacionIndemnizacion y el elemento SeparacionIndemnizacion, debe existir. Ya que la clave expresada en TipoPercepcion es 022 ó 023 ó 025.";
                $this->codigo .= "; NOM198";
                $error=true;
            }
            if ($largo!=0 && !$hay_separacion) { //no hay nodo separacion
                $this->status .= "; NOM198 TotalSeparacionIndemnizacion y el elemento SeparacionIndemnizacion, debe existir. Ya que la clave expresada en TipoPercepcion es 022 ó 023 ó 025.";
                $this->codigo .= "; NOM198";
                $error=true;
            }
            foreach ($l_SeparacionIndemnizacion as $SeparacionIndemnizacion) {
                $NumAnosServicio = $SeparacionIndemnizacion->getAttribute(utf8_encode("NumAñosServicio"));
                if ((int)$NumAnosServicio<0) {
                    $this->status .= "; NOM226 El valor del atributo Nomina.Percepciones:Percepcion:SeparacionIndemnizacion:NumAñosServicio no cumple con el valor minimo permitido.";
                    $this->codigo .= "; NOM226";
                    $error=true;
                }
                if ((int)$NumAnosServicio>99) {
                    $this->status .= "; NOM226 El valor del atributo Nomina.Percepciones:Percepcion:SeparacionIndemnizacion:NumAñosServicio no cumple con el valor maximo permitido.";
                    $this->codigo .= "; NOM226";
                    $error=true;
                }
            }
        } // Hay percepciones
        // }}}
        // {{{ Inicia Deducciones
        $hay_6 = false; $hay_2 = false; $t_impu=0;
        if ($deducs->length > 0) { // SI hay deducciones
            $Deducciones = $deducs->item(0);
            $TotalImpuestosRetenidos = $Deducciones->getAttribute("TotalImpuestosRetenidos");
            $Deducciones = $Deducciones->getElementsByTagName('Deduccion');
            $nb_Deduccion = $Deducciones->length;
            for ($i=0; $i<$nb_Deduccion; $i++) {
                $Deduccion = $Deducciones->item($i);
                $TipoDeduccion = $Deduccion->getAttribute("TipoDeduccion");
                $ok = $this->Checa_Catalogo("c_TipoDeduccion", $TipoDeduccion);
                if (!$ok) {
                    $this->status .= "; NOM213 El valor del atributo Nomina.Deducciones.Deduccion.TipoDeduccion no cumple con un valor del catálogo c_TipoDeduccion.";
                    $this->codigo .= "; NOM213";
                    $error=true;
                }
                $Importe = (double)$Deduccion->getAttribute("Importe");
                if ($Importe<=0) {
                    $this->status .= "; NOM216 Nomina.Deducciones.Deduccion.Importe no es mayor que cero.";
                    $this->codigo .= "; NOM216";
                    $error=true;
                }
                if ($TipoDeduccion=="002") { // Impuesto
                    $hay_2 = true; 
                    $t_impu += $Importe;
                }
                if ($TipoDeduccion=="006") { // Incapacidad
                    $hay_6 = true;
                    $t_incapacidad += $Importe;
                }
            } // foreach deducc
            if ($hay_2) { // Si hubo concepto deduccion impuesto
                if (abs((double)$TotalImpuestosRetenidos-$t_impu)>0.001) {
                    $this->status .= "; NOM211 El valor en el atributo Nomina.Deducciones.TotalImpuestosRetenidos no es igual a la suma de los atributos Importe de las deducciones que tienen expresada la clave 002 en el atributo TipoDeduccion.";
                    $this->codigo .= "; NOM211";
                    $error=true;
                }
            } else { // NO hubo tipo impuesto
                if ($TotalImpuestosRetenidos!=null) {
                    $this->status .= "; NOM212 Nomina.Deducciones.TotalImpuestosRetenidos no debe existir ya que no existen deducciones con clave 002 en el atributo TipoDeduccion.";
                    $this->codigo .= "; NOM212";
                    $error=true;
                }
            }
        } // hay deduc
        // }}}
        // {{{ Incapacidades
        // En Percepciones->Percepcion se prendio $hay_14 y y sumo t_incapcidad
        // En Deducciones->wDeduccion se prendio $hay_6 y sumo en t_incapcidad
        $Incapacidades = $nomi->getElementsByTagName('Incapacidad');
        if ($Incapacidades->length==0) { // No hay Nodo Incapacidades
            if ($hay_14) { // Hay el concepto Incapacidad en Percepciones
                $this->status .= "; NOM206 El nodo Incapacidades debe existir, Ya que la clave expresada en el atributo TipoPercepcion es 014.";
                $this->codigo .= "; NOM206";
                $error=true;
            }
            if ($hay_6) { // Hay el concepto Incapacidad en Deducciones
                $this->status .= "; NOM214 Debe existir el elemento Incapacidades, ya que la clave expresada en Nomina.Deducciones.Deduccion.TipoDeduccion es 006.";
                $this->codigo .= "; NOM214";
                $error=true;
            }
        } else { // SI hay incapacidades
            if (!$hay_14 && !$hay_6) { // NO hay Incapacidad en Percepciones ni deducciones
                $this->status .= "; NOM214 Debe existir el elemento Incapacidades, ya que la clave expresada en Nomina.Deducciones.Deduccion.TipoDeduccion es 006.";
                $this->codigo .= "; NOM214";
                $error=true;
            }
            $suma=0;
            foreach ($Incapacidades as $Incapacidad) {
                $TipoIncapacidad = $Incapacidad->getAttribute("TipoIncapacidad");
                $ok = $this->Checa_Catalogo("c_TipoIncapacidad", $TipoIncapacidad);
                if (!$ok) {
                    $this->status .= "; NOM224 El valor del atributo incapacidades.incapacidad.TipoIncapacidad no cumple con un valor del catálogo c_TIpoIncapacidad.";
                    $this->codigo .= "; NOM224";
                    $error=true;
                }
                $ImporteMonetario = $Incapacidad->getAttribute("ImporteMonetario");
                $suma += (double)$ImporteMonetario;
            }
            if (abs($suma-$t_incapacidad)>0.001) { 
                if ($hay_14) {
                    $this->status .= "; NOM207 La suma de los campos ImporteMonetario debe ser igual a la suma de los valores ImporteGravado e ImporteExento de la percepción, Ya que la clave expresada en el atributo TipoPercepcion es 014.";
                    $this->codigo .= "; NOM207";
                    $error=true;
                } else {
                    $this->status .= "; NOM215 El atributo Deduccion:Importe no es igual a la suma de los nodos Incapacidad:ImporteMonetario.  Ya que la clave expresada en Nomina.Deducciones.Deduccion.TipoDeduccion es 006.";
                    $this->codigo .= "; NOM215";
                    $error=true;
                }
            }
        }
        // }}}
        // {{{ Otros Pagos
        $OtrosPagos = $nomi->getElementsByTagName('OtroPago');
        if ($OtrosPagos->length!=0) { // SI hay Nodo OtrosPagos
            foreach ($OtrosPagos as $OtroPago) {
                $TipoOtroPago = $OtroPago->getAttribute("TipoOtroPago");
                $ok = $this->Checa_Catalogo("c_TipoOtroPago", $TipoOtroPago);
                if (!$ok) {
                    $this->status .= "; NOM217 El valor del atributo Nomina.OtrosPagos.OtroPago.TipoOtroPago no cumple con un valor del catálogo c_TipoOtroPago.";
                    $this->codigo .= "; NOM217";
                    $error=true;
                }
                $Importe= (double)$OtroPago->getAttribute("Importe");
                if ($Importe<=0) {
                    $this->status .= "; NOM220 Nomina.OtrosPagos.OtroPago.Importe no es mayor que cero.";
                    $this->codigo .= "; NOM220";
                    $error=true;
                }
                $l_SubsidioAlEmpleo = $OtroPago->getElementsByTagName("SubsidioAlEmpleo");
                $largo = $l_SubsidioAlEmpleo->length;
                if ($largo==0) { // NO Hay NODO SubsidioAlEmpleo
                    if ($TipoOtroPago=="002") {
                        $this->status .= "; NOM219 El nodo SubsidioAlEmpleo. debe existir, ya que el valor de Nomina.OtrosPagos.OtroPago.TipoOtroPago es 002.";
                        $this->codigo .= "; NOM219";
                        $error=true;
                    }
                } else { // SI Hay NODO SubsidioAlEmpleo
                    if ($TipoOtroPago!="002") {
                        $this->status .= "; NOM219 El nodo SubsidioAlEmpleo. debe existir, ya que el valor de Nomina.OtrosPagos.OtroPago.TipoOtroPago es 002.";
                        $this->codigo .= "; NOM219";
                        $error=true;
                    }
                    $SubsidioAlEmpleo = $l_SubsidioAlEmpleo->item(0);
                    $SubsidioCausado = (double)$SubsidioAlEmpleo->getAttribute("SubsidioCausado");
                    if ($SubsidioCausado<$Importe) {
                        $this->status .= '; NOM221 Nomina.OtrosPagos.OtroPago.SubsidioAlEmpleo.SubsidioCausado no es mayor o igual que el valor del atributo "importe" del nodo OtroPago.';
                        $this->codigo .= "; NOM221";
                        $error=true;
                    }
                }
                $l_CompensacionSaldosAFavor = $OtroPago->getElementsByTagName("CompensacionSaldosAFavor");
                $largo = $l_CompensacionSaldosAFavor->length;
                if ($largo==0) { // NO Hay NODO CompensacionSaldosAFavor
                    if ($TipoOtroPago=="004") {
                        $this->status .= "; NOM218 El nodo CompensacionSaldosAFavor debe existir, ya que el valor de Nomina.OtrosPagos.OtroPago.TipoOtroPago es 004.";
                        $this->codigo .= "; NOM218";
                        $error=true;
                    }
                } else { // SI Hay NODO CompensacionSaldosAFavor
                    if ($TipoOtroPago!="004") {
                        $this->status .= "; NOM218 El nodo CompensacionSaldosAFavor debe existir, ya que el valor de Nomina.OtrosPagos.OtroPago.TipoOtroPago es 004.";
                        $this->codigo .= "; NOM218";
                        $error=true;
                    }
                    $CompensacionSaldosAFavor = $l_CompensacionSaldosAFavor->item(0);
                    $SaldoAFavor = (double)$CompensacionSaldosAFavor->getAttribute("SaldoAFavor");
                    $RemanenteSalFav = (double)$CompensacionSaldosAFavor->getAttribute("RemanenteSalFav");
                    if ($SaldoAFavor < $RemanenteSalFav) {
                        $this->status .= "; NOM222 Nomina.OtrosPagos.OtroPago.CompensacionSaldosAFavor.SaldoAFavor no es mayor o igual que el valor del atributo CompensacionSaldosAFavor:RemanenteSalFav.";
                        $this->codigo .= "; NOM222";
                        $error=true;
                    }
                    $Ano = $CompensacionSaldosAFavor->getAttribute(utf8_encode("Año"));
                    if ((int)$Ano>(int)date("Y")) {
                        $this->status .= "; NOM223 Nomina.OtrosPagos.OtroPago.CompensacionSaldosAFavor.Año  no es menor que el año en curso.";
                        $this->codigo .= "; NOM223";
                        $error=true;
                    }
                }
            }
        }
        // }}}
        if ($error) {
            if (substr($this->status,0,2)=="; ") $this->status=substr($this->status,2);
            if (substr($this->codigo,0,2)=="; ") $this->codigo=substr($this->codigo,2);
            return false;
        } else {
            $this->status = "NOM000 Validacion de semantica nomina 1.2 correcta.";
            $this->codigo = 0;
            return true;
        }
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
    // {{{ cuenta_l_rfc
    private function cuenta_l_rfc() {
        // $cant= $this->conn->GetOne("select count(*) from pac_l_rfc");
        // $this->cuenta = $cant;
        $this->cuenta = 1; // Siempre hay registros
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
    // {{{ Valida_CLABE($CuentaBancaria)
    private function Valida_CLABE($cuenta) {
        $ok = true;
        // 17 dígitos CLABE 0   3   2   1   8   0   0   0   0   1   1   8   3   5   9   7   1
        // ×
        // Factores de peso 3   7   1   3   7   1   3   7   1   3   7   1   3   7   1   3   7
        // = ( % 10 )
        // Productos, módulo 10 0   1   2   3   6   0   0   0   0   3   7   8   9   5   9   1   7
        // Suma de productos, módulo 10 1
        // 10 - suma, módulo 10 9 (dígito control)
        //
        $suma = 0;
        $peso = "37137137137137137";
        for ($i=0;$i<17;$i++) {
            $a = (int)substr($cuenta,$i,1);
            $b = (int)substr($peso,$i,1);
            $c = ($a * $b) % 10;
            $suma += $c;
            // echo "i=$i a=$a b=$b c=$c\n";
        }
        // echo "suma=$suma\n";
        $dig = 10 - ($suma % 10);
        $ultimo = substr($cuenta,-1);
        // echo "ultimo=$ultimo dig=$dig\n";
        $ok = ($dig==$ultimo);
        return $ok;
    }
    // }}}
}
