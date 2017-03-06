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
        $this->status = "Inicia Validacion de semantica pagos 1.0";
        $this->codigo = "0 ".$this->status;
        $Comprobante = $this->xml_cfd->getElementsByTagName('Comprobante')->item(0);
        $Emisor = $this->xml_cfd->getElementsByTagName('Emisor')->item(0);
        $Receptor = $this->xml_cfd->getElementsByTagName('Receptor')->item(0);
        $version = $Comprobante->getAttribute("version");
        if ($version==null) {
            $version = $Comprobante->getAttribute("Version");
        }
        if ($version != "3.3" && $version != "3.2") {
                $this->status = "PAG100 El atributo cfdi:Comprobante:version no tiene un valor valido.";
                $this->codigo = "100 ".$this->status;
                return false;
        }
        $pagos = $Comprobante->getElementsByTagName('Pagos')->item(0);
        $Complemento = $Comprobante->getElementsByTagName('Complemento')->item(0);
        $pago_version = $pagos->getAttribute("Version");
        $Pagos = $pagos->getElementsByTagName('Pago');
        $nb_Pagos = $Pagos->length;
        // }}}
        if ($version == "3.2") {
            // {{{ Valida Comprobante 3.2
            $fecha = $Comprobante->getAttribute("fecha");
            $regex = "(20[1-9][0-9])-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])T(([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9])";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$fecha);
            if (!$ok) {
                $this->status = "PAG101 El atributo cfdi:Comprobante:fecha no cumple con el patrón requerido.";
                $this->codigo = "101 ".$this->status;
                return false;
            }
            $noCertificado = $Comprobante->getAttribute("noCertificado");
            if (strlen($noCertificado)!=20) {
                $this->status = "PAG102 El atributo cfdi:Comprobante:noCertificado no es de 20 posiciones";
                $this->codigo = "102 ".$this->status;
                return false;
            }
            $formaDePago = $Comprobante->getAttribute("formaDePago");
            if ($formaDePago!="NA") {
                $this->status = "PAG103 El atributo cfdi:Comprobante:formaDePago no es de 'NA' ";
                $this->codigo = "103 ".$this->status;
                return false;
            }
            $CodigoPostal = $Comprobante->getAttribute("LugarExpedicion");
            $regex = "([0-9]{5})";
            $aux = "/^$regex$/A";
            $ok = preg_match($aux,$CodigoPostal);
            if ($ok) $ok = $this->Checa_Catalogo("c_CP", $CodigoPostal);
            if (!$ok) {
                $this->status = 'PAG104 El atributo cfdi:Comprobante:LugarExpedicion no cumple con alguno de los valores permitidos.';
                $this->codigo = "104 ".$this->status;
                return false;
            }
            $subTotal = $Comprobante->getAttribute("subTotal");
            if ($subTotal!=0) {
                $this->status = "PAG105 El atributo cfdi:Comprobante:subTotal no es = 0  ";
                $this->codigo = "105 ".$this->status;
                return false;
            }

            $total = $Comprobante->getAttribute("total");
            if ($total!=0) {
                $this->status = "PAG106 El atributo cfdi:Comprobante:total no es = 0  ";
                $this->codigo = "106 ".$this->status;
                return false;
            }

            $tipoDeComprobante = $Comprobante->getAttribute("tipoDeComprobante");
            if ($tipoDeComprobante!="ingreso") {
                $this->status = "PAG107 El atributo cfdi:Comprobante:tipoDeComprobante no es de 'ingreso'   ";
                $this->codigo = "107 ".$this->status;
                return false;
            }
            $metodoDePago = $Comprobante->getAttribute("metodoDePago");
            if ($metodoDePago!="pago") {
                $this->status = "PAG108 El atributo cfdi:Comprobante:metodoDePago no es de 'pago'   ";
                $this->codigo = "108 ".$this->status;
                return false;
            }

            $condicionesDePago = $Comprobante->getAttribute("condicionesDePago");
            $descuento = $Comprobante->getAttribute("descuento");
            $motivoDescuento = $Comprobante->getAttribute("motivoDescuento");
            $TipoCambio = $Comprobante->getAttribute("TipoCambio");
            $Moneda = $Comprobante->getAttribute("Moneda");
            $NumCtaPago = $Comprobante->getAttribute("NumCtaPago");
            $SerieFolioFiscalOrig = $Comprobante->getAttribute("SerieFolioFiscalOrig");
            $FechaFolioFiscalOrig = $Comprobante->getAttribute("FechaFolioFiscalOrig");
            $MontoFolioFiscalOrig = $Comprobante->getAttribute("MontoFolioFiscalOrig");
             $this->status = "PAG109 Los atributos de cfdi:Comprobante:";
            if ($condicionesDePago!="") $this->status.="condicionesDePago, ";
            if ($descuento!="") $this->status.="descuento, ";
            if ($motivoDescuento!="") $this->status.="motivoDescuento, ";
            if ($TipoCambio!="") $this->status.="TipoCambio, ";
            if ($Moneda!="") $this->status.="Moneda, ";
            if ($NumCtaPago!="") $this->status.="NumCtaPago, ";
            if ($SerieFolioFiscalOrig!="") $this->status.="SerieFolioFiscalOrig, ";
            if ($FechaFolioFiscalOrig!="") $this->status.="FechaFolioFiscalOrig, ";
            if ($MontoFolioFiscalOrig!="") $this->status.="MontoFolioFiscalOrig, ";
            if ($condicionesDePago!="" OR $descuento!="" OR $motivoDescuento!="" OR
                $TipoCambio!="" OR $Moneda!="" OR $NumCtaPago!="" OR 
                $SerieFolioFiscalOrig!="" OR $FechaFolioFiscalOrig!="" OR
                $MontoFolioFiscalOrig!="") {
                $this->codigo = "109 ".substr($this->status,0,-2);
                return false;
            }


            $DomicilioFiscal = $Emisor->getElementsByTagName('DomicilioFiscal');
            $ExpedidoEn = $Emisor->getElementsByTagName('ExpedidoEn');
            $Domicilio = $Receptor->getElementsByTagName('Domicilio');
            $this->status = "PAG110 Los atributos de cfdi:";
            if ($DomicilioFiscal->length != 0) $this->status.="Emisor:DomicilioFiscal, ";
            if ($ExpedidoEn->length != 0) $this->status.="Emisor:ExpedidoEn, ";
            if ($Domicilio->length != 0) $this->status.="Receptor:Domicilio, ";
            if ($DomicilioFiscal->length != 0 OR $ExpedidoEn->length != 0 OR 
                $Domicilio->length != 0) {
                $this->codigo = "110 ".substr($this->status,0,-2);
                return false;
            }
            $Impuestos = $Comprobante->getElementsByTagName('Impuestos')->item(0);
            $nodo = $Impuestos->childNodes->item(1);
            if ($nodo != NULL) {
                $name=$nodo->nodeName;
                $this->status = "PAG111 El nodo cfdi:Comprobante.Impuestos no cumple 1 la estructura.";
                $this->codigo = "111 ".$this->status;
                return false;
            }
            if ($Impuestos->hasAttributes())  {
                $this->status = "PAG111 El nodo cfdi:Comprobante.Impuestos no cumple 2 la estructura.";
                $this->codigo = "111 ".$this->status;
                return false;
            } // }}}

            $Conceptos = $Comprobante->getElementsByTagName('Concepto');
            if ($Conceptos->length != 1) {
                $this->status = "PAG112 El nodo Comprobante.Conceptos.Concepto,  1 Solo puede registrarse un nodo concepto, sin elementos hijo.";
                $this->codigo = "112 ".$this->status;
                return false;
            }
            $Concepto = $Conceptos->item(0);
            if ($Concepto->hasChildNodes()) {
                $this->status = "PAG112 El nodo Comprobante.Conceptos.Concepto, 2 Solo puede registrarse un nodo concepto, sin elementos hijo.";
                $this->codigo = "112 ".$this->status;
                return false;
            }
            $noIdentificacion = $Concepto->getAttribute("noIdentificacion");
            if ($noIdentificacion!="84111506") {
                $this->status = "PAG113 El nodo Comprobante.Conceptos.noIdentificacion, el valor debe de ser 84111506";
                $this->codigo = "113 ".$this->status;
                return false;
            }
            $cantidad = $Concepto->getAttribute("cantidad");
            if ($cantidad!="1") {
                $this->status = "PAG114 El nodo Comprobante.Conceptos.cantidad, el valor debe de ser 1 ";
                $this->codigo = "114 ".$this->status;
                return false;
            }
            $unidad = $Concepto->getAttribute("unidad");
            if ($unidad!="ACT") {
                $this->status = "PAG115 El nodo Comprobante.Conceptos.unidad, el valor debe de ser 'ACT' ";
                $this->codigo = "115 ".$this->status;
                return false;
            }
            $descripcion = $Concepto->getAttribute("descripcion");
            if ($descripcion!="Pago") {
                $this->status = "PAG116 El nodo Comprobante.Conceptos.descripcion, el valor debe de ser 'Pago' ";
                $this->codigo = "116 ".$this->status;
                return false;
            }

            $valorUnitario = $Concepto->getAttribute("valorUnitario");
            if ($valorUnitario!="0") {
                $this->status = "PAG117 El nodo Comprobante.Conceptos.valorUnitario, el valor debe de ser '0' ";
                $this->codigo = "117 ".$this->status;
                return false;
            }
            $Importe = $Concepto->getAttribute("Importe");
            if ($Importe!="0") {
                $this->status = "PAG118 El nodo Comprobante.Conceptos.Importe, el valor debe de ser '0' ";
                $this->codigo = "118 ".$this->status;
                return false;
            }
        }   // termina version 3.2
        //  ELEMENTOS PAGOS

        if ($pagos->parentNode->nodeName!="cfdi:Complemento") {
            $this->status = "PAG135 El nodo pago10:Pagos debe registrarse como un nodo hijo del nodo Complemento en el CFDI.";
            $this->codigo = "135 ".$this->status;
            return false;
        }

           $pagos_n = $Comprobante->getElementsByTagName('Pagos');
           if ($pagos_n->length >1 ) {
             $this->status = "PAG136 El nodo Comprobante.Complemento.Pagos, solo debe de existir un nodo Pagos en el CFDI  ";
             $this->codigo = "136 ".$this->status;
             return false;
           }

        if ($version == "3.3") {
           $TipoDeComprobante = $Comprobante->getAttribute("TipoDeComprobante");
          //echo "tipo=$TipoDeComprobante";
           if ($TipoDeComprobante=="T" || $TipoDeComprobante=="N") {
              if ($Pagos->length != 0) {
                $this->status = "PAG119; No debe de existir el complemento para pagos cuando el TipoDeComprobante es T o N.";
                 $this->codigo = "119 ".$this->status;
                 return false;
              }
           }
           if ($TipoDeComprobante=="I" || $TipoDeComprobante=="E") {
              $FormaPago = $Comprobante->getAttribute("FormaPago");
              if ($FormaPago != "") {
                 $this->status = "PAG120; No debe de existir el atributo CFDI:FormaPago ";
                 $this->codigo = "120 ".$this->status;
                 return false;
              }
              $MetodoPago = $Comprobante->getAttribute("MetodoPago");
              if ($MetodoPago != "") {
                 $this->status = "PAG120-2; No debe de existir el atributo CFDI:MetodoPago ";
                 $this->codigo = "120-2 ".$this->status;
                 return false;
              }
              $DoctoRelacionado = $Comprobante->getElementsByTagName('DoctoRelacionado');
              if ($DoctoRelacionado->length != 0) {
                 $this->status = "PAG120-3; No debe tener elemento hijo  DoctoRelacionado ";
                 $this->codigo = "120-3 ".$this->status;
                 return false;
              }
              $Impuestos = $pagos->getElementsByTagName('Impuestos');
              if ($Impuestos->length != 0) {
                 $this->status = "PAG120-4; No debe tener elemento hijo  Impuestos ";
                 $this->codigo = "120-4 ".$this->status;
                 return false;
              }
           } //fin  si es comprobante I o E

           if ($TipoDeComprobante=="P") {
               $Subtotal = $Comprobante->getAttribute("Subtotal");
               if ($Subtotal!="0") {
                 $this->status = "PAG121 ; El valor de Subtotal debe de ser 0  ";
                 $this->codigo = "121 ".$this->status;
                 return false;
               }
               $Moneda = $Comprobante->getAttribute("Moneda");
               if ($Moneda!="XXX") {
                 $this->status = "PAG122 ; El valor de Moneda debe de ser 'XXX'  ";
                 $this->codigo = "122 ".$this->status;
                 return false;
               }
               $FormaPago = $Comprobante->getAttribute("FormaPago");
               $MetodoPago = $Comprobante->getAttribute("MetodoPago");
               $CondicionesDePago = $Comprobante->getAttribute("CondicionesDePago");
               $Descuento = $Comprobante->getAttribute("Descuento");
               $TipoCambio = $Comprobante->getAttribute("TipoCambio");
                $this->status = "PAG123 Los atributos mencionados no se deben registrar en cfdi:Comprobante:";
               if ($FormaPago!="") $this->status.="FormaPago, ";
               if ($MetodoPago!="") $this->status.="MetodoPago, ";
               if ($CondicionesDePago!="") $this->status.="CondicionesDePago, ";
               if ($Descuento!="") $this->status.="Descuento, ";
               if ($TipoCambio!="") $this->status.="TipoCambio, ";
               if ($FormaPago!="" OR $MetodoPago!="" OR $CondicionesDePago!="" OR
                   $Descuento!="" OR $TipoCambio!="" ) {
                   $this->codigo = "123 ".substr($this->status,0,-2);
                   return false;
               }
               $Total = $Comprobante->getAttribute("Total");
               if ($Total!="0") {
                 $this->status = "PAG124 ; El valor de Total debe de ser '0'  ";
                 $this->codigo = "124 ".$this->status;
                 return false;
               }

              $Conceptos = $Comprobante->getElementsByTagName('Concepto');
              if ($Conceptos->length != 1) {
                  $this->status = "PAG125 El nodo Comprobante.Conceptos.Concepto,  1 Solo puede registrarse un nodo concepto, sin elementos hijo.";
                  $this->codigo = "125 ".$this->status;
                  return false;
              }
              $Concepto = $Conceptos->item(0);
              if ($Concepto->hasChildNodes()) {
                $this->status = "PAG125 El nodo Comprobante.Conceptos.Concepto, 2 Solo puede registrarse un nodo concepto, sin elementos hijo.";
                $this->codigo = "125 ".$this->status;
                return false;
              }
              $ClaveProdServ = $Concepto->getAttribute("ClaveProdServ");
              if ($ClaveProdServ!="84111506") {
                $this->status = "PAG126 El nodo Comprobante.Conceptos.Concepto.ClaveProdServ, el valor debe de ser '84111506' ";
                $this->codigo = "126 ".$this->status;
                return false;
              }
              $NoIdentificacion = $Concepto->getAttribute("NoIdentificacion");
              if ($NoIdentificacion!="") {
                $this->status = "PAG127 El nodo Comprobante.Conceptos.Concepto.NoIdentificacion, se debe omitir  ";
                $this->codigo = "127 ".$this->status;
                return false;
              }
              $Cantidad = $Concepto->getAttribute("Cantidad");
              if ($Cantidad!="1") {
                $this->status = "PAG128 El nodo Comprobante.Conceptos.Concepto.Cantidad, debe ser '1'  ";
                $this->codigo = "128 ".$this->status;
                return false;
              }
              $Unidad = $Concepto->getAttribute("Unidad");
              if ($Unidad!="ACT") {
                $this->status = "PAG129 El nodo Comprobante.Conceptos.Concepto.Unidad, debe ser 'ACT'  ";
                $this->codigo = "129 ".$this->status;
                return false;
              }
              $Descripcion = $Concepto->getAttribute("Descripcion");
              if ($Descripcion!="Pago") {
                $this->status = "PAG130 El nodo Comprobante.Conceptos.Concepto.Descripcion, debe ser 'Pago'  ";
                $this->codigo = "130 ".$this->status;
                return false;
              }
              $ValorUnitario = $Concepto->getAttribute("ValorUnitario");
              if ($ValorUnitario!="0") {
                $this->status = "PAG131 El nodo Comprobante.Conceptos.Concepto.ValorUnitario, debe ser '0'  ";
                $this->codigo = "131 ".$this->status;
                return false;
              }
              $Importe = $Concepto->getAttribute("Importe");
              if ($Importe!="0") {
                $this->status = "PAG132 El nodo Comprobante.Conceptos.Concepto.ValorImporte, debe ser '0'  ";
                $this->codigo = "132 ".$this->status;
                return false;
              }
              $Descuento = $Concepto->getAttribute("Descuento");
              if ($Descuento!="") {
                $this->status = "PAG133 El nodo Comprobante.Conceptos.Concepto.Descuento, se debe omitir  ";
                $this->codigo = "133 ".$this->status;
                return false;
              }
              $Impuestos = $Concepto->getElementsByTagName('Impuestos');
              if ($Impuestos->length > 0) {
                $this->status = "PAG134 El nodo Comprobante.Conceptos.Concepto.Impuestos, este nodo no se debe registrar en el CFDI  ";
                $this->codigo = "134 ".$this->status;
                return false;
              }

           } // si es comprobante P 
        } // termina version 3.3

        if ($version == "3.3" OR  $version == "3.2") {
           if ($TipoDeComprobante=="T" OR $TipoDeComprobante=="traslado") {
               $pagos_n = $Comprobante->getElementsByTagName('Pagos');
               if ($pagos_n->length !=0 ) {
                 $this->status = "PAG137 El nodo Comprobante.Complemento.Pagos, no debe de existir este complemento  ";
                 $this->codigo = "137 ".$this->status;
                 return false;
               }
           }
           if ($TipoDeComprobante=="I" OR $TipoDeComprobante=="ingreso" OR 
               $TipoDeComprobante=="E" OR $TipoDeComprobante=="egreso") {
               $pagos_n = $Comprobante->getElementsByTagName('Pagos');
               $Complemento_SPEI = $Comprobante->getElementsByTagName('Complemento_SPEI');
               $Nomina = $Comprobante->getElementsByTagName('Nomina');
               if ($pagos_n->length >0 AND ($Complemento_SPEI->length >0 OR $Nomina->length >0)) {
                 $this->status = "PAG138 El nodo Comprobante.Complemento.Pagos, no debe de coexistir  con el Complemento_SPEI y/o Nomina ";
                 $this->codigo = "138 ".$this->status;
                 return false;
               }
           }
        }
        if ($version == "3.3" AND $TipoDeComprobante=="P") {
           foreach ($Complemento->childNodes as $node) {
              if ($node->nodeType == XML_ELEMENT_NODE) {
                  if ($node->nodeName == "tfd:TimbreFiscalDigital" ||
                      $node->nodeName == "pago10:Pagos" ||
                      $node->nodeName == "cfdi:RegistroFiscal") {
                  } else {
                      $this->status = "PAG139 El nodo Comprobante.Complemento.Pagos solo puede coexistir con los complementos Timbre Fiscal Digital, CFDI registro fiscal.";
                      $this->codigo = "139 ".$this->status;
                      return false;
                  }
              }
           } // Buscar que complementos existen
        }
        $Fecha = new Datetime($Comprobante->getAttribute("Fecha"));
           //    echo "fechapago = ".$Fecha->format('d');
        foreach ($Pagos as $node) {
           $FechaPago = new Datetime($node->getAttribute("FechaPago"));
         //      echo "fechapago = ".$FechaPago->format('d');
           if ($FechaPago > $Fecha) {
               $this->status = "PAG140 El valor del atributo cfdi:Fecha no es menor o igual al valor del atributo FechaPago.";
               $this->codigo = "140 ".$this->status;
               return false;
           }
           if ($FechaPago < $Fecha) {
               $Ypago=$FechaPago->format('Y');
               $Mpago=$FechaPago->format('m');
               $Yfecha=$Fecha->format('Y');
               $Mfecha=$Fecha->format('m');
               $Dfecha=$Fecha->format('d');
               $YMpago=($Ypago * 100) + $Mpago;
               $YMfecha=($Yfecha * 100) + $Mfecha;
               if ($Mfecha==1){
                    $YMfecha2=(($Yfecha * 100) - 1) + 12 ; 
               }else{
                   $YMfecha2=($Yfecha * 100) + $Mfecha - 1 ;
               }
     //          echo 'YMpago'.$YMpago.'YMfecha'.$YMfecha.'YMfecha2'.$YMfecha2.'dfecha'.$Dfecha;
               if (($YMpago == $YMfecha) OR ($YMpago == $YMfecha2 AND $Dfecha <= 10)){
               }else{ 
                  $this->status = "PAG140-2 El valor aÃo-mes de Pago:FechaPago debe de ser igual al valor aÃ±o-mes de CFDI:Fech";
                  $this->codigo = "140-2 ".$this->status;
                  return false;
               }
           }
        } // fin foreach

         
        foreach ($Pagos as $node) {
           $FormaDePagoP = $node->getAttribute("FormaDePagoP");
           
           if ($FormaDePagoP == "99") {
              $this->status = " PAG141 El valor del atributo Complemento.Pagos.Pago.FormaDePagoP, debe de ser diferente de '99'  ";
              $this->codigo = "141".$this->status;
              return false;
           }
           $ok = $this->Checa_Catalogo("c_FormaDePagoP",$FormaDePagoP);
           if (!$ok) {
               $this->status = "PAG141-2 El valor del atributo Complemento.Pagos.Pago.FormaDePagoP no cumple con un valor del catalogo c_FormaDePagoP";
               $this->codigo = "141-2  ".$this->status;
               return false;
           }
           if ($FormaDePagoP == "01") {
              $this->status = " PAG141 El valor del atributo Complemento.Pagos.Pago.FormaDePagoP, debe de ser diferente de '99'  ";
              $this->codigo = "141".$this->status;
              return false;
           }
           $ok = $this->Checa_Catalogo("c_FormaDePagoP",$FormaDePagoP);
        }

         

        




        $this->status = "LLEGO HASTA ABAJO!!!!";
        $this->codigo = "0 ".$this->status;
        return false;
        $this->status = "Validacion de semantica pagos correcta";
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
