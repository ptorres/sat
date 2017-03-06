<?php
/*****************************************************************************
 * semantica_cfdi.php Valida semantica del CFDI (actualmente solo 3.3)       *
 *                                                                           *
 * 27/dic/2016 Version inicial con numero de error inventado                 *
 *             en espera de matriz de valdiacion para bateria de pruebas     *
 *****************************************************************************/
class Sem_CFDI {
    var $xml_cfd;
    var $con;
    var $codigo;
    var $status;
    var $cuenta=false; // Para saber si ya conto la cantidad de l_rfc
    function valida($xml_cfd,$conn) {
    // {{{ valida : nodo Comprobante
        error_reporting(E_ALL);
        $ok = true;
        $this->xml_cfd = $xml_cfd;
        $this->conn = $conn;
        $Comprobante = $this->xml_cfd->getElementsByTagName('Comprobante')->item(0);
        $version = $Comprobante->getAttribute("version");
        if ($version==null) $version = $Comprobante->getAttribute("Version");
        if ($version == "3.2") {
            $this->status = "CFDI000; No se valida semantica en CFDI version 3.2";
            $this->codigo = "0 ".$this->status;
            return true;
        }
        if ($version != "3.3") {
            $this->status = "CFDI001; El valor del atributo cfdi:Comprobante:Version debe de ser 3.3";
            $this->codigo = "1 ".$this->status;
            return false;
        }
        $Fecha = $Comprobante->getAttribute("Fecha");
        $a_Fecha = new DateTime($Fecha);
        $a_hoy = new DateTime();
        // $margen_inf = new DateInterval("PT1H"); // Debe de ser una Hora
        $margen_inf = new DateInterval("P1Y"); // Ponemos un anio para probar
        $a_inf = date_sub($a_hoy, $margen_inf);
        //$margen_sup = new DateInterval("PT1H"); // 1 Hora
        $margen_sup = new DateInterval("P1Y"); // 
        $a_hoy = new DateTime();
        $a_sup = date_add($a_hoy, $margen_sup);
        if ($a_Fecha < $a_inf) {
            $this->status = "CFDI101; La fecha ".$a_Fecha->format("c")." es inferior al limite (".$a_inf->format("c").")";
            $this->codigo = "101 ".$this->status;
            return false;
        }
        if ($a_Fecha > $a_sup) {
            $this->status = "CFDI102; La fecha ".$a_Fecha->format("c")." es superior al limite (".$a_sup->format("c").")";
            $this->codigo = "102 ".$this->status;
            return false;
        }
        $TipoDeComprobante = $Comprobante->getAttribute("TipoDeComprobante");
        $Conceptos = $Comprobante->getElementsByTagName("Concepto");
        $nb_Conceptos = $Conceptos->length;
        $t_Descuento = 0; $t_Importe = 0; $t_impuestos = 0;
        $hay_Descuento = false;
        for ($i=0; $i<$nb_Conceptos; $i++) {
            $Concepto = $Conceptos->item($i);
            $c_Descuento = $Concepto->getAttribute("Descuento");
            $c_Importe = $Concepto->getAttribute("Importe");
            $t_Importe += (double)$c_Importe;
            $t_Descuento += (double)$c_Descuento;
            if ($TipoDeComprobante=="T" || $TipoDeComprobante=="P") {
                if ($c_Descuento != null) {
                    $this->status = "CFDI113; El Descuento del Concepto se debe de omitir si el tipo de comprobante es T o P.";
                    $this->codigo = "113 ".$this->status;
                    return false;
                }
            }
            if ($c_Descuento != null) $hay_Descuento=true;
            $Impuestos = $Concepto->getElementsByTagName("Impuestos");
            if ($Impuestos->length > 0) {
                // {{{ Hay Impuestos
                if ($TipoDeComprobante=="T" || $TipoDeComprobante=="P" ||
                    $TipoDeComprobante=="N") {
                    $this->status = "CFDI114; Se debe de omitir el elemento Impuestos cuando TipoDeComprobante es T, P o N.";
                    $this->codigo = "114 ".$this->status;
                    return false;
                }
                $Traslados = $Impuestos->item(0)->getElementsByTagName("Traslado");
                foreach ($Traslados as $Traslado) {
                    $t_impuestos -= (double)$Traslado->getAttribute("Importe");
                }
                $Retenciones = $Impuestos->item(0)->getElementsByTagName("Retencion");
                foreach ($Retenciones as $Retencion) {
                    $t_impuestos += (double)$Retencion->getAttribute("Importe");
                }
            } // }}} Impuestos->length > 0
        } // for cada concepto
        $SubTotal = $Comprobante->getAttribute("SubTotal");
        if ($TipoDeComprobante=="I" || $TipoDeComprobante=="E" ||
            $TipoDeComprobante=="N") {
                if ($SubTotal != $t_Importe) {
                    $this->status = "CFDI103; El SubTotal ($SubTotal) no es igual a la suma ($t_Importe) del Importe de los Conceptos.";
                    $this->codigo = "103 ".$this->status;
                    return false;
                }
        } elseif ($TipoDeComprobante=="T" || $TipoDeComprobante=="P") {
                if ($SubTotal != 0) {
                    $this->status = "CFDI104; El SubTotal ($SubTotal) debe de tener el valor cero.";
                    $this->codigo = "104 ".$this->status;
                    return false;
                }
        }
        $Descuento = $Comprobante->getAttribute("Descuento");
        if ($Descuento > $SubTotal) {
            $this->status = "CFDI105; El Descuento ($Descuento) no debe de ser mayor que el SubTotal ($SubTotal).";
            $this->codigo = "105 ".$this->status;
            return false;
        }
        if ($TipoDeComprobante=="I" || $TipoDeComprobante=="E" ||
            $TipoDeComprobante=="N") {
             if ($hay_Descuento) {
                if (abs($Descuento-$t_Descuento)>0.001) {
                    $this->status = "CFDI106; El Descuento ($Descuento) no es igual a la suma ($t_Descuento) de los Descuentos de los Conceptos.";
                    $this->codigo = "106 ".$this->status;
                    return false;
                }
             } else { // No hay descuentos
                if ($Descuento != null) {
                    $this->status = "CFDI107; El Descuento en el comprobante ($Descuento) no debe de existir si no hay Descuentos en los Conceptos.";
                    $this->codigo = "107 ".$this->status;
                    return false;
                }
             }
        }
        $Moneda = $Comprobante->getAttribute("Moneda");
        $c_Moneda = $this->Obten_Catalogo("c_Moneda", $Moneda);
        if (sizeof($c_Moneda) == 0) {
            $this->status = "CFDI255; La moneda ($Moneda) no existe en el catalogo c_Moneda";
            $this->codigo = "255 ".$this->status;
            return false;
        }
        $TipoCambio = $Comprobante->getAttribute("TipoCambio");
        if ($Moneda=="XXX") {
            if ($TipoCambio != null) {
                $this->status = "CFDI108; El TipoCambio no debe de existir si la Moneda es igual a 'XXX'.";
                $this->codigo = "108 ".$this->status;
                return false;
            }
        } elseif ($Moneda=="MXN") {
            if ($TipoCambio != null && $TipoCambio != "1") {
                $this->status = "CFDI109; El TipoCambio puede no existir, pero si existe debe de tener el valor '1'";
                $this->codigo = "109 ".$this->status;
                return false;
            }
        } else {
            /* TODO: Validar limites del tipo de cambio contra
             * lo publicado */
        }
        $Total = $Comprobante->getAttribute("Total");
        if ($TipoDeComprobante=="T" || $TipoDeComprobante=="P") {
            if ($Total != 0) {
                $this->status = "CFD110; El Total ($Total) debe de ser cero si el TipoDeCoprobante es T o P.";
                $this->codigo = "110 ".$this->status;
                return false;
            }
        } else {
            $aux = (double)$SubTotal - (double)$Descuento + (double)$t_impuestos;
            if (abs($Total-$aux)>0.001) {
                $this->status = "CFD111; El Total ($Total) debe de ser igual que la suma del SubTotal menos Descuento mas los impuestos ($aux).";
                $this->codigo = "111 ".$this->status;
                return false;
            }
        }
        $CondicionesDePago = $Comprobante->getAttribute("CondicionesDePago");
        if ($TipoDeComprobante=="T" || $TipoDeComprobante=="P" ||
            $TipoDeComprobante=="N") {
            if ($CondicionesDePago != null) {
                $this->status = "CFD112; Se debe de omitir CondicionesDePago cuando el TipoDeComprobante es T, P o N.";
                $this->codigo = "112 ".$this->status;
                return false;
            }
        } 
        $FormaPago = $Comprobante->getAttribute("FormaPago");
        $MetodoPago = $Comprobante->getAttribute("MetodoPago");
        if ($TipoDeComprobante=="T" || $TipoDeComprobante=="P") {
            if ($FormaPago != null || $MetodoPago != null) {
                $this->status = "CFD115; Se deben de omitir FormaPago y MetodoPago cuando el TipoDeComprobante es T o P.";
                $this->codigo = "115 ".$this->status;
                return false;
            }
        } 
        $hay_pagos = false; $hay_cce=false;
        $Complemento = $Comprobante->getElementsByTagName("Complemento");
        if ($Complemento->length > 0) {
            $Pagos = $Complemento->item(0)->getElementsByTagName("Pagos");
            if ($Pagos->length > 0) {
                $hay_pagos = true;
            }
            $Cce = $Complemento->item(0)->getElementsByTagName("ComercioExterior");
            if ($Cce->length > 0) {
                $hay_cce = true;
            }
        }
        if ($MetodoPago == "PIP" && !$hay_pagos) {
            $this->status = "CFD117; Si MetodoPago es 'PIP: Pago inicial y parcialidades' debe de existir Complemento.Pagos.";
            $this->codigo = "117 ".$this->status;
            return false;
        } elseif ($MetodoPago != null && $hay_pagos) {
            $this->status = "CFDI116; Si existe Complemento.Pagos no debe de existir MetodoPago.";
            $this->codigo = "116 ".$this->status;
            return false;
        }
        $LugarExpedicion = $Comprobante->getAttribute("LugarExpedicion");
        $ok = $this->Checa_Catalogo("c_CP", $LugarExpedicion);
        if (!$ok) {
            $this->status = "CFDI118; El valor del atributo LugarExpedicion no cumple con un valor del catalogo c_CodigoPostal";
            $this->codigo = "118 ".$this->status;
            return false;
        }
        /* TODO: Validar contra codigos de autorizacion */
        $Confirmacion = $Comprobante->getAttribute("Confirmacion");
        /* TODO: Validar contra codigos de autorizacion */
        // }}} Comprobante
        // {{{ Emisor
        $Emisor = $Comprobante->getElementsByTagName('Emisor')->item(0);
        $rfc = $Emisor->getAttribute("Rfc");
        $RegimenFiscal = $Emisor->getAttribute("RegimenFiscal");
        $regimen = (strlen($rfc)==13) ? "c_RegimenFisica" : "c_RegimenMoral";
        $ok = $this->Checa_Catalogo($regimen, $RegimenFiscal);
        if (!$ok) {
            $this->status = "CFDI130; El valor del atributo RegimenFiscal ($RegimenFiscal) no cumple con un valor del catalogo $regimen";
            $this->codigo = "130 ".$this->status;
            return false;
        }
        // }}} Emisor
        // {{{ Receptor
        $Receptor = $Comprobante->getElementsByTagName('Receptor')->item(0);
        $rfcReceptor = $Receptor->getAttribute("Rfc");
        $ResidenciaFiscal = $Receptor->getAttribute("ResidenciaFiscal");
        $c_Pais = $this->Obten_Catalogo("c_Pais", $ResidenciaFiscal);
        if ($c_Pais) {
            $lista_taxid = trim($c_Pais["lista_taxid"]);
            $regex_taxid = trim($c_Pais["regex_taxid"]);
        } else {
            $lista_taxid = null;
            $regex_taxid = null;
        }
        $NumRegIdTrib = $Receptor->getAttribute("NumRegIdTrib");
        $row= $this->lee_l_rfc($rfcReceptor);
        if ($rfcReceptor=="XAXX010101000") {
            // {{{ RFC Generico Nacional
            if ($ResidenciaFiscal!=null) {
                $this->status = "CFDI132; El atributo cfdi:Comprobante.Receptor.ResidenciaFiscal no debe de existir para Rfc generico nacional.";
                $this->codigo = "132 ".$this->status;
                return false;
            }
            if ($NumRegIdTrib!=null) {
                $this->status = "CFDI134; El atributo cfdi:Comprobante.Receptor.NumRegIdTrib NO debe de existir para Rfc generico nacional.";
                $this->codigo = "134 ".$this->status;
                return false;
            }
            // }}}
        } elseif ($rfcReceptor=="XEXX010101000") {
            // {{{ RFC Genericos Extranjero
            if (($hay_cce || $NumRegIdTrib != null) &&
                ($ResidenciaFiscal==null || $ResidenciaFiscal=="MEX")) {
                    $this->status = "CFDI133; El atributo cfdi:Comprobante.Receptor.ResidenciaFiscal debe de existir si hay cce o NumRegIdTrib.";
                    $this->codigo = "133 ".$this->status;
                    return false;
            }
            if ($ResidenciaFiscal!=null && $NumRegIdTrib==null) {
                $this->status = "CFDI135; El atributo cfdi:Comprobante.Receptor.NumRegIdTrib debe de existir si hay ResidenciaFiscal.";
                $this->codigo = "135 ".$this->status;
                return false;
            }
            if ($hay_cce && $NumRegIdTrib==null) {
                $this->status = "CFDI136; El atributo cfdi:Comprobante.Receptor.NumRegIdTrib debe de existir si hay cce.";
                $this->codigo = "136 ".$this->status;
                return false;
            }
            // }}}
        } else {
            // {{{ NO son genericos, deben de estar en l_rfc
            if (sizeof($row)==0) {
                $this->status = "CFDI131; El atributo cfdi:Comprobante.Receptor.rfc no es válido según la lista de RFC inscritos no cancelados en el SAT (l_RFC).";
                $this->codigo = "131 ".$this->status;
                return false;
            }
            if ($ResidenciaFiscal!=null) {
                $this->status = "CFDI132; El atributo cfdi:Comprobante.Receptor.ResidenciaFiscal no debe de existir para Rfc nacional.";
                $this->codigo = "132 ".$this->status;
                return false;
            }
            if ($NumRegIdTrib!=null) {
                $this->status = "CFDI134; El atributo cfdi:Comprobante.Receptor.NumRegIdTrib NO debe de existir para Rfc nacional.";
                $this->codigo = "134 ".$this->status;
                return false;
            }
            // }}}
        }
        if ($this->Cuenta_Catalogo("c_Taxid",$ResidenciaFiscal)>0) { 
            // Si hay registros , busca en catalogo
            $ok = $this->Checa_Catalogo("c_Taxid",$NumRegIdTrib,$ResidenciaFiscal);
            if (!$ok) {
                $this->status = "CFDI137; El valor del atributo NumRegIdTrib no es valido. No esta en lista c_Taxid";
                $this->codigo = "137 ".$this->status;
                return false;
            }
        } elseif ($regex_taxid!="") { // Valida solo formato, no en lista
            $aux = "/$regex_taxid/";
            $ok = preg_match($aux,$NumRegIdTrib);
            if (!$ok) {
                $this->status = "CFDI138; El valor del atributo NumRegIdTrib no es valido. No coincide con formato $regex_taxid).";
                $this->codigo = "138 ".$this->status;
                return false;
            }
        }
        $UsoCFDI = $Receptor->getAttribute("UsoCFDI");
        $uso = (strlen($rfcReceptor)==13) ? "c_usoCFDIFisica" : "c_usoCFDIMoral";
        $ok = $this->Checa_Catalogo($uso, $UsoCFDI);
        if (!$ok) {
            $this->status = "CFDI139; El valor del atributo UsoCFDI no cumple con un valor del catalogo $uso";
            $this->codigo = "139 ".$this->status;
            return false;
        }
        // }}} Receptor
        // {{{ Conceptos
        $dec_moneda = (int)$c_Moneda["decimales"];
        $acum_rete = array(); $acum_tras=array();
        for ($i=0; $i<$nb_Conceptos; $i++) {
            $Concepto = $Conceptos->item($i);
            $ClaveProdServ = $Concepto->getAttribute("ClaveProdServ");
            $c_ProdServ = $this->Obten_Catalogo("c_ClaveProdServ", $ClaveProdServ);
            if (sizeof($c_ProdServ) == 0) {
                $this->status = "CFDI150; El valor de ClaveProdServ ($ClaveProdServ) no existe en el catalogo c_ClaveProdServ";
                $this->codigo = "150 ".$this->status;
                return false;
            }
            $ValorUnitario = $Concepto->getAttribute("ValorUnitario");
            $dec_valor = $this->cantidad_decimales($ValorUnitario);
            if ($dec_valor != $dec_moneda) {
                $this->status = "CFDI155; La cantidad de decimales del ValorUnitario($dec_valor) no es igual a las que soporta la Moneda ($dec_moneda).";
                $this->codigo = "155 ".$this->status;
                return false;
            }
            if ( ($TipoDeComprobante=="I" || $TipoDeComprobante=="E" || $TipoDeComprobante=="N") && (double)$ValorUnitario <= 0) {
                $this->status = "CFDI156; Si el TipoDeCompriobante I, E o N el ValorUnitario debe de ser mayor que cero.";
                $this->codigo = "156 ".$this->status;
                return false;
            }
            $Cantidad = $Concepto->getAttribute("Cantidad");
            $dec_cant = $this->cantidad_decimales($Cantidad);
            $Importe = $Concepto->getAttribute("Importe");
            $dec_impo = $this->cantidad_decimales($Importe);
            if ($dec_impo != $dec_moneda) {
                $this->status = "CFDI160; La cantidad de decimales del Importe($dec_impo) no es igual a las que soporta la Moneda ($dec_moneda).";
                $this->codigo = "160 ".$this->status;
                return false;
            }
            $inf = round($Cantidad - pow(10,-1*$dec_cant)/2*($ValorUnitario - pow(10,-1*$dec_valor)/2),$dec_moneda,PHP_ROUND_HALF_DOWN);
            $sup = round(($Cantidad + pow(10,-1*$dec_cant)/2-pow(10,-12))*($ValorUnitario + pow(10,-1 * $dec_valor)/2)-pow(10,-12),$dec_moneda,PHP_ROUND_HALF_UP);
            $impo = (double)$Importe;
            if ($impo < $inf || $impo > $sup) {
                $this->status = "CFDI161; El importe esta fuera de los limites, inferior ($inf) o superior($sup).";
                $this->codigo = "161 ".$this->status;
                return false;
            }
            $Descuento = $Concepto->getAttribute("Descuento");
            if ((double)$Descuento > $impo) {
                $this->status = "CFDI165; El Descuento ($Descuento) debe de ser menor o igual que el Importe ($impo).";
                $this->codigo = "165 ".$this->status;
                return false;
            }
            $Impuestos = $Concepto->getElementsByTagName('Impuestos');
            if ($Impuestos->length>0) {
                $Traslados = $Impuestos->item(0)->getElementsByTagName('Traslado');
                $Retenciones = $Impuestos->item(0)->getElementsByTagName('Retencion');
                if ($Traslados->length==0 && $Retenciones->length==0) {
                    $this->status = "CFDI170; Debe de existir al menos uno de los nodos hijos: Traslado o Retencion,";
                    $this->codigo = "170 ".$this->status;
                    return false;
                }
                for ($j=0; $j<$Traslados->length; $j++) {
                    $Traslado=$Traslados->item($j);
                    $Base = $Traslado->getAttribute("Base");
                    $dec_base = $this->cantidad_decimales($Base);
                    $Impuesto = $Traslado->getAttribute("Impuesto");
                    $TipoFactor = $Traslado->getAttribute("TipoFactor");
                    $TasaOCuota = $Traslado->getAttribute("TasaOCuota");
                    $i_Importe = $Traslado->getAttribute("Importe");
                    if ($TipoFactor=="Tasa") {
                        if ($dec_base > $dec_moneda) {
                            $this->status = "CFDI171; La Base no puede tener mas ($dec_base) decimales que los que soporta la Moneda ($dec_moneda).";
                            $this->codigo = "171 ".$this->status;
                            return false;
                        }
                        if ((double)$Base > (double)$Importe) {
                            $this->status = "CFDI172; La Base ($Base) no puede ser mayor que el Importe del Concepto ($Importe).";
                            $this->codigo = "172 ".$this->status;
                            return false;
                        }
                    }
                    if ((double)$Base<=0) {
                        $this->status = "CFDI173; La Base del Impuesto Trasladado debe de ser mayor que cero.";
                        $this->codigo = "173 ".$this->status;
                        return false;
                    }
                    if ($TipoFactor=="Exento") {
                        if ($TasaOCuota != null || $i_Importe != null) {
                            $this->status = "CFDI175; Si TipoFactor es Exento no se deben registrar TasaOCuota ($TasaOCuota) ni Importe ($i_Importe).";
                            $this->codigo = "175 ".$this->status;
                            return false;
                        }
                    }
                    if ($TipoFactor=="Tasa" || $TipoFactor=="Cuota") {
                        if ($TasaOCuota == null || $i_Importe == null) {
                            $this->status = "CFDI176; Si TipoFactor es Tasa o Cuota se deben registrar TasaOCuota ($TasaOCuota) e Importe ($i_Importe).";
                            $this->codigo = "176 ".$this->status;
                            return false;
                        }
                        $row = $this->Obten_Catalogo("c_TasaOCuota",$TasaOCuota,$Impuesto,$TipoFactor);
                        if (sizeof($row) == 0) {
                            $this->status = "CFDI177; No existe un registro en el catalogo c_TasaOCuota para una TasaOCuota='$TasaOCuota', Impuesto='$Impuesto' y TipoFactor='$TipoFactor'.";
                            $this->codigo = "177 ".$this->status;
                            return false;
                        }
                    }
                    if ($i_Importe != null) {
                        $inf = round(($Base - pow(10,-1*$dec_base)/2)*$TasaOCuota,$dec_moneda,PHP_ROUND_HALF_DOWN);
                        $sup = round(($Base + pow(10,-1*$dec_base)/2-pow(10,-12))*$TasaOCuota,$dec_moneda,PHP_ROUND_HALF_UP);
                        $impo = (double)$i_Importe;
                        if ($impo < $inf || $impo > $sup) {
                            $this->status = "CFDI180; El importe ($impo) esta fuera de los limites, inferior ($inf) o superior($sup). (Decimales de la base $dec_base)";
                            $this->codigo = "180 ".$this->status;
                            return false;
                        }
                    }
                    $llave=$Impuesto.$TasaOCuota;
                    if (!array_key_exists($Impuesto,$acum_tras)) $acum_tras[$llave] = 0;
                    $acum_tras[$llave] += $i_Importe;
                }
                for ($j=0; $j<$Retenciones->length; $j++) {
                    $Retencion=$Retenciones->item($j);
                    $Base = $Retencion->getAttribute("Base");
                    $dec_base = $this->cantidad_decimales($Base);
                    if ($dec_base > $dec_moneda) {
                        $this->status = "CFDI190; La Base no puede tener mas ($dec_base) decimales que los que soporta la Moneda ($dec_moneda).";
                        $this->codigo = "190 ".$this->status;
                        return false;
                    }
                    if ((double)$Base > (double)$Importe) {
                        $this->status = "CFDI191; La Base ($Base) no puede ser mayor que el Importe del Concepto ($Importe).";
                        $this->codigo = "191 ".$this->status;
                        return false;
                    }
                    if ((double)$Base<=0) {
                        $this->status = "CFDI192; La Base del Impuesto Retenido debe de ser mayor que cero.";
                        $this->codigo = "192 ".$this->status;
                        return false;
                    }
                    $Impuesto = $Retencion->getAttribute("Impuesto");
                    $TipoFactor = $Retencion->getAttribute("TipoFactor");
                    if ($TipoFactor=="Exento") {
                        $this->status = "CFDI195; El TipoFactor de Impuesto Retenido no puede ser Exento.";
                        $this->codigo = "195 ".$this->status;
                        return false;
                    }
                    $TasaOCuota = $Retencion->getAttribute("TasaOCuota");
                    $i_Importe = $Retencion->getAttribute("Importe");
                    $dec_impo = $this->cantidad_decimales($i_Importe);
                    if ($dec_impo > $dec_moneda) {
                        $this->status = "CFDI201; El importe del impuesto no puede tener mas ($dec_impo) decimales que los que soporta la Moneda ($dec_moneda).";
                        $this->codigo = "201 ".$this->status;
                        return false;
                    }
                    $inf = round(($Base - pow(10,-1*$dec_base)/2)*$TasaOCuota,$dec_moneda,PHP_ROUND_HALF_DOWN);
                    $sup = round(($Base + pow(10,-1*$dec_base)/2-pow(10,-12))*$TasaOCuota,$dec_moneda,PHP_ROUND_HALF_UP);
                    $impo = (double)$i_Importe;
                    if ($impo < $inf || $impo > $sup) {
                        $this->status = "CFDI202; El importe ($impo) esta fuera de los limites, inferior ($inf) o superior($sup).";
                        $this->codigo = "202 ".$this->status;
                        return false;
                    }
                    if (!array_key_exists($Impuesto,$acum_rete)) $acum_rete[$Impuesto] = 0;
                    $acum_rete[$Impuesto] += $impo;
                }
            }
            $info = $Concepto->getElementsByTagName('InformacionAduanera');
            if ($info->length>0) {
                for ($j=0; $j<$info->length; $j++) {
                    $InformacionAduanera=$info->item($j);
                    // Para que solo tome InformacionAduanera a nivel 
                    // concepto y no a nivel Parte
                    if (!$InformacionAduanera->parentNode->isSameNode($Concepto)) continue;
                    $NumeroPedimento = $InformacionAduanera->getAttribute("NumeroPedimento");
                    if ($hay_cce && $NumeroPedimento != null) {
                        $this->status = "CFDI210; No se debe de registrar NumeroPedimento cuando el CFDI tiene complemento de comercio exterior.";
                        $this->codigo = "210 ".$this->status;
                        return false;
                    } elseif ($NumeroPedimento != null) {
                        $err= $this->valida_pedimento($NumeroPedimento);
                        switch ($err) {
                        case 0:
                            break; // Todo correcto
                        case 1:
                            $this->status = "CFDI211; Los primeros dos digitos del pedimento debe de ser de los ultimos 10 años ($NumeroPedimento).";
                            $this->codigo = "211 ".$this->status;
                            return false;
                        case 2:
                            $this->status = "CFDI212; Las posiciones 5 y 6 no existe en el catalogo c_Aduanas. ($NumeroPedimento).";
                            $this->codigo = "212 ".$this->status;
                            return false;
                        case 3:
                            $this->status = "CFDI213; Las posiciones 9 a la 12 no existe en el catalogo c_PatenteAduanal. ($NumeroPedimento).";
                            $this->codigo = "213 ".$this->status;
                            return false;
                        case 4:
                            $this->status = "CFDI214; No existe c_PedimentoAduana para ese anio, aduana, patente. ($NumeroPedimento).";
                            $this->codigo = "214 ".$this->status;
                            return false;
                        case 5:
                            $this->status = "CFDI215; Los ultimos 6 digitos no esta en el rango del catalogo c_NumPedimentoAduana. ($NumeroPedimento).";
                            $this->codigo = "215 ".$this->status;
                            return false;
                        default:
                            $this->status = "CFDI999; Error no definido ($err) al validar el pedimento ($NumeroPedimento).";
                            $this->codigo = "999 ".$this->status;
                            return false;
                        }
                    } // Validacion de Pedimento de concepto
                } // Para cada informacion
            } // Hay Informacion aduanera en el Concepto
            $Partes = $Concepto->getElementsByTagName('Parte');
            if ($Partes->length>0) {
                for ($j=0; $j<$Partes->length; $j++) {
                    $Parte=$Partes->item($j);
                    $ValorUnitario = $Parte->getAttribute('ValorUnitario');
                    $Importe = $Parte->getAttribute('Importe');
                    if ($ValorUnitario!= "" || $Importe != "") {
                        $dec_valor = $this->cantidad_decimales($ValorUnitario);
                        if ($dec_valor > $dec_moneda) {
                            $this->status = "CFDI220; La cantidad de decimales del ValorUniario ($dec_valor) es mayor a las que soporta la Moneda ($dec_moneda).";
                            $this->codigo = "220 ".$this->status;
                            return false;
                        }
                        if ((double)$ValorUnitario <= 0) {
                            $this->status = "CFDI221; El ValorUnitario debe de ser mayor que cero ($ValorUnitario).";
                            $this->codigo = "221 ".$this->status;
                            return false;
                        }
                        $dec_impo = $this->cantidad_decimales($Importe);
                        if ($dec_impo > $dec_moneda) {
                            $this->status = "CFDI222; La cantidad de decimales del Importe($dec_impo) es mayor a las que soporta la Moneda ($dec_moneda).";
                            $this->codigo = "222 ".$this->status;
                            return false;
                        }
                        $Cantidad = $Parte->getAttribute('Cantidad');
                        $dec_cant = $this->cantidad_decimales($Cantidad);
                        $inf = round($Cantidad - pow(10,-1*$dec_cant)/2*($ValorUnitario - pow(10,-1*$dec_valor)/2),$dec_moneda,PHP_ROUND_HALF_DOWN);
                        $sup = round(($Cantidad + pow(10,-1*$dec_cant)/2-pow(10,-12))*($ValorUnitario + pow(10,-1 * $dec_valor)/2)-pow(10,-12),$dec_moneda,PHP_ROUND_HALF_UP);
                        $impo = (double)$Importe;
                        if ($impo < $inf || $impo > $sup) {
                            $this->status = "CFDI223; El importe esta fuera de los limites, inferior ($inf) o superior($sup).";
                            $this->codigo = "223 ".$this->status;
                            return false;
                        }
                    }
                    $info = $Parte->getElementsByTagName('InformacionAduanera');
                    if ($info->length>0) {
                        for ($j=0; $j<$info->length; $j++) {
                            $InformacionAduanera=$info->item($j);
                            $NumeroPedimento = $InformacionAduanera->getAttribute("NumeroPedimento");
                            if ($hay_cce && $NumeroPedimento != null) {
                                $this->status = "CFDI230; No se debe de registrar NumeroPedimento cuando el CFDI tiene complemento de comercio exterior.";
                                $this->codigo = "230 ".$this->status;
                                return false;
                            } elseif ($NumeroPedimento != null) {
                                $err= $this->valida_pedimento($NumeroPedimento);
                                switch ($err) {
                                case 0:
                                    break; // Todo correcto
                                case 1:
                                    $this->status = "CFDI231; Los primeros dos digitos del pedimento debe de ser de los ultimos 10 años ($NumeroPedimento).";
                                    $this->codigo = "231 ".$this->status;
                                    return false;
                                case 2:
                                    $this->status = "CFDI232; Las posiciones 5 y 6 no existe en el catalogo c_Aduanas. ($NumeroPedimento).";
                                    $this->codigo = "232 ".$this->status;
                                    return false;
                                case 3:
                                    $this->status = "CFDI233; Las posiciones 9 a la 12 no existe en el catalogo c_PatenteAduanal. ($NumeroPedimento).";
                                    $this->codigo = "233 ".$this->status;
                                    return false;
                                case 4:
                                    $this->status = "CFDI234; No existe c_PedimentoAduana para ese anio, aduana, patente. ($NumeroPedimento).";
                                    $this->codigo = "234 ".$this->status;
                                    return false;
                                case 5:
                                    $this->status = "CFDI235; Los ultimos 6 digitos no esta en el rango del catalogo c_NumPedimentoAduana. ($NumeroPedimento).";
                                    $this->codigo = "235 ".$this->status;
                                    return false;
                                default:
                                    $this->status = "CFDI999; Error no definido ($err) al validar el pedimento ($NumeroPedimento).";
                                    $this->codigo = "999 ".$this->status;
                                    return false;
                                }
                            } // Valida pedimento de parte
                        } // para cada informacion aduanera
                    } // SI hay Informacion aduanera
                } // Para cada Parte
            } // Hay Partes
        } // Para cada concepto
        // }}} Conceptos
        // {{{ Impuestos
        $Impuestos = $Comprobante->getElementsByTagName('Impuestos');
        foreach ($Impuestos as $Impuesto) {
            // Para que solo tome Impuestos a nivel comprobante y no a
            // nivel Concepto
            if (!$Impuesto->parentNode->isSameNode($Comprobante)) continue;
            //
            if ($TipoDeComprobante=="T" || $TipoDeComprobante=="P") {
                $this->status = "CFDI235; Se debe de omitir el elemento Impuestos cuando TipoDeComprobante es T o P.";
                $this->codigo = "235 ".$this->status;
                return false;
            }
            $TotalImpuestosRetenidos=$Impuesto->getAttribute("TotalImpuestosRetenidos");
            $dec_impo = $this->cantidad_decimales($TotalImpuestosRetenidos);
            if ($dec_impo > $dec_moneda) {
                $this->status = "CFDI236; El importe de TotalImpuestosRetenidos debe de tener maximo los decimales que soporta la Moneda.";
                $this->codigo = "236 ".$this->status;
                return false;
            }
            $TotalImpuestosTrasladados=$Impuesto->getAttribute("TotalImpuestosTrasladados");
            $dec_impo = $this->cantidad_decimales($TotalImpuestosTrasladados);
            if ($dec_impo > $dec_moneda) {
                $this->status = "CFDI238; El importe de TotalImpuestosTrasladados debe de tener maximo los decimales que soporta la Moneda.";
                $this->codigo = "238 ".$this->status;
                return false;
            }
            $t_rete=0; $t_tras=0;
            $rete = array();
            $Retenciones = $Impuesto->getElementsByTagName('Retencion');
            foreach ($Retenciones as $Retencion) {
                $impu=$Retencion->getAttribute("Impuesto");
                $impo=$Retencion->getAttribute("Importe");
                if (array_key_exists($impu,$rete)) {
                    $this->status = "CFDI240; Debe de haber un solo registro de Impuesto:Retencion para el impuesto ($impu).";
                    $this->codigo = "240 ".$this->status;
                    return false;
                }
                $rete[$impu]=$impo;
                $t_rete += (double)$impo;
                if ($TotalImpuestosRetenidos==null) {
                    $this->status = "CFDI241; Si hay Impuestos:Retencion debe de exitir TotalImpuestosRetenidos.";
                    $this->codigo = "241 ".$this->status;
                    return false;
                }
                $dec_impo = $this->cantidad_decimales($impo);
                if ($dec_impo > $dec_moneda) {
                    $this->status = "CFDI242; El importe del Impuesto Retencion debe de tener maximo los decimales que soporta la Moneda.";
                    $this->codigo = "242 ".$this->status;
                    return false;
                }
                if (!array_key_exists($impu,$acum_rete) ||
                    abs($acum_rete[$impu]-$impo)>0.001) {
                    $this->status = "CFDI243; Comprobante:Impuestos:Retencion:Importe ($impo) debe de ser la suma de Conceptos:Retencion:Importe ".$acum_rete[$impu].".";
                    $this->codigo = "243 ".$this->status;
                    return false;
                }
            }
            if (abs($t_rete-(double)$TotalImpuestosRetenidos)>0.001) {
                $this->status = "CFDI237 EL valor del Atribute TotalImpuestosRetenidos ($TotalImpuestosRetenidos) debe de ser la suma de los atributos hijos Retencion:Importe ($t_rete).";
                $this->codigo = "237 ".$this->status;
                return false;
            }
            $tras = array();
            $Traslados = $Impuesto->getElementsByTagName('Traslado');
            foreach ($Traslados as $Traslado) {
                $impu=$Traslado->getAttribute("Impuesto");
                $TasaOCuota=$Traslado->getAttribute("TasaOCuota");
                $TipoFactor=$Traslado->getAttribute("TipoFactor");
                $llave=$impu.$TasaOCuota;
                if (array_key_exists($llave,$tras)) {
                    $this->status = "CFDI245; Debe de haber un solo registro de Impuesto:Traslado para el impuesto ($impu) y TasaOCuota ($TasaOCuota).";
                    $this->codigo = "245 ".$this->status;
                    return false;
                }
                $row = $this->Obten_Catalogo("c_TasaOCuota",$TasaOCuota,$impu,$TipoFactor);
                if (sizeof($row) == 0) {
                    $this->status = "CFDI246; No existe un registro en el catalogo c_TasaOCuota para una TasaOCuota='$TasaOCuota', Impuesto='$impu' y TipoFactor='$TipoFactor'.";
                    $this->codigo = "246 ".$this->status;
                    return false;
                }
                $impo=$Traslado->getAttribute("Importe");
                $tras[$llave]=$impo;
                $t_tras += (double)$impo;
                if ($TotalImpuestosTrasladados==null) {
                    $this->status = "CFDI250; Si hay Impuestos:Traslado debe de exitir TotalImpuestosTrasladados.";
                    $this->codigo = "250 ".$this->status;
                    return false;
                }
                $dec_impo = $this->cantidad_decimales($impo);
                if ($dec_impo > $dec_moneda) {
                    $this->status = "CFDI251; El importe del Impuesto Traslado debe de tener maximo los decimales que soporta la Moneda.";
                    $this->codigo = "251 ".$this->status;
                    return false;
                }
                if (!array_key_exists($llave,$acum_tras) ||
                    abs($acum_tras[$llave]-$impo)>0.001) {
                    $this->status = "CFDI252; Comprobante:Impuestos:Traslado:Importe ($impo) debe de ser la suma de Conceptos:Traslado:Importe (llave=$llave).";
                    $this->codigo = "252 ".$this->status;
                    return false;
                }
            }
            if (abs($t_tras-$TotalImpuestosTrasladados)>0.001) {
                $this->status = "CFDI239 EL valor del Atribute TotalImpuestosTrasladados ($TotalImpuestosTrasladados) debe de ser la suma de los atributos hijos Traslado:Importe ($t_tras).";
                $this->codigo = "239 ".$this->status;
                return false;
            }
            //
        }
        // }}} Impuestos
        $this->status = "CFDI0 Validacion correcta semantica cfdi 3.3";
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
    // {{{ Oeten_Catalogo
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
    // {{{ cuenta_l_rfc
    private function cuenta_l_rfc() {
        $cant= $this->conn->GetOne("select count(*) from pac_l_rfc");
        $this->cuenta = $cant;
    }
    // }}}
    // {{{ cantidad_decimales
    private function cantidad_decimales($impo) {
        @list($ent,$dec) = @explode(".",$impo);
        return strlen($dec);
    }
    // }}}
    // {{{ valida_pedimento
    private function valida_pedimento($pedi) {
        $err = 0;
        $anio = substr($pedi,0,2); // 1 y 2
        $adua = substr($pedi,4,2); // 5 y 6
        $pate = substr($pedi,8,4); // 9 a la 12
        $cant = substr($pedi,-6); // Ultimos 6 digitos
        $max = date("y"); $min = $max - 10;
        if ($anio < $min || $anio > $max) return 1;
        $ok = $this->Checa_Catalogo("c_Aduanas", $adua);
        if (!$ok) return 2;
        $ok = $this->Checa_Catalogo("c_PatenteAduanal", $pate);
        if (!$ok) return 3;
        $reg = $this->Obten_Catalogo("c_NumPedimentoAduana", $adua, $pate, $anio+2000);
        if (sizeof($reg) == 0) return 4;
        $ultimo = $reg["decimales"];
        if ($cant > $ultimo) return 5;
        return $err;
    }
    // }}}
}
