<?php
error_reporting(E_ALL);
class Nomi12 {
    var $xml_cfd;
    var $con;
    var $codigo;
    var $status;
    // {{{ valida : semantica_nomi12
    public function valida($xml_cfd,$conn) {
        $ok = true;
        $this->xml_cfd = $xml_cfd;
        $this->conn = $conn;
        $this->status = "NOM000: Inicia Validacion de semantica nomina";
        $this->codigo = "0 ".$this->status;
        /// Verifica sea version 1.2
        $Comprobante = $this->xml_cfd->getElementsByTagName('Comprobante')->item(0);
        $version = $Comprobante->getAttribute("version");
        // $nomi = $Comprobante->getElementsByTagName('Nomina')->item(0);
        $nomi = $this->xml_cfd->getElementsByTagName('Nomina')->item(0);
        $nombre = $nomi->parentNode->nodeName;
        if ($nombre != "cfdi:Complemento") {
            $this->status = "NOM148: El nodo Nomina no se puede utilizar dentro del elemento ComplementoConcepto ($nombre).";
            $this->codigo = "148 ".$this->status;
            return false;
        }
        $Version = $nomi->getAttribute("Version");
        if ($Version != "1.2") {
            $this->status = "NOM000: Solo valida Version 1.2";
            $this->codigo = "0 ".$this->status;
            return true; // Correcta
        }
        // }}}
        // {{{ Atributos generales de Nomina, lee otros nodos
        $TotalPercepciones = $nomi->getAttribute("TotalPercepciones");
        $TotalOtrosPagos = $nomi->getAttribute("TotalOtrosPagos");
        $TotalDeducciones = $nomi->getAttribute("TotalDeducciones");
        if ($TotalPercepciones=="" && $TotalOtrosPagos=="") {
            $this->status = "NOM149: El nodo Nomina no tiene TotalPercepciones y/o TotalOtrosPagos";
            $this->codigo = "149 ".$this->status;
            return false;
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
						// <xs:attribute name="AntigÃ¼edad" use="optional">
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
                $this->status = "NOM101: El atributo fecha no cumple con el patrón requerido";
                $this->codigo = "101 ".$this->status;
                return false;
            }
            $metodoDePago = $Comprobante->getAttribute("metodoDePago");
            if ($metodoDePago != "NA") {
                $this->status = "NOM102: El atributo metodoDePago no tiene el valor 'NA'";
                $this->codigo = "102 ".$this->status;
                return false;
            }
            $noCertificado = $Comprobante->getAttribute("noCertificado");
            $regex = "[0-9]{20}";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$noCertificado);
            if (!$ok) {
                $this->status = "NOM103: El atributo noCertificado no cumple con el patrón requerido";
                $this->codigo = "103 ".$this->status;
                return false;
            }
            $Moneda = $Comprobante->getAttribute("Moneda");
            if ($Moneda != "MXN") {
                $this->status = "NOM104: El atributo Moneda, no cumple con el valor MXN";
                $this->codigo = "104 ".$this->status;
                return false;
            }
            $TipoCambio = $Comprobante->getAttribute("TipoCambio");
            if ($TipoCambio!="" && $TipoCambio!="1.00") {
                $this->status = "NOM105: El atributo TipoCambio no tiene el valor = '1'.";
                $this->codigo = "105 ".$this->status;
                return false;
            }
            $subTotal = (double)$Comprobante->getAttribute("subTotal");
            if ($subTotal!=(double)$TotalPercepciones+(double)$TotalOtrosPagos) {
                $this->status = "NOM106: El valor del atributo subTotal no coincide con la suma de Nomina12:TotalPercepciones más Nomina12:TotalOtrosPagos.";
                $this->codigo = "106 ".$this->status;
                return false;
            }
            $descuento = (double)$Comprobante->getAttribute("descuento");
            if ($descuento!=(double)$TotalDeducciones) {
                $this->status = "NOM107: El valor de descuento no es igual a Nomina12:TotalDeducciones";
                $this->codigo = "107 ".$this->status;
                return false;
            }
            $total = (double)$Comprobante->getAttribute("total");
            $regex = "[0-9]{1,18}(.[0-9]{1,2})?";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$total);
            if (!$ok) {
                $this->status = "NOM108: El atributo total, no cumple con el patron requerido";
                $this->codigo = "108 ".$this->status;
                return false;
            }
            $a_total = (double)$TotalPercepciones + (double)$TotalOtrosPagos - (double)$TotalDeducciones;
            if ($total!=$a_total) {
                $this->status = "NOM109: El valor del atributo total no coincide con la suma Nomina12:TotalPercepciones más Nomina12:TotalOtrosPagos.menos Nomina12:TotalDeducciones";
                $this->codigo = "109 ".$this->status;
                return false;
            }
            $tipoDeComprobante = $Comprobante->getAttribute("tipoDeComprobante");
            if ($tipoDeComprobante!="egreso") {
                $this->status = "NOM110: El atributo tipoDeComprobante no tiene el valor = 'egreso'.";
                $this->codigo = "110 ".$this->status;
                return false;
            }
            $LugarExpedicion = $Comprobante->getAttribute("LugarExpedicion");
            $regex = "[0-9]{5}";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$LugarExpedicion);
            if (!$ok) {
                $this->status = "NOM111: El atributo cfdi:Comprobante:LugarExpedicion debe cumplir con el patron [0-9]{5}.";
                $this->codigo = "111 ".$this->status;
                return false;
            }
            $ok = $this->Checa_Catalogo("c_CP", $LugarExpedicion);
            if (!$ok) {
                $this->status = "NOM111: El valor del atributo LugarExpedicion no cumple con un valor en c_CodigoPostal";
                $this->codigo = "111 ".$this->status;
                return false;
            }
            $motivoDescuento = $Comprobante->getAttribute("motivoDescuento");
            $NumCtaPago = $Comprobante->getAttribute("NumCtaPago");
            $condicionesDePago = $Comprobante->getAttribute("condicionesDePago");
            $SerieFolioFiscalOrig = $Comprobante->getAttribute("SerieFolioFiscalOrig");
            $FechaFolioFiscalOrig = $Comprobante->getAttribute("FechaFolioFiscalOrig");
            $MontoFolioFiscalOrig = $Comprobante->getAttribute("MontoFolioFiscalOrig");
            if ($motivoDescuento!="" || $NumCtaPago!="" ||
                $condicionesDePago!="" || $SerieFolioFiscalOrig!="" ||
                $FechaFolioFiscalOrig!="" || $MontoFolioFiscalOrig!="") {
                $this->status = "NOM112: No deben de existir los atributos motivoDescuento, condicionesDePago, SerieFolioFiscalOrig, FechaFolioFiscalOrig ni MontoFolioFiscalOrig";
                $this->codigo = "112 ".$this->status;
                return false;
            }
            $Emisor = $Comprobante->getElementsByTagName('Emisor')->item(0);
            $rfcEmisor = $Emisor->getAttribute("rfc");
            if (strlen($rfcEmisor)==12 && $n_E_Curp!="") {
                $this->status = "NOM113: El atributo Nomina12:Emisor:Curp. no aplica para persona moral.";
                $this->codigo = "113 ".$this->status;
                return false;
            }
            if (strlen($rfcEmisor)==13 && $n_E_Curp=="") {
                $this->status = "NOM114: El atributo Nomina12:Emisor:Curp. Debe aplicar para persona fisica.";
                $this->codigo = "114 ".$this->status;
                return false;
            }
            $l_rfc_emisor= $this->lee_l_rfc($rfcEmisor);
            if (sizeof($l_rfc_emisor)==0) {
                $this->status = "NOM115: No existe registro en l_rfc";
                $this->codigo = "115 ".$this->status;
                return false;
            }
            if ($l_rfc_emisor["rfc_sub"]=="si" && $n_subs==0) {
                $this->status = "NOM115: El nodo Subcontratacion se debe registrar";
                $this->codigo = "115 ".$this->status;
                return false;
            }
            $lista = $Emisor->getElementsByTagName('DomicilioFiscal');
            if ($lista->length != 0) {
                $this->status = "NOM116: El elemento DomicilioFiscal no debe existir";
                $this->codigo = "116 ".$this->status;
                return false;
            }
            $lista = $Emisor->getElementsByTagName('ExpedidoEn');
            if ($lista->length != 0) {
                $this->status = "NOM116: El elemento ExpedidoEn no debe de existir";
                $this->codigo = "116 ".$this->status;
                return false;
            }
            $lista = $Emisor->getElementsByTagName('RegimenFiscal');
            if ($lista->length != 1) {
                $this->status = "NOM117: Solo debe existir un solo nodo RegimenFiscal";
                $this->codigo = "117 ".$this->status;
                return false;
            }
            $RegimenFiscal = $lista->item(0);
            $Regimen=$RegimenFiscal->getAttribute("Regimen");
            $ok = $this->Checa_Catalogo("c_RegimenFiscal", $Regimen);
            if (!$ok) {
                $this->status = "NOM118: El atributo Regimen no cumple con un valor en  c_RegimenFiscal.";
                $this->codigo = "118 ".$this->status;
                return false;
            }
            $Receptor = $Comprobante->getElementsByTagName('Receptor')->item(0);
            $rfcReceptor = $Receptor->getAttribute("rfc");
            if (strlen($rfcReceptor)!=13) {
                $this->status = "NOM119: El atributo cfdi:Comprobante.Receptor.rfc debe ser persona física (13 caracteres).";
                $this->codigo = "119 ".$this->status;
                return false;
            }
            $row= $this->lee_l_rfc($rfcReceptor);
            if (sizeof($row)==0) {
                $this->status = "NOM120: El atributo cfdi:Comprobante.Receptor.rfc no es válido según la lista de RFC inscritos no cancelados en el SAT (l_RFC).";
                $this->codigo = "120 ".$this->status;
                return false;
            }
            $lista = $Receptor->getElementsByTagName('Domicilio');
            if ($lista->length != 0) {
                $this->status = "NOM121: El nodo Domicilio no debe existir";
                $this->codigo = "121 ".$this->status;
                return false;
            }
            $Conceptos = $Comprobante->getElementsByTagName('Concepto');
            if ($Conceptos->length != 1) {
                $this->status = "NOM122: El nodo concepto solo debe existir uno";
                $this->codigo = "122 ".$this->status;
                return false;
            }
            $Concepto = $Conceptos->item(0);
            if ($Concepto->hasChildNodes()) {
                $this->status = "NOM122: El nodo concepto solo debe existir uno, sin elementos hijo";
                $this->codigo = "122 ".$this->status;
                return false;
            }
            $noIdentificacion = $Concepto->getAttribute("noIdentificacion");
            if ($noIdentificacion!="") {
                $this->status = "NOM123: El atributo noIdentificacion no debe existir";
                $this->codigo = "123 ".$this->status;
                return false;
            }
            $cantidad = $Concepto->getAttribute("cantidad");
            if ($cantidad!="1") {
                $this->status = "NOM124: El atributo cfdi:Comprobante:Conceptos.Concepto.cantidad no tiene el valor '1'";
                $this->codigo = "124 ".$this->status;
                return false;
            }
            $unidad = $Concepto->getAttribute("unidad");
            if ($unidad!="ACT") {
                $this->status = "NOM125: El atributo cfdi:Comprobante:Conceptos.Concepto.unidad no tiene el valor 'ACT'";
                $this->codigo = "125 ".$this->status;
                return false;
            }
            $descripcion = strtoupper($Concepto->getAttribute("descripcion"));
            if ($descripcion!="PAGO DE NOMINA") {
                $this->status = "NOM126: El atributo cfdi:Comprobante:Conceptos.Concepto.descripcion no tiene el valor 'Pago de Nomina'";
                $this->codigo = "126 ".$this->status;
                return false;
            }
            $a_total = (double)$TotalPercepciones + (double)$TotalOtrosPagos;
            $valorUnitario = (double)$Concepto->getAttribute("valorUnitario");
            if ($a_total != $valorUnitario) {
                $this->status = "NOM127: El valor del atributo.cfdi:Comprobante.Conceptos.Concepto.valorUnitario no coincide con la suma TotalPercepciones más TotalOtrosPagos.";
                $this->codigo = "127 ".$this->status;
                return false;
            }
            $importe = (double)$Concepto->getAttribute("importe");
            if ($a_total != $importe) {
                $this->status = "NOM128: El valor del atributo.cfdi:Comprobante.Conceptos.Concepto.Importe no coincide con la suma TotalPercepciones más TotalOtrosPagos.";
                $this->codigo = "128 ".$this->status;
                return false;
            }
            $Impuestos = $Comprobante->getElementsByTagName('Impuestos')->item(0);
            $nodo = $Impuestos->childNodes->item(1);
            if ($nodo != NULL) {
                $name=$nodo->nodeName;
                $this->status = "NOM129: El nodo cfdi:Comprobante.Impuestos no cumple la estructura, no debe de tener nodos hijos ($name)";
                $this->codigo = "129 ".$this->status;
                return false;
            }
            if ($Impuestos->hasAttributes())  {
                $this->status = "NOM129: El nodo cfdi:Comprobante.Impuestos no cumple la estructura,  no debe de tener atributos";
                $this->codigo = "129 ".$this->status;
                return false;
            } // }}}
        } // fin de 3.2
        // {{{ Validacion de Complemento de NOmina
        $TipoNomina = $nomi->getAttribute("TipoNomina");
        $ok = $this->Checa_Catalogo("c_TipoNomina",$TipoNomina);
        if (!$ok) {
            $this->status = "NOM150: El valor del atributo Nomina.TipoNomina no está en catálogo c_TipoNomina";
            $this->codigo = "150  ".$this->status;
            return false;
        }
        $a_pago = (int)$PeriodicidadPago;
        if ($TipoNomina=="E" && $a_pago != 99) {
            $this->status = "NOM152: El valor del atributo tipo de periodicidad no es 99";
            $this->codigo = "152 ".$this->status;
            return false;
        } elseif ($TipoNomina=="O" && $a_pago > 1 && $a_pago < 9) {
            // OK
        } else {
            $this->status = "NOM151: El valor del atributo tipo de periodicidad no se encuentra entre 01 al 09";
            $this->codigo = "151 ".$this->status;
            return false;
        }
        $FechaInicialPago = new Datetime($nomi->getAttribute("FechaInicialPago"));
        $FechaFinalPago = new Datetime($nomi->getAttribute("FechaFinalPago"));
        if ($FechaInicialPago > $FechaFinalPago) {
            $this->status = "NOM153: El valor del atributo FechaInicialPago no es menor o igual al valor del atributo FechaFinalPago.";
            $this->codigo = "153 ".$this->status;
            return false;
        }
        if ($FechaFinalPago < $FechaInicialPago) {
            $this->status = "NOM154: El valor del atributo Nomina.FechaFinalPago no es mayor o igual al valor del atributo FechaInicialPago.";
            $this->codigo = "154 ".$this->status;
            return false;
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
            if ($TotalPercepciones != $a_total) {
                $this->status = "NOM156: El valor del atributo Nomina.TotalPercepciones no coincide con la suma TotalSueldos más TotalSeparacionIndemnizacion más TotalJubilacionPensionRetiro del nodo Percepciones.";
                $this->codigo = "156 ".$this->status;
                return false;
            }
        } else { // No hay percepciones
            if ($TotalPercepciones != null) {
                $this->status = "NOM155: El atributo Nomina.TotalPercepciones, no debe existir.";
                $this->codigo = "155 ".$this->status;
                return false;
            }
        }
        $deducs = $nomi->getElementsByTagName('Deducciones');
        if ($deducs->length > 0) { // SI hay deducciones
            $Deducciones = $deducs->item(0);
            $TotalOtrasDeducciones = $Deducciones->getAttribute("TotalOtrasDeducciones");
            $TotalImpuestosRetenidos = $Deducciones->getAttribute("TotalImpuestosRetenidos");
            $a_total = (double)$TotalOtrasDeducciones + (double)$TotalImpuestosRetenidos;
            if ($TotalDeducciones != $a_total) {
                $this->status = "NOM158: El valor del atributo Nomina.TotalDeducciones no coincide con la suma de los atributos TotalOtrasDeducciones más TotalImpuestosRetenidos del elemento Deducciones.";
                $this->codigo = "158 ".$this->status;
                return false;
            }
        } else { // No Hay Deducciones
            if ($TotalDeducciones != null) {
                $this->status = "NOM157: El atributo Nomina.TotalDeducciones, no debe existir";
                $this->codigo = "157 ".$this->status;
                return false;
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
            if ((double)$TotalOtrosPagos != $suma) {
                $this->status = "NOM159: El valor del atributo Nomina.TotalOtrosPagos no coincide con la suma de los atributos Importe de los nodos nomina12:OtrosPagos:OtroPago.";
                $this->codigo = "159 ".$this->status;
                return false;
            }
        } else { // NO hay otros pagos
            if ($TotalOtrosPagos != null) {
                $this->status = "NOM159: Nomina:TotalOtrosPagos no debe de existir si no hay OtrosPagos";
                $this->codigo = "159 ".$this->status;
                return false;
            }
        } // }}}
        // {{{ Validacion de Emisor
        if ($n_E_RfcPatronOrigen != "") {
            $row= $this->lee_l_rfc($n_E_RfcPatronOrigen);
            if (sizeof($row)==0) {
                $this->status = "NOM160: El atributo Nomina.Emisor.RfcPatronOrigen no está inscrito en el SAT (l_RFC)";
                $this->codigo = "160 ".$this->status;
                return false;
            }
        }
        if ($n_Emisores->length > 0) { // SI hay Emisor
            $sncf = $n_Emisor->getElementsByTagName('EntidadSNCF');
            if ($sncf->length > 0) { // SI hay Entidad SNCF
                if ($l_rfc_emisor["rfc_sncf"]!="si") {
                    $this->status = "NOM163: El nodo Nomina.Emisor.EntidadSNCF existe y NO se espera porque NO tiene la marca de estar adherido al SNCF.";
                    $this->codigo = "163 ".$this->status;
                    return false;
                }
                $EntidadSNCF = $sncf->item(0);
                $OrigenRecurso = $EntidadSNCF->getAttribute("OrigenRecurso");
                $ok = $this->Checa_Catalogo("c_OrigenRecurso",$OrigenRecurso);
                if (!$ok) {
                    $this->status = "NOM164: El valor del atributo Nomina.Emisor.EntidadSNCF.OrigenRecurso no está en catálogo c_OrigenRecurso";
                    $this->codigo = "164 ".$this->status;
                    return false;
                }
                $MontoRecursoPropio = $EntidadSNCF->getAttribute("MontoRecursoPropio");
                if ($OrigenRecurso=="IM") { // Ingresos Mixtos
                    if ($MontoRecursoPropio == null) {
                        $this->status = "NOM165: El atributo Nomina.Emisor.EntidadSNCF.MontoRecursoPropio debe existir";
                        $this->codigo = "165 ".$this->status;
                        return false;
                    }
                    $a_total = (double)$TotalPercepciones + (double)$TotalOtrosPagos;
                    if ((double)$MontoRecursoPropio > $a_total) {
                        $this->status = "NOM166: El valor del atributo Nomina.Emisor.EntidadSNCF.MontoRecursoPropio no es menor a la suma de los valores de los atributos TotalPercepciones y TotalOtrosPagos. ";
                        $this->codigo = "166 ".$this->status;
                        return false;
                    }
                } else { // NO es IM
                    if ($MontoRecursoPropio != null) {
                        $this->status = "NOM165: El atributo Nomina.Emisor.EntidadSNCF.MontoRecursoPropio solo debe existir si el OrigenRecurso es igual a 'IM'";
                        $this->codigo = "165 ".$this->status;
                        return false;
                    }
                }// IM
            } else { // no hay nodo hijo sncf
                if ($l_rfc_emisor["rfc_sncf"]=="si") {
                    $this->status = "NOM163: El nodo Nomina.Emisor.EntidadSNCF no existe y se espera porque tiene la marca de estar adherido al SNCF.";
                    $this->codigo = "163 ".$this->status;
                    return false;
                }
            } // sncf
        } //  hay emisor
        if ($n_E_RegistroPatronal == null) {
            if ($NumSeguridadSocial != null ||
                $FechaInicioRelLaboral != null || $Antiguedad != null ||
                $RiesgoPuesto != null || $SalarioDiarioIntegrado != null) {
                $this->status = "NOM162: Uno de los atributos nomina12:Receptor:NumSeguridadSocial ($NumSeguridadSocial), nomina12:Receptor:FechaInicioRelLaboral ($FechaInicioRelLaboral), nomina12:Receptor:Antigüedad ($Antiguedad),  nomina12:Receptor:RiesgoPuesto ($RiesgoPuesto) y nomina12:Receptor:SalarioDiarioIntegrado ($SalarioDiarioIntegrado) existe.";
                $this->codigo = "162 ".$this->status;
                return false;
            }
        } else { // SI si hay registro patronal
            if ($NumSeguridadSocial == null || 
                $FechaInicioRelLaboral == null || $Antiguedad == null ||
                $RiesgoPuesto == null || $SalarioDiarioIntegrado == null) {
                $this->status = "NOM162: Uno de los atributos nomina12:Receptor:NumSeguridadSocial ($NumSeguridadSocial), nomina12:Receptor:FechaInicioRelLaboral ($FechaInicioRelLaboral), nomina12:Receptor:Antigüedad ($Antiguedad),  nomina12:Receptor:RiesgoPuesto ($RiesgoPuesto) y nomina12:Receptor:SalarioDiarioIntegrado ($SalarioDiarioIntegrado) no existen.";
                $this->codigo = "162 ".$this->status;
                return false;
            }
        }
        //
        if ((int)$TipoContrato >= 1 && (int)$TipoContrato <= 8) {
            if ($n_E_RegistroPatronal == null) {
                $this->status = "NOM161: El atributo Nomina.Emisor.RegistroPatronal no existe";
                $this->codigo = "161 ".$this->status;
                return false;
            }
        } else {
            if ($n_E_RegistroPatronal != null) {
                $this->status = "NOM161: El atributo Nomina.Emisor.RegistroPatronal NO debe de existir";
                $this->codigo = "161 ".$this->status;
                return false;
            }
        }
        // }}}
        // {{{ Valida Receptor
        $ok = $this->Checa_Catalogo("c_TipoContrato",$TipoContrato);
        if (!$ok) {
            $this->status = "NOM167: El valor del atributo Nomina.Receptor.TipoContrato no está en catálogo c_TipoContrato";
            $this->codigo = "167 ".$this->status;
            return false;
        }
        if ($TipoJornada != null) {
            $ok = $this->Checa_Catalogo("c_TipoJornada",$TipoJornada);
            if (!$ok) {
                $this->status = "NOM168: El valor del atributo Nomina.Receptor.TipoJornada no está en catálogo c_TipoJornada";
                $this->codigo = "168 ".$this->status;
                return false;
            }
        }
        $a_FechaInicioRelLaboral = new Datetime($FechaInicioRelLaboral);
        if ($FechaInicioRelLaboral != null) {
            if ($a_FechaInicioRelLaboral > $FechaFinalPago) {
                $this->status = "NOM169: El valor del atributo Nomina.Receptor.FechaInicioRelLaboral no es menor o igual al atributo a FechaFinalPago.";
                $this->codigo = "169 ".$this->status;
                return false;
            }
        }
        // TODO: Validar antiguedad
        if ($Antiguedad != null) {
            $regex = "P([1-9][0-9]{0,3})W";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$Antiguedad,$matches);
            if ($ok) {
                $dias = date_diff($FechaFinalPago, $a_FechaInicioRelLaboral);
                $dias = $dias->format("%a");
                $dias += 1;
                $semanas = floor($dias / 7);
                $aux2 = $matches[1];
                if ($aux2 > $semanas) {
                    $this->status = "NOM170: El valor numerico del atributo Nomina.Receptor.Antigüedad ($aux2) no es menor o igual al cociente de (la suma del número de días transcurridos entre la FechaInicioRelLaboral y la FechaFinalPago más uno) dividido entre siete ($semanas).";
                    $this->codigo = "170 ".$this->status;
                    return false;
                }
            } else {
                $int_ant = new DateInterval($Antiguedad);
                $int_diff = date_diff($FechaFinalPago, $a_FechaInicioRelLaboral);
                $a_diff = $int_diff->format("P%yY%mM%dD");
                if ($a_diff != $Antiguedad) {
                    $this->status = "NOM171: El valor del atributo Nomina.Receptor.Antigüedad ($Antiguedad). no cumple con el número de años, meses y días transcurridos entre la FechaInicioRelLaboral y la FechaFinalPago ($a_diff).";
                    $this->codigo = "171 ".$this->status;
                    return false;
                }
            }
        }
        $ok = $this->Checa_Catalogo("c_TipoRegimen",$TipoRegimen);
        if (!$ok) {
            $this->status = "NOM172: El valor del atributo Nomina.Receptor.TipoRegimen no está en catálogo c_TipoRegimen";
            $this->codigo = "172 ".$this->status;
            return false;
        }
        $TipoContrato = (int)$TipoContrato;
        $TipoRegimen = (int)$TipoRegimen;
        if ($TipoContrato >= 1 && $TipoContrato <= 8) {
            if ($TipoRegimen==2 || $TipoRegimen==3 || $TipoRegimen==4) {
                // OK
            } else {
                $this->status = "NOM173: El atributo Nomina.Receptor.TipoRegimen no es 02, 03 ó 04.";
                $this->codigo = "173 ".$this->status;
                return false;
            }
        }
        if ($TipoContrato >= 9) {
            if ($TipoRegimen>=5 && $TipoRegimen<=99) {
                // OK
            } else {
                $this->status = "NOM174: El atributo Nomina.Receptor.TipoRegimen no está entre 05 a 99.";
                $this->codigo = "174 ".$this->status;
                return false;
            }
        }
        $ok = $this->Checa_Catalogo("c_RiesgoPuesto",$RiesgoPuesto);
        if (!$ok) {
            $this->status = "NOM175: El valor del atributo Nomina.Receptor.RiesgoPuesto no está en catálogo c_RiesgoPuesto";
            $this->codigo = "175 ".$this->status;
            return false;
        }
        $ok = $this->Checa_Catalogo("c_PeriodicidadPago",$PeriodicidadPago);
        if (!$ok) {
            $this->status = "NOM176: El valor del atributo Nomina.Receptor.PeriodicidadPago no está en catálogo c_PeriodicidadPago";
            $this->codigo = "176 ".$this->status;
            return false;
        }
        if ($Banco != NULL) {
            $ok = $this->Checa_Catalogo("c_Banco",$Banco);
            if (!$ok) {
                $this->status = "NOM177: El valor del atributo Nomina.Receptor.Banco no está en catálogo c_Banco";
                $this->codigo = "177 ".$this->status;
                return false;
            }
        }
        $largo = strlen($CuentaBancaria);
        if ($largo==18) { // CLABE
            if ($Banco != NULL) {
                $this->status = "NOM179: El nodo Banco no debe existir (Es CLABE)";
                $this->codigo = "179 ".$this->status;
                return false;
            }
            $ok = $this->Valida_CLABE($CuentaBancaria);
            if (!$ok) {
                $this->status = "NOM180: El dígito de control debe ser correcto cuando ingresan una CLABE.";
                $this->codigo = "180 ".$this->status;
                return false;
            }
        } elseif ($largo==16 || $largo==11 || $largo==10) { // debito, cuenta, tele
            if ($Banco == NULL) {
                $this->status = "NOM181: El nodo Banco debe existir";
                $this->codigo = "181 ".$this->status;
                return false;
            }
        } else {
            $this->status = "NOM178: El atributo CuentaBancaria no cumple con la longitud de 10, 11, 16 ó 18 posiciones.";
            $this->codigo = "178 ".$this->status;
            return false;
        }
        $ok = $this->Checa_Catalogo("c_Estado",$ClaveEntFed,"MEX");
        if (!$ok) {
            $this->status = "NOM182: El valor del atributo ClaveEntFed no es una clave del catálogo de c_Estado, donde la clave de país es MEX.";
            $this->codigo = "182 ".$this->status;
            return false;
        }
        if ($n_subs>0) {
            $suma=0;
            for ($i=0; $i<$n_subs; $i++) {
                $SubContratacion = $subs->item($i);
                $RfcLabora = $SubContratacion->getAttribute("RfcLabora");
                $row= $this->lee_l_rfc($RfcLabora);
                if (sizeof($row)==0) {
                    $this->status = "NOM183: El valor del atributo Nomina.Receptor.SubContratacion.RfcLabora no está en la lista de RFC (l_RFC).";
                    $this->codigo = "183 ".$this->status;
                    return false;
                }
                $suma += (double)$SubContratacion->getAttribute("PorcentajeTiempo");
            }
            if ($suma!=100) {
                $this->status = "NOM184: La suma del valor de Nomina.Receptor.SubContratacion.PorcentajeTiempo no es igual a 100";
                $this->codigo = "184 ".$this->status;
                return false;
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
                    $this->status = "NOM192: El valor del atributo Nomina.Percepciones.Percepcion.TipoPercepcion no es una clave del catálogo de c_TipoPercepcion.";
                    $this->codigo = "192 ".$this->status;
                    return false;
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
                        $this->status = "NOM202: El elemento AccionesOTitulos debe existir. Ya que la clave expresada en el atributo TipoPercepcion es 045.";
                        $this->codigo = "202 ".$this->status;
                        return false;
                    }
                } else { // SI Hay NODO acciones
                    if ($TipoPercepcion!="045") {
                        $this->status = "NOM203: El elemento AccionesOTitulos no debe existir. Ya que la clave expresada en el atributo TipoPercepcion no es 045.";
                        $this->codigo = "203 ".$this->status;
                        return false;
                    }
                }
                $l_HorasExtra = $Percepcion->getElementsByTagName('HorasExtra');
                $largo = $l_HorasExtra->length;
                if ($largo==0) { // NO Hay NODO HorasExtra
                    if ($TipoPercepcion=="019") {
                        $this->status = "NOM204: El elemento HorasExtra, debe existir. Ya que la clave expresada en el atributo TipoPercepcion es 019.";
                        $this->codigo = "204 ".$this->status;
                        return false;
                    }
                } else { // SI Hay NODO HorasExtra
                    if ($TipoPercepcion!="019") {
                        $this->status = "NOM205: El elemento HorasExtra, no debe existir. Ya que la clave expresada en el atributo TipoPercepcion no es 019.";
                        $this->codigo = "205 ".$this->status;
                        return false;
                    }
                    foreach ($l_HorasExtra as $HorasExtra) {
                        $TipoHoras = $HorasExtra->getAttribute("TipoHoras");
                        $ok = $this->Checa_Catalogo("c_TipoHoras",$TipoHoras);
                        if (!$ok) {
                            $this->status = "NOM208: El nodo TipoHoras Nomina.Percepciones.Percepcon.HorasExtra.TipoHoras no es una clave del catálogo de c_TipoHoras";
                            $this->codigo = "208 ".$this->status;
                            return false;
                        }
                    }
                }
            } // for percs
            $a_suma1 = (double)$TotalSueldos+
                       (double)$TotalSeparacionIndemnizacion+
                       (double)$TotalJubilacionPensionRetiro;
            $a_suma2 = (double)$TotalGravado+(double)$TotalExento;
            if ($a_suma1 != $a_suma2) {
                $this->status = "NOM185: La suma de los valores de los atributos TotalSueldos más TotalSeparacionIndemnizacion más TotalJubilacionPensionRetiro ($a_suma1) no es igual a la suma de los valores de los atributos TotalGravado más TotalExento ($a_suma2).";
                $this->codigo = "185 ".$this->status;
                return false;
            }
            if ((double)$TotalGravado != $t_gravado) {
                $this->status = "NOM189: El valor del atributo Nomina.Percepciones.TotalGravado, no es igual a la suma de los atributos ImporteGravado de los nodos Percepcion.";
                $this->codigo = "189 ".$this->status;
                return false;
            }
            if ((double)$TotalExento != $t_exento) {
                $this->status = "NOM190: El valor del atributo Nomina.Percepciones.TotalExento, no es igual a la suma de los atributos ImporteExento de los nodos Percepcion.";
                $this->codigo = "190 ".$this->status;
                return false;
            }
            if ($TotalSueldos != null && (double)$TotalSueldos != $t_sueldo) {
                $this->status = "NOM186: El valor del atributo Nomina.Percepciones.TotalSueldos , no es igual a la suma de los atributos ImporteGravado e ImporteExento donde la clave expresada en el atributo TipoPercepcion es distinta de 022 Prima por Antigüedad, 023 Pagos por separación, 025 Indemnizaciones, 039 Jubilaciones, pensiones o haberes de retiro en una exhibición y 044 Jubilaciones, pensiones o haberes de retiro en parcialidades.";
                $this->codigo = "186 ".$this->status;
                return false;
            }
            if ((double)$TotalSeparacionIndemnizacion != $t_separacion) {
                $this->status = "NOM187: El valor del atributo Nomina.Percepciones.TotalSeparacionIndemnizacion, no es igual a la suma de los atributos ImporteGravado e ImporteExento donde la clave en el atributo TipoPercepcion es igual a 022 Prima por Antigüedad, 023 Pagos por separación ó 025 Indemnizaciones.";
                $this->codigo = "187 ".$this->status;
                return false;
            }
            if ((double)$TotalJubilacionPensionRetiro != $t_jubilacion) {
                $this->status = "NOM188: El valor del atributo Nomina.Percepciones.TotalJubilacionPensionRetiro, no es igual a la suma de los atributos ImporteGravado e importeExento donde la clave expresada en el atributo TipoPercepcion es igual a 039(Jubilaciones, pensiones o haberes de retiro en una exhibición)  ó 044 (Jubilaciones, pensiones o haberes de retiro en parcialidades).";
                $this->codigo = "188 ".$this->status;
                return false;
            }
            if ($hay_sueldo && $TotalSueldos == null) {
                $this->status = "NOM194: TotalSueldos, debe existir. Ya que la clave expresada en TipoPercepcion es distinta de 022, 023, 025, 039 y 044";
                $this->codigo = "194 ".$this->status;
                return false;
            }
            if (!$hay_sueldo && $TotalSueldos != null) {
                $this->status = "NOM195: TotalSueldos, no debe existir. Ya que la clave expresada en TipoPercepcion es 022, 023, 025, 039 y 044";
                $this->codigo = "195 ".$this->status;
                return false;
            }
            if ($hay_separacion && $TotalSeparacionIndemnizacion == null) {
                $this->status = "NOM196: TotalSeparacionIndemnizacion debe existir. Ya que la clave expresada en TipoPercepcion es 022 ó 023 ó 025.";
                $this->codigo = "196 ".$this->status;
                return false;
            }
            if (!$hay_separacion && $TotalSeparacionIndemnizacion != null) {
                $this->status = "NOM197: TotalSeparacionIndemnizacion no debe existir. Ya que la clave expresada en TipoPercepcion no es 022 ó 023 ó 025.";
                $this->codigo = "197 ".$this->status;
                return false;
            }
            if ($hay_jubilacion && $TotalJubilacionPensionRetiro == null) {
                $this->status = "NOM198: TotalJubilacionPensionRetiro debe existir,  ya que la clave expresada en el atributo TipoPercepcion es 039 ó 044.";
                $this->codigo = "198 ".$this->status;
                return false;
            }
            if (!$hay_jubilacion && $TotalJubilacionPensionRetiro != null) {
                $this->status = "NOM199: TotalJubilacionPensionRetiro no debe existir,  ya que la clave expresada en el atributo TipoPercepcion no es 039 ó 044,";
                $this->codigo = "199 ".$this->status;
                return false;
            }
            if ($t_gravado + $t_exento <= 0) {
                $this->status = "NOM191: Los importes de los atributos ImporteGravado e ImporteExento no es mayor que cero.";
                $this->codigo = "191 ".$this->status;
                return false;
            }
            $l_JubilacionPensionRetiro = $Percepciones->getElementsByTagName('JubilacionPensionRetiro');
            $largo = $l_JubilacionPensionRetiro->length;
            if ($largo==0) { //Si no hay nodo jubilacion
                if ($hay_jubilacion) {
                    $this->status = "NOM198: El elemento JubilacionPensionRetiro debe existir,  ya que la clave expresada en el atributo TipoPercepcion es 039 ó 044.";
                    $this->codigo = "198 ".$this->status;
                    return false;
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
                    $this->status = "NOM209: Los atributos MontoDiario y TotalParcialidad no deben de existir, ya que existe valor en TotalUnaExhibicion";
                    $this->codigo = "209 ".$this->status;
                    return false;
                    }
                if ($TotalParcialidad!=null &&
                    ($TotalUnaExhibicion!=null || $MontoDiario==null) ) {
                    $this->status = "NOM210: El atributo MontoDiario debe existir y el atributo TotalUnaExhibicion no debe existir, ya que Nomina.Percepciones.JubilacionPensionRetiro.TotalParcialidad tiene valor.";
                    $this->codigo = "210 ".$this->status;
                    return false;
                    }
                if (!$hay_jubilacion) {
                    $this->status = "NOM199: El elemento JubilacionPensionRetiro  no debe existir,  ya que la clave expresada en el atributo TipoPercepcion no es 039 ó 044,";
                    $this->codigo = "199 ".$this->status;
                    return false;
                }
                if ($clave_39 && ($TotalUnaExhibicion==null || 
                                  $TotalParcialidad!=null || 
                                  $MontoDiario!=null) ) {
                    $this->status = "NOM200: TotalUnaExhibicion debe existir y no deben existir TotalParcialidad, MontoDiario. Ya que la clave expresada en el atributo TipoPercepcion es 039.";
                    $this->codigo = "200 ".$this->status;
                    return false;
                }
                if ($clave_44 && ($TotalUnaExhibicion!=null || 
                                  $TotalParcialidad==null || 
                                  $MontoDiario==null) ) {
                    $this->status = "NOM201: TotalUnaExhibicion no debe existir y deben existir TotalParcialidad, MontoDiario. Ya que la clave expresada en el atributo TipoPercepcion es 044.";
                    $this->codigo = "201 ".$this->status;
                    return false;
                }
            }
            $l_SeparacionIndemizacion = $Percepciones->getElementsByTagName('SeparacionIndemnizacion');
            $largo = $l_SeparacionIndemizacion->length;
            if ($largo==0 && $hay_separacion) { //Si no hay nodo separacion
                $this->status = "NOM196: El elemento SeparacionIndemnizacion, debe existir. Ya que la clave expresada en TipoPercepcion es 022 ó 023 ó 025.";
                $this->codigo = "196 ".$this->status;
                return false;
            }
            if ($largo!=0 && !$hay_separacion) { //no hay nodo separacion
                $this->status = "NOM197: El elemento SeparacionIndemnizacion, no debe existir. Ya que la clave expresada en TipoPercepcion no es 022 ó 023 ó 025.";
                $this->codigo = "197 ".$this->status;
                return false;
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
                    $this->status = "NOM213: El valor de Nomina.Deducciones.Deduccion.TipoDeduccion no es una clave del catálogo de c_TipoDeduccion .";
                    $this->codigo = "213 ".$this->status;
                    return false;
                }
                $Importe = (double)$Deduccion->getAttribute("Importe");
                if ($Importe<=0) {
                    $this->status = "NOM215: Nomina.Deducciones.Deduccion.Importe no es mayor que cero.";
                    $this->codigo = "215 ".$this->status;
                    return false;
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
            if ($hay_2) { // Si hubo concepto deducion impuesto
                if ((double)$TotalImpuestosRetenidos!=$t_impu) {
                    $this->status = "NOM211: El valor en el atributo Nomina.Deducciones..TotalImpuestosRetenidos no es igual a la suma de los atributos Importe de las deducciones que tienen expresada la clave 002 en el atributo TipoDeduccion.";
                    $this->codigo = "211 ".$this->status;
                    return false;
                }
            } else { // NO hubo tipo impuesto
                if ($TotalImpuestosRetenidos!=null) {
                    $this->status = "NOM212: Nomina.Percepciones.JubilacionPensionRetiro.TotalImpuestosRetenidos no debe existir ya que no existen deducciones con clave 002 en el atributo.";
                    $this->codigo = "212 ".$this->status;
                    return false;
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
                $this->status = "NOM206: El nodo Incapacidades debe existir y la suma de los campos ImporteMonetario debe ser igual a la suma de los valores ImporteGravado e ImporteExento de la percepción, Ya que la clave expresada en el atributo TipoPercepcion es 014.";
                $this->codigo = "206 ".$this->status;
                return false;
            }
            if ($hay_6) { // Hay el concepto Incapacidad en Deducciones
                $this->status = "NOM214: Debe existir el elemento Incapacidades y el atributo Deduccion:Importe debe ser igual a la suma de los nodos Incapacidad:ImporteMonetario.  Ya que la clave expresada en Nomina.Deducciones.Deduccion.TipoDeduccion es 006";
                $this->codigo = "214 ".$this->status;
                return false;
            }
        } else { // SI hay incapacidades
            if (!$hay_14 && !$hay_6) { // NO hay Incapacidad en Percepciones ni deducciones
                $this->status = "NOM214: NO debe existir el elemento Incapacidades ya que la clave expresada en Nomina.Deducciones.Deduccion.TipoDeduccion NO es 006 NI 014";
                $this->codigo = "214 ".$this->status;
                return false;
            }
            $suma=0;
            foreach ($Incapacidades as $Incapacidad) {
                $TipoIncapacidad = $Incapacidad->getAttribute("TipoIncapacidad");
                $ok = $this->Checa_Catalogo("c_TipoIncapacidad", $TipoIncapacidad);
                if (!$ok) {
                    $this->status = "NOM223: Nomina.OtrosPagos.OtroPago.CompensacionSaldosAFavor.TipoIncapacidad no es una clave del catálogo de c_TIpoIncapacidad.";
                    $this->codigo = "223 ".$this->status;
                    return false;
                }
                $ImporteMonetario = $Incapacidad->getAttribute("ImporteMonetario");
                $suma += (double)$ImporteMonetario;
            }
            if ($suma!=$t_incapacidad) { 
                $this->status = "NOM207: La suma de los campos ImporteMonetario debe ser igual a la suma de los valores ImporteGravado e ImporteExento de la percepción, Ya que la clave expresada en el atributo TipoPercepcion es 014.";
                $this->codigo = "207 ".$this->status;
                return false;
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
                    $this->status = "NOM216: Nomina.OtrosPagos.OtroPago.TipoOtroPago debe no es una clave del catálogo de c_TipoOtroPago ";
                    $this->codigo = "216 ".$this->status;
                    return false;
                }
                $Importe= (double)$OtroPago->getAttribute("Importe");
                if ($Importe<=0) {
                    $this->status = "NOM219: Nomina.OtrosPagos.OtroPago.Importe no es mayor que cero";
                    $this->codigo = "219 ".$this->status;
                    return false;
                }
                $l_SubsidioAlEmpleo = $OtroPago->getElementsByTagName("SubsidioAlEmpleo");
                $largo = $l_SubsidioAlEmpleo->length;
                if ($largo==0) { // NO Hay NODO SubsidioAlEmpleo
                    if ($TipoOtroPago=="002") {
                        $this->status = "NOM218: El nodo SubsidioAlEmpleo. debe existir, ya que el valor de Nomina.OtrosPagos.OtroPago.TipoOtroPago es 002";
                        $this->codigo = "218 ".$this->status;
                        return false;
                    }
                } else { // SI Hay NODO SubsidioAlEmpleo
                    if ($TipoOtroPago!="002") {
                        $this->status = "NOM218: El nodo SubsidioAlEmpleo NO debe existir, ya que el valor de Nomina.OtrosPagos.OtroPago.TipoOtroPago NO es 002";
                        $this->codigo = "218 ".$this->status;
                        return false;
                    }
                    $SubsidioAlEmpleo = $l_SubsidioAlEmpleo->item(0);
                    $SubsidioCausado = (double)$SubsidioAlEmpleo->getAttribute("SubsidioCausado");
                    if ($SubsidioCausado<$Importe) {
                        $this->status = "NOM220: El nodo SubsidioAlEmpleo. debe existir, ya que el valor de Nomina.OtrosPagos.OtroPago.TipoOtroPago es 002.";
                        $this->codigo = "220 ".$this->status;
                        return false;
                    }
                }
                $l_CompensacionSaldosAFavor = $OtroPago->getElementsByTagName("CompensacionSaldosAFavor");
                $largo = $l_CompensacionSaldosAFavor->length;
                if ($largo==0) { // NO Hay NODO CompensacionSaldosAFavor
                    if ($TipoOtroPago=="001") {
                        $this->status = "NOM217: El nodo CompensacionSaldosAFavor. debe existir, ya que el valor de Nomina.OtrosPagos.OtroPago.TipoOtroPago es 004";
                        $this->codigo = "217 ".$this->status;
                        return false;
                    }
                } else { // SI Hay NODO CompensacionSaldosAFavor
                    if ($TipoOtroPago!="001") {
                        $this->status = "NOM217: El nodo CompensacionSaldosAFavor. NO debe existir, ya que el valor de Nomina.OtrosPagos.OtroPago.TipoOtroPago NO es 004";
                        $this->codigo = "217 ".$this->status;
                        return false;
                    }
                    $CompensacionSaldosAFavor = $l_CompensacionSaldosAFavor->item(0);
                    $SaldoAFavor = (double)$CompensacionSaldosAFavor->getAttribute("SaldoAFavor");
                    $RemanenteSalFav = (double)$CompensacionSaldosAFavor->getAttribute("RemanenteSalFav");
                    if ($SaldoAFavor!=$Importe+$RemanenteSalFav) {
                        $this->status = "NOM221 : Nomina.OtrosPagos.OtroPago.CompensacionSaldosAFavor.SaldoAFavor no es mayor o igual que el valor del atributo CompensacionSaldosAFavor:RemanenteSalFav.";
                        $this->codigo = "221 ".$this->status;
                        return false;
                    }
                    $Ano = $CompensacionSaldosAFavor->getAttribute(utf8_encode("Año"));
                    if ((int)$Ano>(int)date("Y")) {
                        $this->status = "NOM222: Nomina.OtrosPagos.OtroPago.CompensacionSaldosAFavor.Año  no es menor que el año en curso.";
                        $this->codigo = "222 ".$this->status;
                        return false;
                    }
                }
            }
        }
        // }}}
        $this->status = "NOM000: Validacion de semantica nomina 1.2 correcta";
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
    // {{{ lee_l_rfc
    private function lee_l_rfc($rfc) {
        $l = $this->conn->qstr($rfc);
        $qry = "select * from pac_l_rfc where rfc_rfc = $l";
        $row= $this->conn->GetRow($qry);
        return $row;
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
