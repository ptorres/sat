<?php
/****************************************************************************
 * semantica_cce11   Valida la semantica de los complementos de comercio    *
 *                   exterior version 1.1                                   *
 *                                                                          *
 *      14/feb/2017  Codigos de error en base a matriz oficial del SAT      *
 ****************************************************************************/
error_reporting(E_ALL);
class Cce11 {
    var $xml_cfd;
    var $con;
    var $codigo;
    var $status;
    var $cuenta = 1;
    public function valida($xml_cfd,$conn) {
        // {{{ valida : semantica_cce Version 1.1
        $ok = true;
        $this->xml_cfd = $xml_cfd;
        $this->conn = $conn;
        $this->status = "Inicia Validacion de semantica cce 1.1";
        $this->codigo = "0 ".$this->status;
        $Comprobante = $this->xml_cfd->getElementsByTagName('Comprobante')->item(0);
        $version = $Comprobante->getAttribute("version");
        if ($version==null) {
            $version = $Comprobante->getAttribute("Version");
        }
        if ($version != "3.3" && $version != "3.2") {
                $this->status = "CCE101 El atributo cfdi:Comprobante:version no tiene un valor valido.";
                $this->codigo = "101 ".$this->status;
                return false;
        }
        $cce = $Comprobante->getElementsByTagName('ComercioExterior')->item(0);
        $Complemento = $Comprobante->getElementsByTagName('Complemento')->item(0);
        $TipoOperacion = $cce->getAttribute("TipoOperacion");
        $MotivoTraslado = $cce->getAttribute("MotivoTraslado");
        $Propietario = $cce->getElementsByTagName('Propietario');
        $ClaveDePedimento = $cce->getAttribute("ClaveDePedimento");
        $CertificadoOrigen = $cce->getAttribute("CertificadoOrigen");
        $NumCertificadoOrigen = $cce->getAttribute("NumCertificadoOrigen");
        $NumExportadorConfiable = $cce->getAttribute("NumExportadorConfiable");
        $Incoterm = $cce->getAttribute("Incoterm");
        $Subdivision = $cce->getAttribute("Subdivision");
        $TipoCambioUSD = $cce->getAttribute("TipoCambioUSD");
        $TotalUSD = $cce->getAttribute("TotalUSD");
        $Propietarios = $cce->getElementsByTagName('Propietario');
        $Mercancias = $cce->getElementsByTagName('Mercancia');
        $nb_Mercancias = $Mercancias->length;
        $cce_Emisores = $cce->getElementsByTagName('Emisor');
        $cce_Receptores = $cce->getElementsByTagName('Receptor');
        $cce_pais_receptor = "";
        if ($cce_Receptores->length > 0) {
            $cce_Receptor = $cce->getElementsByTagName('Receptor')->item(0);
            $NumRegIdTrib = $cce_Receptor->getAttribute("NumRegIdTrib");
            $cce_Domicilios_Receptor = $cce_Receptor->getElementsByTagName('Domicilio');
            if ($cce_Domicilios_Receptor->length>0) {
                $cce_Domicilio_Receptor = $cce_Domicilios_Receptor->item(0);
                $cce_pais_receptor = $cce_Domicilio_Receptor->getAttribute("Pais");
            }
        }
        $Destinatarios = $cce->getElementsByTagName('Destinatario');
        $cce_pais_destinatario = "MEX";
        if ($Destinatarios->length>0) {
            $Domicilios_Destinatario = $Destinatarios->item(0)->getElementsByTagName('Domicilio');
            if ($Domicilios_Destinatario->length>0) {
                $cce_pais_destinatario = $Domicilios_Destinatario->item(0)->getAttribute("Pais");
            }
        }
        // }}}
        if ($version == "3.2") {
            // {{{ Valida Comprobante 3.2
            $fecha = $Comprobante->getAttribute("fecha");
            $regex = "(20[1-9][0-9])-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])T(([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9])";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$fecha);
            if (!$ok) {
                $this->status = "CCE102 El atributo cfdi:Comprobante:fecha no cumple con el patrón requerido.";
                $this->codigo = "102 ".$this->status;
                return false;
            }
            $Conceptos = $Comprobante->getElementsByTagName('Concepto');
            $nb_Conceptos = $Conceptos->length;
            $suma=0.0;
            for ($i=0; $i<$nb_Conceptos; $i++) {
                $Concepto = $Conceptos->item($i);
                $importe = (double)$Concepto->getAttribute("importe");
                $suma += $importe;
            }
            $subTotal = round((double)$Comprobante->getAttribute("subTotal"),2);
            if (abs($subTotal - $suma) > 0.001) {
                $this->status = "CCE103 El atributo cfdi:Comprobante:subtotal no coincide con la suma de los atributos importe de los nodos Concepto.";
                $this->codigo = "103 ".$this->status;
                return false;
            }
            $Moneda = $Comprobante->getAttribute("Moneda");
            if ($Moneda=="") {
                $this->status = "CCE104 El atributo cfdi:Comprobante:Moneda se debe registrar.";
                $this->codigo = "104 ".$this->status;
                return false;
            }
            $ok = $this->Checa_Catalogo("c_Moneda", $Moneda);
            if (!$ok) {
                $this->status = "CCE105 El atributo cfdi:Comprobante:Moneda no contiene un valor del catálogo catCFDI:c_Moneda.";
                $this->codigo = "105 ".$this->status;
                return false;
            }
            $TipoCambio = $Comprobante->getAttribute("TipoCambio");
            if ($Moneda=="MXN" && $TipoCambio!=="1") {
                $this->status = 'CCE106 El atributo TipoCambio no tiene el valor "1" y la moneda indicada es MXN.';
                $this->codigo = "106 ".$this->status;
                return false;
            }
	        if ($TipoCambio=="" && $Moneda!="MXN" && $Moneda!="XXX") {
                $this->status = "CCE107 El atributo cfdi:Comprobante:TipoCambio se debe registrar cuando el atributo cfdi:Comprobante:Moneda tiene un valor distinto de MXN y XXX. ";
                $this->codigo = "107 ".$this->status;
                return false;
            }
            if ($TipoCambio!="" && $Moneda=="XXX") {
                $this->status = "CCE108 El atributo cfdi:Comprobante:TipoCambio no se debe registrar cuando el atributo cfdi:Comprobante:Moneda tiene el valor XXX.";
                $this->codigo = "108 ".$this->status;
                return false;
            }
            $valor = (double)$TipoCambio;
	        if ($TipoCambio!="") {
               $regex = "[0-9]{1,14}(\.([0-9]{1,6}))?";
               $aux = "/^$regex$/A";
               $ok = preg_match($aux,$TipoCambio);
               if (!$ok) {
                   $this->status = "CCE109 El atributo cfdi:Comprobante:TipoCambio no cumple con el patrón requerido.";
                   $this->codigo = "109 ".$this->status;
                   return false;
               }
            }
            $tipoDeComprobante = $Comprobante->getAttribute("tipoDeComprobante");
            if ($tipoDeComprobante=="traslado" ||
                $tipoDeComprobante=="ingreso" ||
                $tipoDeComprobante=="egreso") {
                    // ok
            } else {
                $this->status = "CCE110 El atributo cfdi:Comprobante:tipoDeComprobante no cumple con alguno de los valores permitidos.";
                $this->codigo = "110 ".$this->status;
                return false;
            }
            if ($tipoDeComprobante=="traslado") {
               if ($MotivoTraslado=="") {
                   $this->status = 'CCE111 El atributo MotivoTraslado debe registrarse cuando cfdi:Comprobante:tipoDeComprobante tiene el valor "traslado".';
                   $this->codigo = "111 ".$this->status;
                   return false;
               }
               if ($MotivoTraslado=="05" && $Propietario->length==0) {
                   $this->status = 'CCE112 El nodo Propietario se debe registrar cuando cfdi:Comprobante:tipoDeComprobante tiene el valor "traslado" y MotivoTraslado tiene la clave "05".';
                   $this->codigo = "112 ".$this->status;
                   return false;
               }
               if ($MotivoTraslado!="05" && $Propietario->length!=0) {
                   $this->status = 'CCE114 El nodo Propietario no debe existir cuando cfdi:Comprobante:tipoDeComprobante es distinto de "traslado" y MotivoTraslado tiene una clave distinta de "05".';
                   $this->codigo = "114 ".$this->status;
                   return false;
               }
            } else { // NO es traslado
               if ($MotivoTraslado!=="") {
                   $this->status = 'CCE113 El atributo MotivoTraslado no debe existir cuando cfdi:Comprobante:tipoDeComprobante es distinto de "traslado".';
                   $this->codigo = "113 ".$this->status;
                   return false;
               }
               if ($Propietario->length!=0) {
                   $this->status = 'CCE114 El nodo Propietario no debe existir cuando cfdi:Comprobante:tipoDeComprobante es distinto de "traslado" y MotivoTraslado tiene una clave distinta de "05".';
                   $this->codigo = "114 ".$this->status;
                   return false;
               }
	        }
            $descuento = (double)$Comprobante->getAttribute("descuento");
            $total = (double)$Comprobante->getAttribute("total");
            $Impuestos = $Comprobante->getElementsByTagName('Impuestos')->item(0);
            $totalImpuestosTrasladados = (double)$Impuestos->getAttribute("totalImpuestosTrasladados");
            $totalImpuestosRetenidos = (double)$Impuestos->getAttribute("totalImpuestosRetenidos");
            $suma = $subTotal - $descuento + $totalImpuestosTrasladados - $totalImpuestosRetenidos;
            if (abs($suma - $total)>0.001) {
                $this->status = 'CCE115 El atributo cfdi:Comprobante:total no coincide con la suma del cdi:Comprobante:subTotal, menos el cfdi:Comprobante:descuento, más cfdi:Comprobante:Impuestos:totalImpuestosTrasladados menos cfdi:Comprobante:Impuestos:totalImpuestosRetenidos.';
                $this->codigo = "115 ".$this->status;
                return false;
            }
            $CodigoPostal = $Comprobante->getAttribute("LugarExpedicion");
            $regex = "([0-9]{5})";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$CodigoPostal);
            if ($ok) $ok = $this->Checa_Catalogo("c_CP", $CodigoPostal);
            if (!$ok) {
                $this->status = 'CCE116 El atributo cfdi:Comprobante:LugarExpedicion no cumple con alguno de los valores permitidos.';
                $this->codigo = "116 ".$this->status;
                return false;
            }
            // }}}
            // {{{ Emisor
            $Emisores = $Comprobante->getElementsByTagName('Emisor');
            foreach ($Emisores as $nodo) {
                if ($nodo->parentNode->nodeName=="cfdi:Comprobante") $Emisor = $nodo;
            }
            $rfcEmisor = $Emisor->getAttribute("rfc");
            $nombre = $Emisor->getAttribute("nombre");
            if ($nombre=="") {
                $this->status = "CCE117 El atributo cfdi:Comprobante:Emisor:Nombre se debe registrar.";
                $this->codigo = "117 ".$this->status;
                return false;
            }
            // }}}
            // {{{ Domicilios Emisor
            $domis = array();
            $lista = $Emisor->getElementsByTagName('DomicilioFiscal');
            foreach ($lista as $nodo) {
                $domis[]= $nodo;
            }
            $lista = $Emisor->getElementsByTagName('ExpedidoEn');
            foreach ($lista as $nodo) {
                $domis[]= $nodo;
            }
            foreach ($domis as $nodo) {
                $nombre = $nodo->nodeName;
                $pais = $nodo->getAttribute("pais");
                if ($pais !== "MEX") {
                    if ($nombre=="cfdi:DomicilioFiscal") {
                        $this->status = 'CCE118 El atributo cfdi:Comprobante:Emisor:DomicilioFiscal:pais debe tener el valor "MEX".';
                        $this->codigo = "118 ".$this->status;
                    } else {
                        $this->status = 'CCE119 El atributo cfdi:Comprobante:Emisor:ExpedidoEn:pais debe tener el valor "MEX".';
                        $this->codigo = "119 ".$this->status;
                    }
                    return false;
                }
                $estado = $nodo->getAttribute("estado");
                $ok = $this->Checa_Catalogo("c_Estado", $estado, $pais);
                if (!$ok) {
                    if ($nombre=="cfdi:DomicilioFiscal") {
                        $this->status = 'CCE120 El atributo cfdi:Comprobante:Emisor:DomicilioFiscal:estado debe contener una clave del catálogo catCFDI:c_Estado donde la columna c_Pais tenga el valor "MEX".';
                        $this->codigo = "120 ".$this->status;
                    } else {
                        $this->status = 'CCE121 El atributo cfdi:Comprobante:Emisor:ExpedidoEn:estado debe contener una clave del catálogo catCFDI:c_Estado donde la columna c_Pais tenga el valor "MEX".';
                        $this->codigo = "121 ".$this->status;
                    }
                    return false;
                }
                $municipio = $nodo->getAttribute("municipio");
                $ok = $this->Checa_Catalogo("c_Municipio", $municipio, $estado);
                if (!$ok) {
                    if ($nombre=="cfdi:DomicilioFiscal") {
                        $this->status = 'CCE122 El atributo cfdi:Comprobante:Emisor:DomicilioFiscal:municipio debe contener una clave del catálogo de catCFDI:c_Municipio donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado si el nodo es generado.';
                        $this->codigo = "122 ".$this->status;
                    } else {
                        $this->status = 'CCE123 El atributo cfdi:Comprobante:Emisor:ExpedidoEn:municipio debe contener una clave del catálogo de catCFDI:c_Municipio donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado si el nodo es generado.';
                        $this->codigo = "123 ".$this->status;
                    }
                    return false;
                }
                $localidad = $nodo->getAttribute("localidad");
                if ($localidad != "") {
                    $ok = $this->Checa_Catalogo("c_Localidad", $localidad, $estado);
                    if (!$ok) {
                        if ($nombre=="cfdi:DomicilioFiscal") {
                            $this->status = 'CCE124 El atributo cfdi:Comprobante:Emisor:DomicilioFiscal:localidad debe contener una clave del catálogo de catCFDI:c_Localidad, donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado si el nodo es generado.';
                            $this->codigo = "124 ".$this->status;
                        } else {
                            $this->status = 'CCE125 El atributo cfdi:Comprobante:Emisor:ExpedidoEn:localidad debe contener una clave del catálogo de catCFDI:c_Localidad, donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado si el nodo es generado.';
                            $this->codigo = "125 ".$this->status;
                        }
                        return false;
                    }
                }
                $colonia = trim($nodo->getAttribute("colonia"));
                $codigoPostal = $nodo->getAttribute("codigoPostal");
//echo "codigopostal=$codigoPostal";
                if (is_numeric($colonia) and strlen($colonia)==4) {
                    $ok = $this->Checa_Catalogo("c_Colonia", $colonia, $codigoPostal);
                    if (!$ok) {
                        if ($nombre=="cfdi:DomicilioFiscal") {
                            $this->status = 'CCE126 El atributo cfdi:Comprobante:Emisor:DomicilioFiscal:colonia debe contener una clave del catálogo de catCFDI:c_Colonia, donde la columna c_CodigoPostal debe ser igual a la clave registrada en el atributo codigoPostal si el nodo es generado.';
                            $this->codigo = "126 ".$this->status;
                        } else {
                            $this->status = 'CCE127 El atributo cfdi:Comprobante:Emisor:ExpedidoEn:colonia debe contener una clave del catálogo de catCFDI:c_Colonia, donde la columna c_CodigoPostal debe ser igual a la clave registrada en el atributo codigoPostal si el nodo es generado.';
                            $this->codigo = "127 ".$this->status;
                        }
                        return false;
                    }
                }
                $c_CP = $this->Obten_Catalogo("c_CP", $codigoPostal);
                if (sizeof($c_CP)==0) {
                    if ($nombre=="cfdi:DomicilioFiscal") {
                        $this->status = 'CCE128 El atributo cfdi:Comprobante:Emisor:DomicilioFiscal:codigoPostal debe contener una clave del catálogo de catCFDI:c_CodigoPostal, donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado, la columna clave de c_Municipio debe ser igual a la clave registrada en el atributo municipio, y si existe el atributo de localidad, la columna clave de c_Localidad debe ser igual a la clave registrada en el atributo localidad si el nodo es generado.';
                        $this->codigo = "128 ".$this->status;
                    } else {
                        $this->status = "CCE129 El atributo cfdi:Comprobante:Emisor:ExpedidoEn:codigoPostal debe contener una clave del catálogo de catCFDI:c_CodigoPostal, donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado, la columna clave de c_Municipio debe ser igual a la clave registrada en el atributo municipio, y si existe el atributo de localidad, la columna clave de c_Localidad debe ser igual a la clave registrada en el atributo localidad si el nodo es generado.";
                        $this->codigo = "129 ".$this->status;
                    }
                    return false;
                }
                // var_dump($c_CP);
                $c_Estado = trim($c_CP["c_estado"]);
                $c_Municipio = trim($c_CP["c_municipio"]);
                $c_Localidad = trim($c_CP["c_localidad"]);
                if ($c_Estado!=$estado ||
                    $c_Municipio!=$municipio ||
                    ($localidad!="" && $c_Localidad!="" && $c_Localidad!==$localidad)) {
                    if ($nombre=="cfdi:DomicilioFiscal") {
                        $this->status = 'CCE128 El atributo cfdi:Comprobante:Emisor:DomicilioFiscal:codigoPostal debe contener una clave del catálogo de catCFDI:c_CodigoPostal, donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado, la columna clave de c_Municipio debe ser igual a la clave registrada en el atributo municipio, y si existe el atributo de localidad, la columna clave de c_Localidad debe ser igual a la clave registrada en el atributo localidad si el nodo es generado.';
                        $this->codigo = "128 ".$this->status;
                    } else {
                        $this->status = "CCE129 El atributo cfdi:Comprobante:Emisor:ExpedidoEn:codigoPostal debe contener una clave del catálogo de catCFDI:c_CodigoPostal, donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado, la columna clave de c_Municipio debe ser igual a la clave registrada en el atributo municipio, y si existe el atributo de localidad, la columna clave de c_Localidad debe ser igual a la clave registrada en el atributo localidad si el nodo es generado.";
                        $this->codigo = "129 ".$this->status;
                    }
                    return false;
                } // no coincide estado o municipio o localidad
            } // Para cada Domicilio
            // }}} Domicilios Emisor
            // {{{ Regimen Fiscal
            $lista = $Emisor->getElementsByTagName('RegimenFiscal');
            if ($lista->length != 1) {
                $this->status = "CCE130 El nodo Comprobante.Emisor.RegimenFiscal debe tener solo un elemento hijo Regimen. ";
                $this->codigo = "130 ".$this->status;
                return false;
            }
            $RegimenFiscal = $lista->item(0);
            $Regimen=$RegimenFiscal->getAttribute("Regimen");
            $cata = (strlen($rfcEmisor)==13) ? "c_RegimenFisica" : "c_RegimenMoral";
            $ok = $this->Checa_Catalogo($cata, $Regimen);
            if (!$ok) {
                $this->status = "CCE131 El atributo cfdi:Comprobante:Emisor:RegistroFiscal:Regimen no cumple con alguno de los valores permitidos para el tipo de persona del emisor.";
                $this->codigo = "131 ".$this->status;
                return false;
            }
            // }}} Domicilios Emisor
            // {{{ Receptor
            $Receptores = $Comprobante->getElementsByTagName('Receptor');
            foreach ($Receptores as $nodo) {
                if ($nodo->parentNode->nodeName=="cfdi:Comprobante") $Receptor = $nodo;
            }
            $rfcReceptor = $Receptor->getAttribute("rfc");
            if ($tipoDeComprobante=="traslado" && $MotivoTraslado=="02") {
                // Es traslado puede ser generico
            } else {
                // En los demas casos no puede ser generico
                if ($rfcReceptor!="XEXX010101000") {
                    $this->status = 'CCE132 El atributo cfdi:Comprobante:Receptor:rfc no tiene el valor "XEXX010101000" y el tipoDeComprobante tiene un valor distinto de "traslado" y MotivoTraslado un valor distinto de "02".';
                    $this->codigo = "132 ".$this->status;
                    return false;
                }
            }
            if ($rfcReceptor!="XEXX010101000") {
                $row= $this->lee_l_rfc($rfcReceptor);
                if (sizeof($row)==0) {
                    $this->status = 'CCE133 El atributo cfdi:Comprobante:Receptor:rfc debe tener un RFC válido dentro de la lista de RFC'."'".'s o el valor "XEXX010101000" cuando el tipoDeComprobante es "traslado" y MotivoTraslado es "02".';
                    $this->codigo = "133 ".$this->status;
                    return false;
                } // No existe RFC
            } // No es generico debe de existir
            $nombre = $Receptor->getAttribute("nombre");
            if (strlen($nombre)<=0) {
                $this->status = "CCE134 El atributo cfdi:Comprobante:Receptor:nombre se debe registrar.";
                $this->codigo = "134 ".$this->status;
                return false;
            }
            $Domicilios = $Receptor->getElementsByTagName('Domicilio');
            if ($Domicilios->length!=1) {
                $this->status = "CCE135 El nodo cfdi:Comprobante:Receptor:Domicilio se debe registrar.";
                $this->codigo = "135 ".$this->status;
                return false;
            }
            $nodorece=$Domicilios->item(0);
            $pais_receptor = $nodorece->getAttribute("pais");
            $estado = $nodorece->getAttribute("estado");
            $municipio = $nodorece->getAttribute("municipio");
            $localidad = $nodorece->getAttribute("localidad");
            $colonia = $nodorece->getAttribute("colonia");
            $codigoPostal = $nodorece->getAttribute("codigoPostal");
            if ($tipoDeComprobante=="traslado" && $MotivoTraslado=="02" && $pais_receptor=="MEX") {
                $ok = $this->Checa_Catalogo("c_Estado", $estado, $pais_receptor);
                if (!$ok) {
                    $this->status = 'CCE136 El atributo cfdi:Comprobante:Receptor:Domicilio:estado debe contener una clave del catálogo catCFDI:c_Estado donde la columna c_Pais tenga el valor "MEX" si el atributo pais tiene el valor "MEX", el tipoDeComprobante es "traslado" y MotivoTraslado tiene el valor "02".';
                    $this->codigo = "136 ".$this->status;
                    return false;
                }
                $ok = $this->Checa_Catalogo("c_Municipio", $municipio, $estado);
                if (!$ok) {
                    $this->status = 'CCE137 El atributo cfdi:Comprobante:Receptor:Domicilio:municipio debe contener una clave del catálogo de catCFDI:c_Municipio donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado si el atributo pais tiene el valor "MEX", el tipoDeComprobante es "traslado" y MotivoTraslado tiene el valor "02".';
                    $this->codigo = "137 ".$this->status;
                    return false;
                 }
                 if ($localidad!="") {
                     $ok = $this->Checa_Catalogo("c_Localidad", $localidad, $estado);
                     if (!$ok) {
                         $this->status = 'CCE138 El atributo cfdi:Comprobante:Receptor:Domicilio:localidad debe contener una clave del catálogo de catCFDI:c_Localidad, donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado si el atributo pais tiene el valor "MEX", el tipoDeComprobante es "traslado" y MotivoTraslado tiene el valor "02".';
                         $this->codigo = "138 ".$this->status;
                         return false;
                    }
                 }
                 $c_CP = $this->Obten_Catalogo("c_CP", $codigoPostal);
                 if (sizeof($c_CP)==0) {
                     $this->status = 'CCE218 Error no clasificado, No existe codigo postal en c_CodigoPostal';
                     $this->codigo = "218 ".$this->status;
                     return false;
                 }
                 $ok = $this->Checa_Catalogo("c_Colonia", $colonia, $codigoPostal);
                 if (!$ok) {
                     $this->status = 'CCE139 El atributo cfdi:Comprobante:Receptor:Domicilio:colonia debe contener una clave del catálogo de catCFDI:c_Colonia, donde la columna c_CodigoPostal debe ser igual a la clave registrada en el atributo codigoPostal si el atributo pais tiene el valor "MEX", el tipoDeComprobante es "traslado" y MotivoTraslado tiene el valor "02".';
                     $this->codigo = "139 ".$this->status;
                     return false;
                 }
                 $c_Estado = trim($c_CP["c_estado"]);
                 $c_Municipio = trim($c_CP["c_municipio"]);
                 $c_Localidad = trim($c_CP["c_localidad"]);
                 if ($c_Estado!==$estado ||
                     $c_Municipio!==$municipio ||
                     ($localidad!="" && $c_Localidad!==$localidad)) {
                       $this->status = 'CCE140 El atributo cfdi:Comprobante:Receptor:Domicilio:codigoPostal debe contener una clave del catálogo de catCFDI:c_CodigoPostal, donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo estado, la columna clave de c_Municipio debe ser igual a la clave registrada en el atributo municipio, y si existe el atributo de localidad, la columna clave de c_Localidad debe ser igual a la clave registrada en el atributo localidad si el atributo pais tiene el valor "MEX", el tipoDeComprobante es "traslado" y MotivoTraslado tiene el valor "02".';
                       $this->codigo = "140 ".$this->status;
                       return false;
                 }
            } else {
                // No es traslado ni motivo 02
                 $ok = $this->Checa_Catalogo("c_Pais", $pais_receptor);
                 if (!$ok || $pais_receptor=="MEX") {
                       $this->status = 'CCE141 El atributo cfdi:Comprobante:Receptor:Domicilio:pais debe ser distinto de "MEX" y existir en el catálogo catCFDI:c_Pais si tipoDeComprobante es distinto de "traslado" o MotivoTraslado es distinto de "02".';
                        $this->codigo = "141 ".$this->status;
                        return false;
                 }
                 if ($pais_receptor=="ZZZ" || $this->Cuenta_Catalogo("c_Estado",$pais_receptor)==0) {
                       // ok
                 } else {
                      $ok = $this->Checa_Catalogo("c_Estado", $estado, $pais_receptor);
                      if (!$ok) {
                          $this->status = 'CCE142 El atributo cfdi:Comprobante:Receptor:Domicilio:pais debe contener una clave del catálogo catCFDI:c_Estado donde la columna c_Pais sea igual a la clave del pais registrada en el atributo pais del mismo nodo.';
                          $this->codigo = "142 ".$this->status;
                          return false;
                      }
                 } // pais = ZZZ
                 $c_Pais_receptor = $this->Obten_Catalogo("c_Pais", $pais_receptor);
                 $regex_cp = trim($c_Pais_receptor["regex_cp"]);
                 $regex_taxid = trim($c_Pais_receptor["regex_taxid"]);
                 if ($codigoPostal=="") {
                     $this->status = 'CCE143 El atributo cfdi:Comprobante:Receptor:Domicilio:codigoPostal se debe registrar cuando tipoDeComprobante es distinto de "traslado" o MotivoTraslado es distinto de "02" y el pais es distinto de "MEX".';
                     $this->codigo = "143 ".$this->status;
                     return false;
                 }
                 if ($regex_cp!="") { 
                     $aux = "/^$regex_cp$/A";
                     $ok = preg_match($aux,$codigoPostal);
                     if (!$ok) {
                         $this->status = 'CCE144 El atributo cfdi:Comprobante:Receptor:Domicilio:codigoPostal debe cumplir con el patrón especificado en el catálogo catCFDI:c_Pais cuando tipoDeComprobante es distinto de "traslado" o MotivoTraslado es distinto de "02" y el pais es distinto de "MEX".';
                         $this->codigo = "144 ".$this->status;
                         return false;
                     }
                 } // regex_cp
              } // traslado, moti = 2
        } // es 3.2
        // }}}
        if ($version == "3.3") {
            // {{{ Valida 3.3
            $Moneda = $Comprobante->getAttribute("Moneda");
            $TipoCambio = $Comprobante->getAttribute("TipoCambio");
            $TipoDeComprobante = $Comprobante->getAttribute("TipoDeComprobante");
            if ($TipoDeComprobante!="I" && 
                $TipoDeComprobante!="E" && 
                $TipoDeComprobante!="T") {
                  $this->status = 'CCE145 El atributo cfdi:Comprobante:TipoDeComprobante no cumple con alguno de los valores permitidos para este complemento.';
                  $this->codigo = "145 ".$this->status;
                  return false;
            }
            if ($TipoDeComprobante=="T" && $MotivoTraslado=="") {
                $this->status = 'CCE146 El atributo MotivoTraslado se debe registrar cuando el atributo cfdi:Comprobante:TipoDeComprobante tiene el valor "T".';
                $this->codigo = "146 ".$this->status;
                return false;
            }
            if ($TipoDeComprobante=="T" && $MotivoTraslado=="05") {
               if ($Propietarios->length==0) {
                   $this->status = 'CCE147 El nodo Propietario se debe registrar cuando el atributo cfdi:Comprobante:TipoDeComprobante tiene el valor "T" y MotivoTraslado tiene la clave "05".';
                   $this->codigo = "147 ".$this->status;
                   return false;
               }
            } else { // Ni t ni 05
               if ($Propietarios->length!=0) {
                   $this->status = 'CCE148 El nodo Propietario no se debe registrar cuando el atributo cfdi:Comprobante:TipoDeComprobante tiene un valor distinto de "T" y MotivoTraslado tiene una clave distinta de "05".';
                   $this->codigo = "148 ".$this->status;
                   return false;
               }
            } // T y 05
            $Emisor = $Comprobante->getElementsByTagName('Emisor')->item(0);
           $Nombre = $Emisor->getAttribute("Nombre");
           $rfcEmisor = $Emisor->getAttribute("rfc");
           if ($Nombre=="") {
               $this->status = 'CCE149 El atributo cfdi:Comprobante:Emisor:Nombre se debe registrar. ';
               $this->codigo = "149 ".$this->status;
               return false;
           }
           $Receptor = $Comprobante->getElementsByTagName('Receptor')->item(0);
           $rfcReceptor = $Receptor->getAttribute("Rfc");
           if ($TipoDeComprobante!="T" && $MotivoTraslado!="02") {
               if ($rfcReceptor!="XEXX010101000") {
                  $this->status = 'CCE150 El atributo cfd:Comprobante:Receptor:Rfc no tiene el valor "XEXX010101000" y el TipoDeComprobante tiene un valor distinto de "T" y MotivoTraslado un valor distinto de "02".';
                  $this->codigo = "150 ".$this->status;
                  return false;
               }
           }
           if ($rfcReceptor!="XEXX010101000") {
               $row= $this->lee_l_rfc($rfcReceptor);
               if (sizeof($row)==0) {
                   $this->status = 'CCE151 El atributo cfdi:Comprobante:Receptor:Rfc debe tener un RFC válido dentro de la lista de RFC'."'".'s o el valor "XEXX010101000" cuando el TipoDeComprobante es "T" y MotivoTraslado es "02".';
                   $this->codigo = "151 ".$this->status;
                   return false;
               }
           }
           $Nombre = $Receptor->getAttribute("Nombre");
           if ($Nombre=="") {
               $this->status = "CCE152 El atributo cfdi:Comprobante:Receptor:Nombre se debe registrar. ";
               $this->codigo = "152 ".$this->status;
               return false;
           }
           $Conceptos = $Comprobante->getElementsByTagName('Concepto');
           $nb_Conceptos = $Conceptos->length;
        }
        // }}}
        // {{{ Validaciones del complemento CCE 1.1
        $lista = $Comprobante->getElementsByTagName('ComercioExterior');
        if ($lista->length>1) {
            $this->status = "CCE153 El nodo cce11:ComercioExterior no puede registrarse mas de una vez.";
            $this->codigo = "153 ".$this->status;
            return false;
        }
        if ($cce->parentNode->nodeName!="cfdi:Complemento") {
            $this->status = "CCE154 El nodo cce11:ComercioExterior debe registrarse como un nodo hijo del nodo Complemento en el CFDI.";
            $this->codigo = "154 ".$this->status;
            return false;
        }
        foreach ($Complemento->childNodes as $node) {
            if ($node->nodeType == XML_ELEMENT_NODE) {
                if ($node->nodeName == "tfd:TimbreFiscalDigital" ||
                    $node->nodeName == "cce11:ComercioExterior" ||
                    $node->nodeName == "otros:Otros" ||
                    $node->nodeName == "leyenda:LeyendasFiscales" ||
                    $node->nodeName == "pago:Pagos" ||
                    $node->nodeName == "leyenda:RegistroFiscal") {
                } else {
                    $this->status = "CCE155 El nodo cce11:ComercioExterior solo puede coexistir con los complementos Timbre Fiscal Digital, otros derechos e impuestos, leyendas fiscales, recepción de pago, CFDI registro fiscal.";
                    $this->codigo = "155 ".$this->status;
                    return false;
                }
            }
        } // Buscar que complementos existen
        if ($version == "3.2" && $MotivoTraslado=="01") {
            $FolioFiscalOrig = $Comprobante->getAttribute("FolioFiscalOrig");
            if ($FolioFiscalOrig=="") {
                  $this->status = 'CCE156 El atributo cfdi:FolioFiscalOrig se debe registrar si el valor de cce11:ComercioExterior:MotivoTraslado es "01".';
                  $this->codigo = "156 ".$this->status;
                  return false;
            }
        } // 3.2 motivo traslado 01
        if ($version == "3.3" && $MotivoTraslado=="01") {
            $ok = false;
            $CfdiRelacionados = $Comprobante->getElementsByTagName('CfdiRelacionados');
            if ($CfdiRelacionados->length == 1) {
                $a = $CfdiRelacionados->item(0);
                $TipoRelacion = $a->getAttribute("TipoRelacion");
                if ($TipoRelacion=="05") {
                    $CfdiRelacionado = $a->getElementsByTagName('CfdiRelacionado');
                    foreach ($CfdiRelacionado as $b) {
                        $UUID = $b->getAttribute("UUID");
                        if ($UUID != "") $ok = true;
                    }
                }
            }
            if (!$ok) {
                $this->status = 'CCE157 El atributo cfdi:CfdiRelacionados:CfdiRelacionado:UUID se debe registrar si el valor de cce11:ComercioExterior:MotivoTraslado es "01" con el tipo de relación "05".';
                $this->codigo = "157 ".$this->status;
                return false;
            }
        } // 3.3 motivotraslado=01
        if ($TipoOperacion=="A") {
            if ($MotivoTraslado!="" || $ClaveDePedimento!="" || $CertificadoOrigen!="" ||
                $NumCertificadoOrigen!="" || $NumExportadorConfiable!="" ||
                $Incoterm!="" || $Subdivision!="" || $TipoCambioUSD!="" ||
                $TotalUSD!="" || $nb_Mercancias!=0) {
                $lista = "";
                if ($MotivoTraslado!="") $lista .= "MotivoTraslado,";
                if ($ClaveDePedimento!="") $lista .= "ClaveDePedimento,";
                if ($CertificadoOrigen!="") $lista .= "CertificadoOrigen,";
                if ($NumCertificadoOrigen!="") $lista .= "NumCertificadoOrigen,";
                if ($NumExportadorConfiable!="") $lista .= "NumExportadorConfiable,";
                if ($Incoterm!="") $lista .= "Incoterm,";
                if ($Subdivision!="") $lista .= "Subdivision,";
                if ($TipoCambioUSD!="") $lista .= "TipoCambioUSD,";
                if ($TotalUSD!="") $lista .= "TotalUSD,";
                if ($nb_Mercancias!=0) $lista .= "Mercancias";
                $this->status = 'CCE158 El atributo '.$lista.' no debe existir si el valor de cce11:ComercioExterior:TipoOperacion es "A".';
                $this->codigo = "158 ".$this->status;
                return false;
            }
        }
        if ($TipoOperacion=="1" || $TipoOperacion=="2") {
            if ($ClaveDePedimento=="" || $CertificadoOrigen=="" ||
                $Incoterm=="" || $Subdivision=="" || $TipoCambioUSD=="" ||
                $TotalUSD=="" || $nb_Mercancias==0) {
                $lista = "";
                if ($ClaveDePedimento=="") $lista .= "ClaveDePedimento,";
                if ($CertificadoOrigen=="") $lista .= "CertificadoOrigen,";
                if ($Incoterm=="") $lista .= "Incoterm,";
                if ($Subdivision=="") $lista .= "Subdivision,";
                if ($TipoCambioUSD=="") $lista .= "TipoCambioUSD,";
                if ($TotalUSD=="") $lista .= "TotalUSD,";
                if ($nb_Mercancias==0) $lista .= "Mercancias";
                $this->status = 'CCE159 El atributo '.$lista.' debe registrarse si la clave de cce11:ComercioExterior:TipoOperacion registrada es "1" ó "2".';
                $this->codigo = "159 ".$this->status;
                return false;
             }
        }
        if ($CertificadoOrigen=="0" && $NumCertificadoOrigen!="") {
            $this->status = 'CCE160 El atributo cce11:ComercioExterior:NumCertificadoOrigen no se debe registrar si el valor de cce11:ComercioExterior:CertificadoOrigen es "0".';
            $this->codigo = "160 ".$this->status;
            return false;
        }
        if ($NumExportadorConfiable!="") {
            if ($cce_pais_receptor=="") $cce_pais_receptor = $pais_receptor;
            if ($cce_pais_receptor=="") $cce_pais_receptor = "MEX";
            $c_paisReceptor = $this->Obten_Catalogo("c_Pais", $cce_pais_receptor);
            $agrupacionReceptor = trim($c_paisReceptor["agrupaciones"]);
            $c_paisDestinatario = $this->Obten_Catalogo("c_Pais", $cce_pais_destinatario);
            $agrupacionDestinatario = trim($c_paisDestinatario["agrupaciones"]);
            if ($agrupacionReceptor!="Union Europea" &&
                $agrupacionDestinatario!="Union Europea") {
                $this->status = 'CCE161 El atributo cce11:ComercioExterior:NumExportadorConfiable no se debe registrar si la clave de país del receptor o del destinatario no corresponde a un país del catálogo catCFDI:c_Pais donde la columna Agrupación tenga el valor Unión Europea.';
                $this->codigo = "161 ".$this->status;
                return false;
            }
        }
        $suma=0.0;
        for ($i=0; $i<$nb_Mercancias; $i++) {
            $Mercancia = $Mercancias->item($i);
            $ValorDolares = (double)$Mercancia->getAttribute("ValorDolares");
            $suma += $ValorDolares;
        }
        if (abs($TotalUSD - $suma) > 0.001) {
            $this->status = "CCE162 El atributo cce11:ComercioExterior:TotalUSD no coincide con la suma de ValorDolares de las mercancías.";
            $this->codigo = "162 ".$this->status;
            return false;
        }
        if ($this->cantidad_decimales($TotalUSD) != 2) {
            $this->status = "CCE163 El atributo cce11:ComercioExterior:TotalUSD debe registrarse con dos decimales.";
            $this->codigo = "163 ".$this->status;
            return false;
        }
        // }}}
        // {{{ Emisor
        if ($cce_Emisores->length == 0) {
            // Emisor es obligatorio en 3.3
            if ($version == "3.3") {
                $this->status = "CCE167 El nodo cce11:ComercioExterior:Emisor:Domicilio debe registrarse si la versión de CFDI es 3.3.";
                $this->codigo = "167 ".$this->status;
                return false;
            }
        } else {
            // Si hay Emisor se valida
            $cce_Emisor = $cce_Emisores->item(0);
            $curp = $cce_Emisor->getAttribute("Curp");
            if (strlen($rfcEmisor)==12 && $curp != "") {
                $this->status = 'CCE164 El atributo cce11:ComercioExterior:Emisor:Curp no se debe registrar si el atributo Rfc del nodo cfdi:Comprobante:Emisor es de longitud 12.';
                $this->codigo = "164 ".$this->status;
                return false;
            }
            if (strlen($rfcEmisor)==13 && $curp == "") {
                $this->status = 'CCE165 El atributo cce11:ComercioExterior:Emisor:Curp se debe registrar si el atributo Rfc del nodo cfdi:Comprobante:Emisor es de longitud 13.';
                $this->codigo = "165 ".$this->status;
                return false;
            }
            $Domicilios = $cce_Emisor->getElementsByTagName('Domicilio');
            if ($Domicilios->length > 0 && $version == "3.2") {
                $this->status = "CCE166 El nodo cce11:ComercioExterior:Emisor:Domicilio no debe registrarse si la versión de CFDI es 3.2.";
                $this->codigo = "166 ".$this->status;
                return false;
            }
            if ($Domicilios->length == 0 && $version == "3.3") {
                $this->status = "CCE167 El nodo cce11:ComercioExterior:Emisor:Domicilio debe registrarse si la versión de CFDI es 3.3.";
                $this->codigo = "167 ".$this->status;
                return false;
            }
            if ($Domicilios->length > 0) {
                $Domicilio = $Domicilios->item(0);
                $Pais = $Domicilio->getAttribute("Pais");
                if ($Pais != "MEX") { 
                    $this->status = 'CCE168 El atributo cce11:ComercioExterior:Emisor:Domicilio:Pais debe tener la clave "MEX".';
                    $this->codigo = "168 ".$this->status;
                    return false;
                }
                $c_Pais = $this->Obten_Catalogo("c_Pais", $Pais);
                $regex_cp = trim($c_Pais["regex_cp"]);
                $colonia = $Domicilio->getAttribute("Colonia");
                $localidad = $Domicilio->getAttribute("Localidad");
                $municipio = $Domicilio->getAttribute("Municipio");
                $estado = $Domicilio->getAttribute("Estado");
                $codigoPostal = $Domicilio->getAttribute("CodigoPostal");
                $ok = $this->Checa_Catalogo("c_Estado", $estado, $Pais);
                if (!$ok) {
                    $this->status = 'CCE169 El atributo cce11:ComercioExterior:Emisor:Domicilio:Estado debe contener una clave del catálogo de catCFDI:c_Estado donde la columna c_Pais tiene el valor "MEX".';
                    $this->codigo = "169 ".$this->status;
                    return false;
                }
                $ok = $this->Checa_Catalogo("c_Municipio", $municipio, $estado);
                if (!$ok) {
                    $this->status = 'CCE170 El atributo cce11:ComercioExterior:Emisor:Domicilio:Municipio debe contener una clave del catálogo de catCFDI:c_Municipio donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo Estado.';
                    $this->codigo = "170 ".$this->status;
                    return false;
                }
                if ($localidad != "") {
                    $ok = $this->Checa_Catalogo("c_Localidad", $localidad, $estado);
                    if (!$ok) {
                        $this->status = "CC171 El atributo cce11:ComercioExterior:Emisor:Domicilio:Localidad debe contener una clave del catálogo de catCFDI:c_Localidad donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo Estado.";
                        $this->codigo = "171 ".$this->status;
                        return false;
                    }
                }
                $ok = $this->Checa_Catalogo("c_Colonia", $colonia, $codigoPostal);
                if (!$ok) {
                    $this->status = 'CCE172 El atributo cce11:ComercioExterior:Emisor:Domicilio:Colonia debe contener una clave del catálogo de catCFDI:c_Colonia donde la columna c_CodigoPostal debe ser igual a la clave registrada en el atributo CodigoPostal.';
                    $this->codigo = "172 ".$this->status;
                    return false;
                }
                $c_CP = $this->Obten_Catalogo("c_CP", $codigoPostal);
                if (sizeof($c_CP)==0) {
                    $this->status = "CCE218 Error no clasidficado. $codigoPostal  no existe";
                    $this->codigo = "218 ".$this->status;
                    return false;
                }
                // var_dump($c_CP);
                $c_Estado = trim($c_CP["c_estado"]);
                $c_Municipio = trim($c_CP["c_municipio"]);
                $c_Localidad = trim($c_CP["c_localidad"]);
                if ($c_Estado!==$estado ||
                    $c_Municipio!==$municipio ||
                    ($localidad!="" && $c_Localidad!==$localidad)) {
                    $this->status = 'CCE173 El atributo cce11:ComercioExterior:Emisor:Domicilio:CodigoPostal debe contener una clave del catálogo catCFDI:c_CodigoPostal donde la columna clave de c_Estado debe ser igual a la clave registrada en el atributo Estado, la columna clave de c_Municipio debe ser igual a la clave registrada en el atributo Municipio y si existe el atributo de Localidad, la columna clave de c_Localidad debe ser igual a la clave registrada en el atributo Localidad.';
                    $this->codigo = "173 ".$this->status;
                    return false;
                }
            }
        }
        // }}}
        // {{{ Propietario
        $cce_Propietarios = $cce->getElementsByTagName('Propietario');
        if ($cce_Propietarios->length > 0) {
            $Propietario = $cce_Propietarios->item(0);
            $NumRegIdTrib = $Propietario->getAttribute("NumRegIdTrib");
            $ResidenciaFiscal = $Propietario->getAttribute("ResidenciaFiscal");
            $c_Pais = $this->Obten_Catalogo("c_Pais", $ResidenciaFiscal);
            $regex_cp = trim($c_Pais["regex_cp"]);
            $regex_taxid = trim($c_Pais["regex_taxid"]);
            $lista_taxid = trim($c_Pais["lista_taxid"]);
            if ($this->Cuenta_Catalogo("c_Taxid",$ResidenciaFiscal)>0) { // Si hay registros , busca en catalogo
                $ok = $this->Checa_Catalogo("c_Taxid",$NumRegIdTrib,$ResidenciaFiscal);
                if (!$ok) {
                    $this->status = "CCE174 El atributo cce11:ComercioExterior:Propietario:NumRegIdTrib no tiene un valor que exista en el registro del país indicado en el atributo cce1:Propietario:ResidenciaFiscal.";
                    $this->codigo = "174 ".$this->status;
                    return false;
                 }
            } elseif ($regex_taxid!="") { // Valida solo formato, no en lista
                   $aux = "/^$regex_taxid$/A";
                   $ok = preg_match($aux,$NumRegIdTrib);
                   if (!$ok) {
                       $this->status = 'CCE175 El atributo cce11:ComercioExterior:Propietario:NumRegIdTrib no cumple con el patrón publicado en la columna "Formato de registro de identidad tributaria" del país indicado en el atributo cce1:Propietario:ResidenciaFiscal.';
                       $this->codigo = "175 ".$this->status;
                       return false;
                   }
            }
        }
        // }}}
        // {{{ Receptor
        $NumRegIdTrib = $cce_Receptor->getAttribute("NumRegIdTrib");
        if ($version == "3.3") {
            if ($NumRegIdTrib!="") {
                $this->status = "CCE176 El atributo cce11:ComercioExterior:Receptor:NumRegIdTrib no debe registrarse si la versión de CFDI es 3.3.";
                $this->codigo = "176 ".$this->status;
                return false;
            }
        }
        if ($version == "3.2") {
            if ($NumRegIdTrib=="") {
                $this->status = "CCE177 El atributo cce11:ComercioExterior:Receptor:NumRegIdTrib debe registrarse si la versión de CFDI es 3.2. ";
                $this->codigo = "177 ".$this->status;
                return false;
            }
            if ($this->Cuenta_Catalogo("c_Taxid",$pais_receptor)>0) { 
                // Si hay registros , busca en catalogo
                $ok = $this->Checa_Catalogo("c_Taxid",$NumRegIdTrib,$pais_receptor);
                if (!$ok) {
                    $this->status = 'CCE178 El atributo cce11:ComercioExterior:Receptor:NumRegIdTrib no tiene un valor que exista en el registro del país indicado en el atributo cfdi:Comprobante:Receptor:Domicilio:pais.';
                    $this->codigo = "178 ".$this->status;
                    return false;
                }
            } elseif ($regex_taxid!="") { // Valida solo formato, no en lista
                $aux = "/^$regex_taxid$/A";
                $ok = preg_match($aux,$NumRegIdTrib);
                if (!$ok) {
                        $this->status = 'CCE179 El atributo cce11:ComercioExterior:Receptor:NumRegIdTrib no cumple con el patrón publicado en la columna "Formato de registro de identidad tributaria" del país indicado en el atributo.';
                        $this->codigo = "179 ".$this->status;
                        return false;
                 }
            }
        } // 3.2
        if ($version == "3.2") {
            if ($cce_Domicilios_Receptor->length > 0) {
                $this->status = 'CCE180 El nodo cce11:ComercioExterior:Receptor:Domicilio no debe registrarse si la versión de CFDI es 3.2. ';
                $this->codigo = "180 ".$this->status;
                return false;
            }
        }
        if ($version == "3.3") {
            if ($cce_Domicilios_Receptor->length == 0) {
                $this->status = 'CCE181 El nodo cce11:ComercioExterior:Receptor:Domicilio debe registrarse si la versión de CFDI es 3.3. ';
                $this->codigo = "181 ".$this->status;
                return false;
            }
            $curp = $cce_Receptor->getAttribute("Curp");
            $Domicilio = $cce_Domicilios_Receptor->item(0);
            $Pais = $Domicilio->getAttribute("Pais");
            $c_Pais = $this->Obten_Catalogo("c_Pais", $Pais);
            if (sizeof($c_Pais)==0) {
                $this->status = "CCE218 Error No identificado. Pais Receptor $Pais no existe en c:Pais.";
                $this->codigo = "218 ".$this->status;
                return false;
            }
            $regex_cp = trim($c_Pais["regex_cp"]);
            $Colonia = $Domicilio->getAttribute("Colonia");
            $Localidad = $Domicilio->getAttribute("Localidad");
            $Municipio = $Domicilio->getAttribute("Municipio");
            $Estado = $Domicilio->getAttribute("Estado");
            $CodigoPostal = $Domicilio->getAttribute("CodigoPostal");
            if ($Pais == "MEX") { // Con mexico se valida, demas es texto libre
                if (strlen($Colonia)==4 && is_numeric($Colonia)) {
                    $ok = $this->Checa_Catalogo("c_Colonia", $Colonia, $CodigoPostal);
                    if (!$ok) {
                        $this->status = 'CCE182 El atributo cce11:ComercioExterior:Receptor:Domicilio:Colonia debe tener un valor del catálogo de colonia donde la columna código postal sea igual a la clave registrada en el atributo CodigoPostal cuando la clave de país es "MEX", contiene una cadena numérica de cuatro posiciones y la versión de CFDI es 3.3.';
                        $this->codigo = "182 ".$this->status;
                        return false;
                    }
                } // Colonia
                if ($Pais=="ZZZ" || $this->Cuenta_Catalogo("c_Estado",$Pais)==0) {
                    // ok
                } else {
                    $ok = $this->Checa_Catalogo("c_Estado", $Estado, $Pais);
                    if (!$ok) {
                        $this->status = 'CCE185 El atributo cce11:ComercioExterior:Receptor:Domicilio:Estado debe tener un valor del catálogo de estados catCFDI:c_Estado donde la columna c_Pais sea igual a la clave de país registrada en el atributo Pais y la versión de CFDI es 3.3.';
                        $this->codigo = "185 ".$this->status;
                        return false;
                    }
                } // Estado
                $ok = $this->Checa_Catalogo("c_Localidad", $Localidad, $Estado);
                if (!$ok) {
                    $this->status = 'CCE183 El atributo cce11:ComercioExterior:Receptor:Domicilio:Localidad debe tener un valor del catálogo de localidades (catCFDI:c_Localidad) donde la columna c_Estado sea igual a la clave registrada en el atributo Estado cuando la clave de país es "MEX" y la versión de CFDI es 3.3. ';
                    $this->codigo = "183 ".$this->status;
                    return false;
                }
                 // Localidad
                $ok = $this->Checa_Catalogo("c_Municipio", $Municipio, $Estado);
                if (!$ok) {
                    $this->status = 'CCE184 El atributo cce11:ComercioExterior:Receptor:Domicilio:Municipio debe tener un valor del catálogo de municipios (catCFDI:c_Municipio) donde la columna c_Estado sea igual a la clave registrada en el atributo Estado cuando la clave de país es "MEX" y la versión de CFDI es 3.3. ';
                    $this->codigo = "184 ".$this->status;
                    return false;
                } // Municipio
                $c_CP = $this->Obten_Catalogo("c_CP", $CodigoPostal);
                if (sizeof($c_CP)==0) {
                     $this->status = 'CCE218 Error no clasificado, No existe codigo postal cce:Receptor en c_CodigoPostal';
                     $this->codigo = "218 ".$this->status;
                     return false;
                }
                $c_Estado = trim($c_CP["c_estado"]);
                $c_Municipio = trim($c_CP["c_municipio"]);
                $c_Localidad = trim($c_CP["c_localidad"]);
                if ($c_Estado!==$Estado ||
                    $c_Municipio!==$Municipio ||
                    ($Localidad!="" && $c_Localidad!==$Localidad) ) {
                    $this->status = 'CCE187 El atributo cce11:ComercioExterior:Receptor:Domicilio:CodigoPostal debe tener un valor del catálogo de códigos postales catCFDI:c_CodigoPostal donde la columna c_Estado sea igual a la clave registrada en el atributo Estado, la columna c_Municipio sea igual a la clave registrada en el atributo Municipio y la columna c_Localidad sea igual a la clave registrada en el atributo Localidad en caso de que se haya registrado cuando la clave de país es "MEX" y la versión de CFDI es 3.3. ';
                    $this->codigo = "187 ".$this->status;
                    return false;
                }
            } else { // Pais != "MEX"
                 if ($regex_cp!="") { 
                     $aux = "/^$regex_cp$/A";
                     $ok = preg_match($aux,$CodigoPostal);
                     if (!$ok) {
                         $this->status = 'CCE186 El atributo cfdi:Comprobante:Receptor:Domicilio:codigoPostal debe cumplir con el patrón especificado en el catálogo catCFDI:c_Pais cuando tipoDeComprobante es distinto de "traslado" o MotivoTraslado es distinto de "02" y el pais es distinto de "MEX".';
                         $this->codigo = "186 ".$this->status;
                         return false;
                     }
                 } // regex_cp
            }
        }
        // }}}
        // {{{ Destinatarios
        if ($Destinatarios->length > 0) {
            if ($Destinatarios->length != 1 && 
                ($TipoDeComprobante=="T" || $tipoDeComprobante=="traslado")) {
                $this->status = 'CCE188 El campo tipoDeComprobante tiene el valor "traslado" por lo tanto sólo podrás registrar un Destinatario.';
                $this->codigo = "188 ".$this->status;
                return false;
            }
            $Destinatario = $Destinatarios->item(0);
            $NumRegIdTrib = $Destinatario->getAttribute("NumRegIdTrib");
            $Domicilio = $Destinatario->getElementsByTagName('Domicilio')->item(0);
            $Colonia = $Domicilio->getAttribute("Colonia");
            $Localidad = $Domicilio->getAttribute("Localidad");
            $Municipio = $Domicilio->getAttribute("Municipio");
            $Estado = $Domicilio->getAttribute("Estado");
            $CodigoPostal = $Domicilio->getAttribute("CodigoPostal");
            $Pais = $Domicilio->getAttribute("Pais");
            $c_Pais = $this->Obten_Catalogo("c_Pais", $Pais);
            $regex_cp = trim($c_Pais["regex_cp"]);
            $regex_taxid = trim($c_Pais["regex_taxid"]);
            if ($NumRegIdTrib!="") {
                if ($this->Cuenta_Catalogo("c_Taxid",$Pais)>0) { // Si hay registros , busca en catalogo
                    $ok = $this->Checa_Catalogo("c_Taxid",$NumRegIdTrib,$Pais);
                    if (!$ok) {
                        $this->status = "CCE189 El atributo cce11:ComercioExterior:Destinatario:NumRegIdTrib no tiene un valor que exista en el registro del país indicado en el atributo cce11:ComercioExterior:Destinatario:Domicilio:Pais.";
                        $this->codigo = "189 ".$this->status;
                        return false;
                    }
                } else {
                    if ($regex_taxid!="") { // Valida solo formato, no en lista
                        $aux = "/^$regex_taxid$/A";
                        $ok = preg_match($aux,$NumRegIdTrib);
                        if (!$ok) {
                            $this->status = 'CCE190 El atributo cce11:ComercioExterior:Destinatario:NumRegIdTrib no cumple con el patrón publicado en la columna "Formato de registro de identidad tributaria" del país indicado en el atributo cce11:ComercioExterior:Destinatario:Domicilio:Pais.';
                            $this->codigo = "190 ".$this->status;
                            return false;
                       }
                    }
                }
            } // NumRegIdTrib != ""
            if ($Pais=="ZZZ" || $this->Cuenta_Catalogo("c_Estado",$Pais)==0) {
                // ok
            } else {
                $ok = $this->Checa_Catalogo("c_Estado", $Estado, $Pais);
                if (!$ok) {
                    $this->status = 'CCE194 El atributo cce11:ComercioExterior:Destinatario:Domicilio:Estado debe tener un valor del catálogo de estados catCFDI:c_Estado donde la columna c_Pais sea igual a la clave de país registrada en el atributo Pais cuando la clave de país existe en la columna c_Pais del catálogo catCFDI:c_Estado y es diferente de "ZZZ".';
                    $this->codigo = "194 ".$this->status;
                    return false;
                }
            } // Pais ? ZZZ
            if ($Pais == "MEX") { // Con mexico se valida, demas es texto libre
                if (strlen($Colonia)==4 && is_numeric($Colonia)) {
                    $ok = $this->Checa_Catalogo("c_Colonia", $Colonia, $CodigoPostal);
                    if (!$ok) {
                        $this->status = 'CCE191 El atributo cce11:ComercioExterior:Destinatario:Domicilio:Colonia debe tener un valor del catálogo de colonias donde la columna código postal sea igual a la clave registrada en el atributo CodigoPostal cuando la clave de país es "MEX" y contiene una cadena numérica de cuatro posiciones.';
                        $this->codigo = "191 ".$this->status;
                        return false;
                    }
                } // Colonia
                if ($Localidad != "") {
                    $ok = $this->Checa_Catalogo("c_Localidad", $Localidad, $Estado);
                    if (!$ok) {
                        $this->status = 'CCE192 El atributo cce11:ComercioExterior:Destinatario:Domicilio:Localidad debe tener un valor del catálogo de localidades (catCFDI:c_Localidad) donde la columna c_Estado sea igual a la clave registrada en el atributo Estado cuando la clave de país es "MEX". ';
                        $this->codigo = "192 ".$this->status;
                        return false;
                    }
                } // Localidad
                $ok = $this->Checa_Catalogo("c_Municipio", $Municipio, $Estado);
                if (!$ok) {
                    $this->status = 'CCE193 El atributo cce11:ComercioExterior:Destinatario:Domicilio:Municipio debe tener un valor del catálogo de municipios (catCFDI:c_Municipio) donde la columna c_Estado sea igual a la clave registrada en el atributo Estado cuando la clave de país es "MEX".';
                    $this->codigo = "193 ".$this->status;
                    return false;
                } // Municipio
                $c_CP = $this->Obten_Catalogo("c_CP", $CodigoPostal);
                if (sizeof($c_CP)==0) {
                     $this->status = 'CCE218 Error no clasificado, No existe codigo postal en c_CodigoPostal';
                     $this->codigo = "218 ".$this->status;
                    return false;
                }
                $c_Estado = trim($c_CP["c_estado"]);
                $c_Municipio = trim($c_CP["c_municipio"]);
                $c_Localidad = trim($c_CP["c_localidad"]);
                if ($c_Estado!==$Estado ||
                    $c_Municipio!==$Municipio ||
                    ($Localidad!="" && $c_Localidad!==$Localidad) ) {
                    $this->status = 'CCE196 El atributo cce11:ComercioExterior:Destinatario:Domicilio:CodigoPostal debe tener un valor del catálogo de códigos postales catCFDI:c_CodigoPostal donde la columna c_Estado sea igual a la clave registrada en el atributo Estado, la columna c_Municipio sea igual a la clave registrada en el atributo Municipio y la columna c_Localidad sea igual a la clave registrada en el atributo Localidad en caso de que se haya registrado cuando la clave de país es "MEX".';
                    $this->codigo = "196 ".$this->status;
                    return false;
                }
            } else { // Pais != "MEX"
                if ($regex_cp!="") { // Valida formato
                    $aux = "/^$regex_cp$/A";
                    $ok = preg_match($aux,$CodigoPostal);
                    if (!$ok) {
                        $this->status = 'CCE195 El atributo cce11:ComercioExterior:Destinatario:Domicilio:CodigoPostal debe cumplir con el patrón especificado para el país cuando es distinta de "MEX". ';
                        $this->codigo = "195 ".$this->status;
                        return false;
                    }
                }
            } // Pais == "MEX" o no
        } // Si hay destinario
        // }}}
        // {{{ Mercancias
        $hash_conc=array();
        $c_Moneda = $this->Obten_Catalogo("c_Moneda", $Moneda);
        $dec_moneda = (int)$c_Moneda["decimales"];
        $ID = ($version=="3.2") ? "noIdentificacion" : "NoIdentificacion";
        $CANT = ($version=="3.2") ? "cantidad" : "Cantidad";
        $UNID = ($version=="3.2") ? "unidad" : "Unidad";
        $VALOR = ($version=="3.2") ? "valorUnitario" : "ValorUnitario";
        $IMPO = ($version=="3.2") ? "importe" : "Importe";
        for ($i=0; $i<$nb_Conceptos; $i++) {
            $Concepto = $Conceptos->item($i);
            $noid = $Concepto->getAttribute($ID);
            if ($noid=="") {
                $this->status = 'CCE197 El atributo cfdi:Comprobante:Conceptos:Concepto:NoIdentificacion se debe registrar en cada concepto. ';
                $this->codigo = "197 ".$this->status;
                return false;
            }
            $impo = $Concepto->getAttribute($IMPO);
            if (!array_key_exists($noid,$hash_conc)) $hash_conc[$noid]=0;
            $hash_conc[$noid] += $impo;
        }
        $hash_merc=array();
        for ($j=0; $j<$nb_Mercancias; $j++) {
            $Mercancia = $Mercancias->item($j);
            $noid = $Mercancia->getAttribute("NoIdentificacion");
            $FraccionArancelaria = $Mercancia->getAttribute("FraccionArancelaria");
            if (!array_key_exists($noid,$hash_conc)) {
                $this->status = 'CCE198 Debe existir al menos un cfdi:Comprobante:Conceptos:Concepto:NoIdentificacion relacionado con cce11:ComercioExterior:Mercancias:Mercancia:NoIdentificacion.';
                $this->codigo = "198 ".$this->status;
                return false;
            }
            $impo = (double)$Mercancia->getAttribute("ValorDolares");
            if ($impo!=0 && $impo!=1) {
                if (!array_key_exists($noid,$hash_merc)) $hash_merc[$noid] = 0;
                $hash_merc[$noid] += $impo;
            }
        }    
        for ($i=0; $i<$nb_Conceptos; $i++) {
            $Concepto = $Conceptos->item($i);
            $id_conc = $Concepto->getAttribute($ID);
            $hay=false;
            for ($j=0; $j<$nb_Mercancias; $j++) {
                 $Mercancia = $Mercancias->item($j);
                 $id_merc = $Mercancia->getAttribute("NoIdentificacion");
                 if ($id_conc==$id_merc) {
		             $hay=true;
	             }
	         }
             if (!$hay) {
                 $this->status = 'CCE199 Debe existir al menos un concepto en el nodo cfdi:Comprobante:Conceptos por cada mercancía registrada en el elemento cce1:ComercioExterior:Mercancias donde el atributo cce11:ComercioExterior:Mercancias:Mercancia:NoIdentificacion sea igual al atributo cfdi:Comprobante:Conceptos:Concepto:NoIdentificacion.';
                 $this->codigo = "199 ".$this->status;
                 return false;
             }
        }
		$hash=array();
		for ($l=0; $l<$nb_Mercancias; $l++) {
		    $Mercancia = $Mercancias->item($l);
            $NoIdentificacion = $Mercancia->getAttribute("NoIdentificacion");
            $FraccionArancelaria = $Mercancia->getAttribute("FraccionArancelaria");
            $llave = "$FraccionArancelaria+$NoIdentificacion";
            if (!array_key_exists($llave,$hash)) {
                $hash[$llave]=true;
            } else {
                $this->status = 'CCE200 No se deben repetir elementos Mercancia donde el NoIdentificacion y la FraccionArancelaria sean iguales en el elemento cce11:ComercioExterior:Mercancias.';
                $this->codigo = "200 ".$this->status;
                return false;
            }
            $CantidadAduana = $Mercancia->getAttribute("CantidadAduana");
            $UnidadAduana = $Mercancia->getAttribute("UnidadAduana");
            $ValorUnitarioAduana = $Mercancia->getAttribute("ValorUnitarioAduana");
            $ValorDolares = $Mercancia->getAttribute("ValorDolares");
            for ($m=0; $m<$nb_Conceptos; $m++) {
                $Concepto = $Conceptos->item($m);
                $id_conc = $Concepto->getAttribute($ID);
                if ($id_conc==$NoIdentificacion) {
                    $cantidad = $Concepto->getAttribute($CANT);
                    $unidad = $Concepto->getAttribute($UNID);
                    $valorUnitario = $Concepto->getAttribute($VALOR);
                    $importe = $Concepto->getAttribute($IMPO);
                    if ($CantidadAduana=="") {
                        $regex = "[0-9]{1,14}(\.([0-9]{1,3}))?";
                        $aux="/^$regex$/A";
                        $ok = preg_match($aux,$cantidad);
                        if ($cantidad < 0.001 || !$ok) {
                            $this->status = 'CCE201 El atributo cfdi:Comprobante:Conceptos:Concepto:Cantidad no cumple con alguno de los valores permitidos cuando no se registra el atributo cce11:ComercioExterior:Mercancias:Mercancia:CantidadAduana.';
                            $this->codigo = "201 ".$this->status;
                            return false;
                        }
                        $ok = $this->Checa_Catalogo("c_UnidadMedidaAduana", $unidad);
                        if (!$ok) {
                            $this->status = 'CCE202 El atributo cfdi:Comprobante:Conceptos:Concepto:Unidad no cumple con alguno de los valores permitidos cuando no se registra el atributo cce11:ComercioExterior:Mercancias:Mercancia:CantidadAduana.';
                            $this->codigo = "202 ".$this->status;
                            return false;
                        }
                        $regex = "[0-9]{1,16}(\.([0-9]{1,4}))?";
                        $aux="/^$regex$/A";
                        $ok = preg_match($aux,$valorUnitario);
                        if ($valorUnitario < 0.001 || !$ok) {
                            $this->status = 'CCE203 El atributo cfdi:Comprobante:Conceptos:Concepto:ValorUnitario no cumple con alguno de los valores permitidos cuando no se registra el atributo cce11:ComercioExterior:Mercancias:Mercancia:CantidadAduana.';
                            $this->codigo = "203 ".$this->status;
                            return false;
                        }
                        $dec_cant = $this->cantidad_decimales($cantidad);
                        $dec_valor = $this->cantidad_decimales($valorUnitario);
                        $inf = round(($cantidad - (pow(10,-1*$dec_cant)/2))*($valorUnitario - pow(10,-1*$dec_valor)/2),$dec_moneda,PHP_ROUND_HALF_DOWN);
                        $sup = round(($cantidad + (pow(10,-1*$dec_cant)/2)-pow(10,-12))*($valorUnitario + pow(10,-1 * $dec_valor)/2)-pow(10,-12),$dec_moneda,PHP_ROUND_HALF_UP);
                        $impo = (double)$importe;
                        // echo "inf=$inf sup=$sup impo=$impo dec_cant=$dec_cant dec_valor=$dec_valor dec_moneda=$dec_moneda";
                        if ($impo < $inf || $impo > $sup) {
                            $this->status = "CCE204 El atributo cfdi:Comprobante:Conceptos:Concepto:importe debe ser mayor o igual que el límite inferior y menor o igual que el límite superior calculado.";
                            $this->codigo = "204 ".$this->status;
                            return false;
                        }
                    } //  CantidadAduana == ""
                }
            }
        }
        if ($TipoCambio=="") $TipoCambio="1";
        $dec_tipo = $this->cantidad_decimales($TipoCambio);
        $TipoCambio = (double)$TipoCambio;
        foreach ($hash_merc as $noid => $impo_merc) {
            $impo_conc = $hash_conc[$noid];
            $tipo = ($TipoCambio - (pow(10,-1*$dec_tipo)/2))/
                    ($TipoCambioUSD + (pow(10,-1*$dec_tipo)/2) - pow(10,-12));
            $inf = round(($impo_conc - (pow(10,-1*$dec_moneda)/2))*$tipo,$dec_moneda,PHP_ROUND_HALF_DOWN);
            $tipo = ($TipoCambio + (pow(10,-1*$dec_tipo)/2) - pow(10,-12))/
                    ($TipoCambioUSD - (pow(10,-1*$dec_tipo)/2));
            $sup = round(($impo_conc + (pow(10,-1*$dec_moneda)/2)-pow(10,-12))*$tipo,$dec_moneda,PHP_ROUND_HALF_UP);
             //echo "noid=$noid impo_conc=$impo_conc inf=$inf sup=$sup impo_merc=$impo_merc\n";
            if ($impo_merc < $inf || $impo_merc > $sup) {
                $this->status = 'CCE205 La suma de los campos cce11:ComercioExterior:Mercancias:Mercancia:ValorDolares distintos de "0" y "1" de todas las mercancías que tengan el mismo NoIdentificacion y éste sea igual al NoIdentificacion del concepto debe ser mayor o igual al valor mínimo y menor o igual al valor máximo calculado.';
                $this->codigo = "205 ".$this->status;
                return false;
            }
        }
        $cantidad_0=0; $cantidad_3=0; $cantidad_1=0;
        $hay_muestra = false; $suma_muestra = 0;
		for ($l=0; $l<$nb_Mercancias; $l++) {
		    $Mercancia = $Mercancias->item($l);
            $NoIdentificacion = $Mercancia->getAttribute("NoIdentificacion");
            $FraccionArancelaria = $Mercancia->getAttribute("FraccionArancelaria");
            $CantidadAduana = $Mercancia->getAttribute("CantidadAduana");
            $UnidadAduana = $Mercancia->getAttribute("UnidadAduana");
            $ValorUnitarioAduana = $Mercancia->getAttribute("ValorUnitarioAduana");
            $ValorDolares = $Mercancia->getAttribute("ValorDolares");
            for ($m=0; $m<$nb_Conceptos; $m++) {
                $Concepto = $Conceptos->item($m);
                $id_conc = $Concepto->getAttribute($ID);
                if ($id_conc==$NoIdentificacion) {
                    $cantidad = $Concepto->getAttribute($CANT);
                    $unidad = $Concepto->getAttribute($UNID);
                    $valorUnitario = $Concepto->getAttribute($VALOR);
                    if ($unidad!="99" && $UnidadAduana!="99") {
                        if ($FraccionArancelaria=="") {
                            $this->status = 'CCE206 El atributo cce11:ComercioExterior:Mercancias:Mercancia:FraccionArancelaria debe registrarse cuando el atributo cce11:ComercioExterior:Mercancias:Mercancia:UnidadAduana o el atributo cfdi:Comprobante:Conceptos:Concepto:Unidad tienen un valor distinto de "99".';
                            $this->codigo = "206 ".$this->status;
                            return false;
                        }
                    } else {
                        if ($FraccionArancelaria!="") {
                            $this->status = 'CCE207 El atributo cce11:ComercioExterior:Mercancias:Mercancia:FraccionArancelaria no debe registrarse cuando el atributo cce11:ComercioExterior:Mercancias:Mercancia:UnidadAduana o el atributo cfdi:Comprobante:Conceptos:Concepto:Unidad tienen el valor "99".';
                            $this->codigo = "207 ".$this->status;
                            return false;
                        }
                    }
                    if ($UnidadAduana!="" && $UnidadAduana!="99") {
                        if ($ValorUnitarioAduana!="" && $ValorUnitarioAduana <= "0") {
                            $this->status = 'CCE215 El atributo cce11:ComercioExterior:Mercancias:Mercancia:ValorUnitarioAduana debe ser mayor que "0" cuando  cce11:ComercioExterior:Mercancias:Mercancia:UnidadAduana es distinto de "99".';
                            $this->codigo = "215 ".$this->status;
                            return false;
                        }
                    }
                    if ($FraccionArancelaria!="") {
                        $c_fraccion = $this->Obten_Catalogo("c_FraccionArancelaria", $FraccionArancelaria);
                        if (sizeof($c_fraccion)==0) {
                            $this->status = 'CCE208 El atributo cce11:ComercioExterior:Mercancias:Mercancia:FraccionArancelaria debe tener un valor vigente del catálogo catCFDI:c_FraccionArancelaria.';
                            $this->codigo = "208 ".$this->status;
                            return false;
                        }
                        $c_umt = trim($c_fraccion["unidad"]);
                        if ($UnidadAduana!="" && $UnidadAduana!=$c_umt) {
                            $this->status = 'CCE209 El atributo cce11:ComercioExterior:Mercancias:Mercancia:UnidadAduana debe tener el valor especificado en el catálogo catCFDI:c_FraccionArancelaria columna "UMT" cuando el atributo cce11:ComercioExterior:Mercancias:Mercancia:FraccionArancelaria está registrado.';
                            $this->codigo = "209 ".$this->status;
                            return false;
                        }
                        if ($UnidadAduana=="" && $unidad!="" && $unidad!=$c_umt) {
                            $this->status = 'CCE210 El atributo cfdi:Comprobante:Conceptos:Concepto:Unidad del concepto relacionado a la mercncía debe tener el valor especificado en el catálogo catCFDI:c_FraccionArancelaria columna "UMT" cuando el atributo cce11:ComercioExterior:Mercancias:Mercancia:FraccionArancelaria está registrado.';
                            $this->codigo = "210 ".$this->status;
                            return false;
                        }
                    }
                } // ID conc = ID Merc
            } // Para cada conc para localizar que coincida con merc
            if ($FraccionArancelaria=="98010001") {
			    $pesos=($ValorDolares * $TipoCambioUSD);
                $hay_muestra = true;
                $suma_muestra += $pesos;
            }
            if ($CantidadAduana=="" && $UnidadAduana=="" && $ValorUnitarioAduana=="") {
                $cantidad_0++;
            } elseif ($CantidadAduana!="" && $UnidadAduana!="" && $ValorUnitarioAduana!="") {
                $cantidad_3++;
            } elseif ($CantidadAduana!="" || $UnidadAduana!="" || $ValorUnitarioAduana!="") {
                $cantidad_1++;
            } 
        } // para cada merc
        if ($hay_muestra) {
            if ($version=="3.2") {
                $descuento = (double)$Comprobante->getAttribute("descuento");
                $suma_muestra = $suma_muestra * $TipoCambio;
                if ($suma_muestra > $descuento) {
                    $this->status = 'CCE211 El atributo cfdi:Comprobante:descuento debe ser mayor o igual que la suma de los atributos cce11:ComercioExterior:Mercancias:Mercancia:ValorDolares de todas las mercancías que tengan la fracción arancelaria "98010001" convertida a la moneda del comprobante si la versión del CFDI es 3.2. ';
                    $this->codigo = "211 ".$this->status;
                    return false;
                }
            } else {
                $descuento = (double)$Comprobante->getAttribute("Descuento");
                if ($suma_muestra > $descuento) {
                    $this->status = 'CCE212 La suma de los valores de cfdi:Comprobante:Conceptos:Concepto:Descuento donde el NoIdentificacion es el mismo que el de la mercancía convertida a la moneda del comprobante debe ser mayor o igual que la suma de los valores de cce11:ComercioExterior:Mercancias:Mercancia:ValorDolares de todas las mercancías que tengan la fracción arancelaria "98010001" y el NoIdentificacion sea igual al NoIdentificacion del concepto si la versión del CFDI es 3.3. ';
                    $this->codigo = "212 ".$this->status;
                    return false;
                }
            }
        }
        // echo "cantidad_0=$cantidad_0 cantidad_1=$cantidad_1 cantidad_3=$cantidad_3\n";
        if ($cantidad_1 > 0) {
            $this->status = 'CCE213 Los atributos CantidadAduana, UnidadAduana y ValorUnitarioAduana deben existir en los registros involucrados si se ha registrado alguno de ellos, si existe más de un concepto con el mismo NoIdentificacion o si existe más de una mercancía con el mismo NoIdentificacion.';
            $this->codigo = "213 ".$this->status;
            return false;
        }
        if ($cantidad_0 > 0 && $cantidad_3 > 0) {
            $this->status = 'CCE214 Los atributos CantidadAduana, UnidadAduana y ValorUnitarioAduana deben registrarse en todos los elementos mercancía del comprobante, siempre que uno de ellos los tenga registrados.';
            $this->codigo = "214 ".$this->status;
            return false;
        }
		for ($l=0; $l<$nb_Mercancias; $l++) {
		    $Mercancia = $Mercancias->item($l);
            $NoIdentificacion = $Mercancia->getAttribute("NoIdentificacion");
            $FraccionArancelaria = $Mercancia->getAttribute("FraccionArancelaria");
            $CantidadAduana = $Mercancia->getAttribute("CantidadAduana");
            $UnidadAduana = $Mercancia->getAttribute("UnidadAduana");
            $ValorUnitarioAduana = $Mercancia->getAttribute("ValorUnitarioAduana");
            $ValorDolares = $Mercancia->getAttribute("ValorDolares");
            if ($ValorDolares!="") {
                for ($m=0; $m<$nb_Conceptos; $m++) {
                    $Concepto = $Conceptos->item($m);
                    $id_conc = $Concepto->getAttribute($ID);
                    if ($id_conc==$NoIdentificacion) {
                        $cantidad = $Concepto->getAttribute($CANT);
                        $unidad = $Concepto->getAttribute($UNID);
                        $valorUnitario = $Concepto->getAttribute($VALOR);
                        $importe = $Concepto->getAttribute($IMPO);
                        $impo = (double)$ValorDolares;
                        if ($CantidadAduana!="" && $ValorUnitarioAduana!="") {
                            $dec_cant = $this->cantidad_decimales($CantidadAduana);
                            $dec_valor = $this->cantidad_decimales($ValorUnitarioAduana);
                            $inf = round(($CantidadAduana - (pow(10,-1*$dec_cant)/2))*($ValorUnitarioAduana - pow(10,-1*$dec_valor)/2),2,PHP_ROUND_HALF_DOWN);
                            $sup = round(($CantidadAduana + (pow(10,-1*$dec_cant)/2)-pow(10,-12))*($ValorUnitarioAduana + pow(10,-1 * $dec_valor)/2)-pow(10,-12),2,PHP_ROUND_HALF_UP);
                            // echo "impo=$impo inf=$inf sup=$sup\n";
                            if ($impo < $inf || $impo > $sup) {
                                $this->status = 'CCE216 El atributo cce11:ComercioExterior:Mercancias:ValorDolares de cada mercancía registrada debe ser mayor o igual que el límite inferior y menor o igual que el límtie superior o uno, cuando la normatividad lo permita y exista el atributo cce11:ComercioExterior:Mercancias:Mercancia:CantidadAduana.';
                                $this->codigo = "216 ".$this->status;
                                return false;
                            }
                        }
                        $ok =true;
                        if ($ValorDolares=="1") { 
                            // Si la normatividad lo permite se acepta
                            $ok =true;
                        } elseif ($ValorDolares=="0") {
                            if ($unidad!="99" && $UnidadAduana!="99") $ok = false;
                        } else {
                            //echo "hash_conc=".$hash_conc[$id_conc]."\n";
                            //echo "hash_merc=".$hash_merc[$NoIdentificacion]."\n";
                            //echo "impo=$impo\n";
                            if ($hash_merc[$NoIdentificacion]==$impo) {
                                // Para ques olo busque si solo hay un concepto para esa mercancia
                                $dec_tipo = $this->cantidad_decimales($TipoCambioUSD);
                                $tipo_i = ($TipoCambio - (pow(10,-1*$dec_tipo)/2))/
                                          ($TipoCambioUSD + (pow(10,-1*$dec_tipo)/2) - pow(10,-12));
                                $inf = round(($importe - (pow(10,-1*$dec_moneda)/2))*$tipo_i,$dec_moneda,PHP_ROUND_HALF_DOWN);
                                $tipo_s = ($TipoCambio + (pow(10,-1*$dec_tipo)/2) - pow(10,-12))/
                                          ($TipoCambioUSD - (pow(10,-1*$dec_tipo)/2));
                                $sup = round(($importe + (pow(10,-1*$dec_moneda)/2)-pow(10,-12))*$tipo_s,$dec_moneda,PHP_ROUND_HALF_UP);
                                if ($impo < $inf || $impo > $sup) {
                                    $ok = false;
                                    // echo "inf=$inf sup=$sup impo=$impo importe=$importe TipoCambio=$TipoCambio TipoCambioUSD=$TipoCambioUSD tipo_i=$tipo_i tipo_s=$tipo_s dec_tipo=$dec_tipo dec_moneda=$dec_moneda\n";
                                }
                            }
                        }
                        if (!$ok) {
                            $this->status = 'CCE217 El atributo cce11:ComercioExterior:Mercancias:ValorDolares de cada mercancía registrada debe ser igual al producto del valor del atributo cfdi:Comprobante:Conceptos:Concepto:Importe por el valor del atributo cfdi:Comprobante:TipoCambio y dividido entre el valor del atributo cce11:ComercioExterior:TipoDeCambioUSD donde el atributo cfdi:Comprobante:Conceptos:NoIdentificacion es igual al atributo cce11:ComercioExterior:Mercancias:Mercancia:NoIdentificacion, "0" cuando el atributo cce11:ComercioExterior:Mercancias:Mercancia:UnidadAduana o el atributo cfdi:Comprobante:Conceptos:Concepto:Unidad tienen el valor "99", o "1", cuando la normatividad lo permita y no debe existir el atributo cce11:ComercioExterior:Mercancias:Mercancia:CantidadAduana. ';
                            $this->codigo = "217 ".$this->status;
                            return false;
                        }
                    }
                }
            }
        }
        // }}}
        $this->status = "Validacion de semantica cce correcta";
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
}
