<?php
// Valida semantica del CFDI (actualmente solo 3.3)
class Sem_CFDI {
    var $xml_cfd;
    var $con;
    var $codigo;
    var $status;
    function valida($xml_cfd,$conn) {
    // {{{ valida : nodo Comprobante
        $ok = true;
        $this->xml_cfd = $xml_cfd;
        $this->conn = $conn;
        $Comprobante = $this->xml_cfd->getElementsByTagName('Comprobante')->item(0);
        $version = $Comprobante->getAttribute("version");
        if ($version==null) $version = $Comprobante->getAttribute("Version");
        if ($version == "3.2") {
            $this->status = "No se valida semantica en CFDI version 3.2";
            $this->codigo = "0 ".$this->status;
            return true;
        }
        if ($version != "3.3") {
            $this->status = "El valor del atributo cfdi:Comprobante:Version debe de ser 3.3";
            $this->codigo = "140 ".$this->status;
            return false;
        }
        $Fecha = $Comprobante->getAttribute("Fecha");
        $a_Fecha = new DateTime($Fecha);
        $a_hoy = new DateTime();
        // $margen_inf = new DateInterval("PT1H");
        $margen_inf = new DateInterval("P1Y");
        $a_inf = date_sub($a_hoy, $margen_inf);
        $margen_sup = new DateInterval("PT1H");
        $a_hoy = new DateTime();
        $a_sup = date_add($a_hoy, $margen_sup);
        if ($a_Fecha < $a_inf) {
            $this->status = "La fecha ".$a_Fecha->format("c")." es inferior al limite (".$a_inf->format("c").")";
            $this->codigo = "101 ".$this->status;
            return false;
        }
        if ($a_Fecha > $a_sup) {
            $this->status = "La fecha ".$a_Fecha->format("c")." es superior al limite (".$a_sup->format("c").")";
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
                    $this->status = "El Descuento del Concepto se debe de omitir si el tipo de comprpbante es T o P.";
                    $this->codigo = "113 ".$this->status;
                    return false;
                }
            }
            if ($c_Descuento != null) $hay_Descuento=true;
            $Impuestos = $Concepto->getElementsByTagName("Impuestos");
            if ($Impuestos->length > 0) {
                if ($TipoDeComprobante=="T" || $TipoDeComprobante=="P" ||
                    $TipoDeComprobante=="N") {
                    $this->status = "Se debe de omitir ell elemento Impuestos cuando TipoDeComprobante es T, P o N.";
                    $this->codigo = "114 ".$this->status;
                    return false;
                }
                $Traslados = $Impuestos->item(0)->getElementsByTagName("Traslado");
                foreach ($Traslados as $Traslado) {
                    $t_impuestos -= (double)$Traslado->getAttribute("Impuesto");
                }
                $Retenciones = $Impuestos->item(0)->getElementsByTagName("Retencion");
                foreach ($Retenciones as $Retencion) {
                    $t_impuestos += (double)$Retencion->getAttribute("Impuesto");
                }
            }
        }
        $SubTotal = $Comprobante->getAttribute("SubTotal");
        if ($TipoDeComprobante=="I" || $TipoDeComprobante=="E" ||
            $TipoDeComprobante=="N") {
                if ($SubTotal != $t_Importe) {
                    $this->status = "El SubTotal ($SubTotal) no es igual a la suma ($t_Importe) del Importe de los Conceptos.";
                    $this->codigo = "103 ".$this->status;
                    return false;
                }
        } elseif ($TipoDeComprobante=="T" || $TipoDeComprobante=="P") {
                if ($SubTotal != 0) {
                    $this->status = "El SubTotal ($SubTotal) debe de tener el valoer cero.";
                    $this->codigo = "104 ".$this->status;
                    return false;
                }
        }
        $Descuento = $Comprobante->getAttribute("Descuento");
        if ($Descuento > $SubTotal) {
            $this->status = "El Descuento ($Descuento) no debe de ser mayor que el SubTotal ($SubTotal).";
            $this->codigo = "105 ".$this->status;
            return false;
        }
        if ($TipoDeComprobante=="I" || $TipoDeComprobante=="E" ||
            $TipoDeComprobante=="N") {
             if ($hay_Descuento) {
                if ($Descuento != $t_Descuento) {
                    $this->status = "El Descuento ($Descuento) no es igual a la suma ($t_Descuentos) de los Descuentos de los Conceptos.";
                    $this->codigo = "106 ".$this->status;
                    return false;
                }
             } else { // No hay descuentos
                if ($Descuento != null) {
                    $this->status = "El Descuento en el comprobante ($Descuento) no debe de existir si no hay Descuentos en los Conceptos.";
                    $this->codigo = "107 ".$this->status;
                    return false;
                }
             }
        }
        $Moneda = $Comprobante->getAttribute("Moneda");
        $TipoCambio = $Comprobante->getAttribute("TipoCambio");
        if ($Moneda=="XXX") {
            if ($TipoCambio != null) {
                $this->status = "El TipoCambio no debe de existir si la Moneda es igual a 'XXX'.";
                $this->codigo = "108 ".$this->status;
                return false;
            }
        } elseif ($Moneda=="MXN") {
            if ($TipoCambio != null&& $TipoCambio != "1") {
                $this->status = "El TipoCambio puedo no existir, pero si existe debe de tener el valor '1'";
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
                $this->status = "El Total ($Total) debe de ser cero si el TipoDeCoprobante es T o P.";
                $this->codigo = "110 ".$this->status;
                return false;
            }
        } else {
            $aux = $SubTotal - $Descuentos + $t_impuestos;
            if ($Total != $aux) {
                $this->status = "El Total ($Total) debe de ser igual que la suma del SubTotal menos Descuentos mas los impuestos ($aux).";
                $this->codigo = "111 ".$this->status;
                return false;
            }
        }
        $CondicionesDePago = $Comprobante->getAttribute("CondicionesDePago");
        if ($TipoDeComprobante=="T" || $TipoDeComprobante=="P" ||
            $TipoDeComprobante=="N") {
            if ($CondicionesDePago != null) {
                $this->status = "Se debe de omitir CondicionesDePago cuando el TipoDeComproibantes es T, P o N.";
                $this->codigo = "112 ".$this->status;
                return false;
            }
        } 
        $FormaPago = $Comprobante->getAttribute("FormaPago");
        $MetodoPago = $Comprobante->getAttribute("MetodoPago");
        if ($TipoDeComprobante=="T" || $TipoDeComprobante=="P") {
            if ($FormaPago != null || $MetodoPago != null) {
                $this->status = "Se deben de omitir FormaPago y MetodoPago cuando el TipoDeComproibantes es T o P.";
                $this->codigo = "115 ".$this->status;
                return false;
            }
        } 
        $hay_pagos = false;
        $Complemento = $Comprobante->getElementsByTagName("Complemento");
        if ($Complemento->length > 0) {
            $Pagos = $Complemento->item(0)->getElementsByTagName("Pagos");
            if ($Pagos->length > 0) {
                $hay_pagos = true;
            }
        }
        if ($MetodoPago == "Pago inicial y parcialidades" && !$hay_pagos) {
            $this->status = "Si hay ";
            $this->codigo = "115 ".$this->status;
            return false;
        }
        $LugarExpedicion = $Comprobante->getAttribute("LugarExpedicion");
        $Confirmacion = $Comprobante->getAttribute("Confirmacion");
        /* TODO: Validar contra coidigos de autorizacion */
        // }}} Comprobante
        // {{{ Emisor
        // }}} Emisor
        // {{{ Receptor
        // }}} Receptor
        // {{{ Conceptos
        // }}} Conceptos
        // {{{ Impuestos
        // }}} Impuestos
        return $ok;
    }
}
