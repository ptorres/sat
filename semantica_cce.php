<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once("/var/www/html/pac/sat/utils/Constantes.php");
error_reporting(E_ALL);
class Cce {
    var $xml_cfd;
    var $con;
    var $codigo;
    var $status;
    // {{{ valida : semantica_cce
    public function valida($xml_cfd,$conn) {
        $ok = true;
        $this->xml_cfd = $xml_cfd;
        $this->conn = $conn;
        /// Validaciones del Comprobante CFDI 3.2
        $Comprobante = $this->xml_cfd->getElementsByTagName('Comprobante')->item(0);
        $Conceptos = $Comprobante->getElementsByTagName('Concepto');
        $nb_Conceptos = $Conceptos->length;
        $suma=0.0;
        for ($i=0; $i<$nb_Conceptos; $i++) {
            $Concepto = $Conceptos->item($i);
            $importe = (double)$Concepto->getAttribute("importe");
            $suma += $importe;
        }
        $subTotal = (double)$Comprobante->getAttribute("subTotal");
        if ($subTotal != $suma) {
            $this->status = "La suma de Concepto:importe debe de ser igual a Comprobante:subTotal";
            $this->codigo = "-2 ".$this->status;
            return false;
        }
        $cce = $Comprobante->getElementsByTagName('ComercioExterior')->item(0);
        // $this->conn->debug=true;
        $Moneda = $Comprobante->getAttribute("Moneda");
        $ok = $this->Checa_Catalogo("c_Moneda", $Moneda);
        if (!$ok) {
            $this->status = "Comprobante:Moneda es obligatoria y debe de existir en c_Moneda";
            $this->codigo = "-3 ".$this->status;
            return false;
        }
        // $this->conn->debug=false;
        $TipoCambio = (double)$Comprobante->getAttribute("TipoCambio");
        if ($TipoCambio==0) {
            $this->status = "Comprobante:TipoCambio es obligatoria";
            $this->codigo = "-4 ".$this->status;
            return false;
        }
        $TipoOperacion = $cce->getAttribute("TipoOperacion");
        $tipoDeComprobante = $Comprobante->getAttribute("tipoDeComprobante");
        if ( ($TipoOperacion=="A" || $TipoOperacion=="2") && 
              $tipoDeComprobante !== "ingreso") {
            $this->status = "Si cce:TipoOperacion es A o 2 el tipodecomprobante debe de ser 'ingreso'";
            $this->codigo = "-5 ".$this->status;
            return false;
        }
        $Descuento = (double)$Comprobante->getAttribute("Descuento");
        $total = (double)$Comprobante->getAttribute("total");
        $Impuestos = $Comprobante->getElementsByTagName('Impuestos')->item(0);
        $totalImpuestosTrasladados = (double)$Impuestos->getAttribute("totalImpuestosTrasladados");
        $totalImpuestosRetenidos = (double)$Impuestos->getAttribute("totalImpuestosRetenidos");
        $suma = $subTotal - $Descuento + $totalImpuestosTrasladados - $totalImpuestosRetenidos;
        if ($suma !== $total) {
            $this->status = "total debe de ser subTotal - Descuento +totalImpuestosTrasladados - totalImpuestosRetenidos";
            $this->codigo = "-6 ".$this->status;
            return false;
        }
        $Emisor = $Comprobante->getElementsByTagName('Emisor')->item(0);
        $rfcEmisor = $Emisor->getAttribute("rfc");
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
            $pais = $nodo->getAttribute("pais");
            if ($pais !== "MEX") {
                $this->status = "El pais del emisor debe de ser MEX";
                $this->codigo = "-11 ".$this->status;
                return false;
            }
            $estado = $nodo->getAttribute("estado");
            // $this->conn->debug=true;
            $ok = $this->Checa_Catalogo("c_Estado", $estado, $pais);
            if (!$ok) {
                $this->status = "Emisor:estado es obligatorio {$estado} y debe de existir en c_Estado para pais MEX";
                $this->codigo = "-12 ".$this->status;
                return false;
            }
            $municipio = $nodo->getAttribute("municipio");
            $ok = $this->Checa_Catalogo("c_Municipio", $municipio, $estado);
            if (!$ok) {
                $this->status = "Emisor:municipio es obligatorio y debe de existir en c_Municipio para estado $estado";
                $this->codigo = "-13 ".$this->status;
                return false;
            }
            $localidad = $nodo->getAttribute("localidad");
            // $this->conn->debug=true;
            if ($localidad != "") {
                $ok = $this->Checa_Catalogo("c_Localidad", $localidad, $estado);
                if (!$ok) {
                    $this->status = "Emisor:localidad es opcional, pero si se coloca debe de existir en c_Localidad para estado $estado";
                    $this->codigo = "-14 ".$this->status;
                    return false;
                }
            }
            $colonia = trim($nodo->getAttribute("colonia"));
            $codigoPostal = $nodo->getAttribute("codigoPostal");
            if (is_numeric($colonia) and strlen($colonia)==4) {
                $ok = $this->Checa_Catalogo("c_Colonia", $colonia, $codigoPostal);
                if (!$ok) {
                    $this->status = "Emisor:colonia puede ser texto pero si es numero debe de existir en c_Colonia para el codigo postal $codigoPostal";
                    $this->codigo = "-15 ".$this->status;
                    return false;
                }
            }
            $c_CP = $this->Obten_Catalogo("c_CP", $codigoPostal);
            if ($c_CP===FALSE) {
                $this->status = "Emisor:codigoPostal es obligatorio y debe de existir en c_CP";
                $this->codigo = "-16 ".$this->status;
                return false;
            }
            // var_dump($c_CP);
            $c_Estado = trim($c_CP["c_estado"]);
            $c_Municipio = trim($c_CP["c_municipio"]);
            $c_Localidad = trim($c_CP["c_localidad"]);
            if ($c_Estado!==$estado) {
                $this->status = "Emisor:estado indicado {$estado} no corresponde al codigo postal $codigoPostal {$c_Estado}";
                $this->codigo = "-16 ".$this->status;
                return false;
            }
            if ($c_Municipio!==$municipio) {
                $this->status = "Emisor:municipio indicado {$municipio} no corresponde al codigo postal $codigoPostal {$c_Municipio}";
                $this->codigo = "-16 ".$this->status;
                return false;
            }
            if ($localidad!="" && $c_Localidad!==$localidad) {
                $this->status = "Emisor:localidad indicada {$localidad} no corresponde al codigo postal $codigoPostal {$c_Localidad}";
                $this->codigo = "-16 ".$this->status;
                return false;
            }
        }
        $Receptor = $Comprobante->getElementsByTagName('Receptor')->item(0);
        $rfcReceptor = $Receptor->getAttribute("rfc");
        if ($rfcReceptor!="XEXX010101000") {
            $this->status = "Receptor:rfc debe de ser XEXX010101000";
            $this->codigo = "-21 ".$this->status;
            return false;
        }
        $nombre = $Receptor->getAttribute("nombre");
        if (strlen($nombre)<=0) {
            $this->status = "Receptor:nombre es obligatorio";
            $this->codigo = "-22 ".$this->status;
            return false;
        }
        $Domicilios = $Receptor->getElementsByTagName('Domicilio');
        if ($Domicilios->length==0) {
            $this->status = "Receptor:Domicilio es obligatorio";
            $this->codigo = "-23 ".$this->status;
            return false;
        }
        $Domicilio = $Domicilios->item(0);
        $pais = $Domicilio->getAttribute("pais");
        $ok = $this->Checa_Catalogo("c_Pais", $pais);
        if ($pais=="MEX" || !$ok) {
            $this->status = "Receptor:pais es obligatorio, debe de existir en c_pais no debe de ser MEX {$pais}";
            $this->codigo = "-24 ".$this->status;
            return false;
        }
        $estado = $Domicilio->getAttribute("estado");
        if ($pais=="ZZZ" || $this->Cuenta_Catalogo("c_Estado",$pais)==0) {
            // ok
        } else {
            $ok = $this->Checa_Catalogo("c_Estado", $estado, $pais);
            if (!$ok) {
                $this->status = "Receptor:estado debe de existir en c_estado para el pais {$pais}";
                $this->codigo = "-25 ".$this->status;
                return false;
            }
        }
        $c_Pais = $this->Obten_Catalogo("c_Pais", $pais);
        $regex_cp = trim($c_Pais["regex_cp"]);
        $codigoPostal = $Domicilio->getAttribute("codigoPostal");
        if ($regex_cp=="") { // solo valida que exista
            if ($codigoPostal=="") {
                $this->status = "Receptor:codigoPostal debe de existir";
                $this->codigo = "-26 ".$this->status;
                return false;
            }
        } else { // Valida que cumpla con el formato
            $aux = "/$regex_cp/";
            $ok = preg_match($aux,$codigoPostal);
            if (!$ok) {
                $this->status = "Receptor:codigoPostal debe de existir cumplir con el formato {$regex_cp} del pais {$pais}";
                $this->codigo = "-26 ".$this->status;
                return false;
            }
        }
        /// Validaciones del complemento
        //$TipoOperacion = $cce->getAttribute("TipoOperacion");
        $ClaveDePedimento = $cce->getAttribute("ClaveDePedimento");
        $CertificadoOrigen = $cce->getAttribute("CertificadoOrigen");
        $NumCertificadoOrigen = $cce->getAttribute("NumCertificadoOrigen");
        $NumExportadorConfiable = $cce->getAttribute("NumExportadorConfiable");
        $Incoterm = $cce->getAttribute("Incoterm");
        $Subdivision = $cce->getAttribute("Subdivision");
        $TipoCambioUSD = $cce->getAttribute("TipoCambioUSD");
        $TotalUSD = $cce->getAttribute("TotalUSD");
        $Mercancias = $cce->getElementsByTagName('Mercancia');
        $nb_Mercancias = $Mercancias->length;
        if ($TipoOperacion=="A") {
            if ($ClaveDePedimento!="" || $CertificadoOrigen!="" ||
                $NumCertificadoOrigen!="" || $NumExportadorConfiable!="" ||
                $Incoterm!="" || $Subdivision!="" || $TipoCambioUSD!="" ||
                $TotalUSD!="" || $nb_Mercancias!=0) 
            {
                $this->status = "cce:TipoOperacion Si la la clave registrada es {A}, no deben de existir los siguientes atributos: [ClaveDePedimento], [CertificadoOrigen], [NumCertificadoOrigen], [NumExportadorConfiable], [Incoterm], [Subdivisión], [TipoCambioUSD], [TotalUSD] y [Mercancias].";
                $this->codigo = "-31 ".$this->status;
                return false;
             }
        }
        if ($TipoOperacion=="1" || $TipoOperacion=="2") {
            if ($ClaveDePedimento=="" || $CertificadoOrigen=="" ||
                $Incoterm=="" || $Subdivision=="" || $TipoCambioUSD=="" ||
                $TotalUSD=="" || $nb_Mercancias==0) 
            {
                $this->status = "cce:TipoOperacion Si la clave registrada es {1} ó {2}, deben existir los siguientes atributos: [ClaveDePedimento], [CertificadoOrigen], [Incoterm], [Subdivision], [TipoCambioUSD], [TotalUSD] y [Mercancias].";
                $this->codigo = "-31 ".$this->status;
                return false;
             }
        }
        if ($CertificadoOrigen=="0" && $NumCertificadoOrigen!="") {
            $this->status = "cce:CertificadoOrigen Si el valor es cero no debe registrarse el atributo [NumCertificadoOrigen].";
            $this->codigo = "-32 ".$this->status;
            return false;
        }
        $suma=0.0;
        for ($i=0; $i<$nb_Mercancias; $i++) {
            $Mercancia = $Mercancias->item($i);
            $ValorDolares = (double)$Mercancia->getAttribute("ValorDolares");
            $suma += $ValorDolares;
            // echo "suma=$suma valo=$ValorDolares nb=$nb_Mercancias\n";
        }
        if ($TotalUSD != $suma) {
            $this->status = "cce:TotalUSD {$TotalUSD} no es igual a la suma de Mercancia:ValorDolares {$suma}";
            $this->codigo = "-33 ".$this->status;
            return false;
        }
        $Emisores = $cce->getElementsByTagName('Emisor');
        if ($Emisores->length > 0) {
            $Emisor = $Emisores->item(0);
            $curp = $Emisor->getAttribute("Curp");
            if (strlen($rfcEmisor)==12 && $curp != "") {
                $this->status = "cce:Emisor:Curp Si el atributo [rfc] del nodo cfdi:Comprobante:Emisor es de longitud 12, entonces este campo no debe existir";
                $this->codigo = "-41 ".$this->status;
                return false;
            }
        }
        $Receptor = $cce->getElementsByTagName('Receptor')->item(0);
        $NumRegIdTrib = $Receptor->getAttribute("NumRegIdTrib");
        $lista_taxid = trim($c_Pais["lista_taxid"]);
        $regex_taxid = trim($c_Pais["regex_taxid"]);
        if ($lista_taxid!="") { // Si tiene lista, busca en catalogo
            $ok = $this->Checa_Catalogo("c_Taxid",$NumRegIdTrib,$pais);
            if (!$ok) {
                $this->status = "cce:Receptor:NumRegIdTrib no existe en lista de validacion del pais {$pais}";
                $this->codigo = "-42 ".$this->status;
                return false;
            }
        } else if ($regex_taxid!="") { // Valida solo formato, no en lista
            $aux = "/$regex_taxid/";
            $ok = preg_match($aux,$NumRegIdTrib);
            if (!$ok) {
                $this->status = "cce:Receptor:NumRegIdTrib formato coincide con el formato {$regex_taxid} del pais {$pais}";
                $this->codigo = "-43 ".$this->status;
                return false;
            }
        }
        $Destinatarios = $cce->getElementsByTagName('Destinatario');
        if ($Destinatarios->length > 0) {
            $Destinatario = $Destinatarios->item(0);
            $NumRegIdTrib = $Destinatario->getAttribute("NumRegIdTrib");
            $Rfc = $Destinatario->getAttribute("Rfc");
            if ($NumRegIdTrib=="" && $Rfc=="") {
                $this->status = "cce:Destinatario Debe existir al menos uno de los atributos [NumRegIdTrib] o [Rfc]";
                $this->codigo = "-51 ".$this->status;
                return false;
            }
            $Domicilio = $Destinatario->getElementsByTagName('Domicilio')->item(0);
            $Pais = $Domicilio->getAttribute("Pais");
            $c_Pais = $this->Obten_Catalogo("c_Pais", $Pais);
            $regex_cp = trim($c_Pais["regex_cp"]);
            $regex_taxid = trim($c_Pais["regex_taxid"]);
            $lista_taxid = trim($c_Pais["lista_taxid"]);
            $Colonia = $Domicilio->getAttribute("Colonia");
            $Localidad = $Domicilio->getAttribute("Localidad");
            $Municipio = $Domicilio->getAttribute("Municipio");
            $Estado = $Domicilio->getAttribute("Estado");
            $CodigoPostal = $Domicilio->getAttribute("CodigoPostal");
            if ($NumRegIdTrib!="") {
                if ($lista_taxid!="") { // Si tiene lista, busca en catalogo
                    $ok = $this->Checa_Catalogo("c_Taxid",$NumRegIdTrib,$Pais);
                    if (!$ok) {
                        $this->status = "cce:Destinatario:NumRegIdTrib no existe en lista de validacion del pais {$Pais}";
                        $this->codigo = "-52 ".$this->status;
                        return false;
                    }
                } else if ($regex_taxid!="") { // Valida solo formato, no en lista
                    $aux = "/$regex_taxid/";
                    $ok = preg_match($aux,$NumRegIdTrib);
                    if (!$ok) {
                        $this->status = "cce:Destinatario:NumRegIdTrib formato coincide con el formato {$regex_taxid} del pais {$Pais}";
                        $this->codigo = "-53 ".$this->status;
                        return false;
                    }
                }
            } // NumRegIdTrib != ""
            if ($Rfc=="XAXX010101000" || $Rfc=="XEXX010101000") {
                $this->status = "cce:Destinatario:Rfc no debe de ser el rfc generico {$Rfc}";
                $this->codigo = "-54 ".$this->status;
                return false;
            }
            if ($Pais=="ZZZ" || $this->Cuenta_Catalogo("c_Estado",$Pais)==0) {
                // ok
            } else {
                $ok = $this->Checa_Catalogo("c_Estado", $Estado, $Pais);
                if (!$ok) {
                    $this->status = "cce:Destinatario:Domicilio::Estado es obligatorio y debe de existir en c_Estado para pais {$Pais}";
                    $this->codigo = "-64 ".$this->status;
                    return false;
                }
            } // Estado
            if ($Pais == "MEX") { // Con mexico se valida, demas es texto libre
                if (strlen($Colonia)==4 && is_numeric($Colonia)) {
                    $ok = $this->Checa_Catalogo("c_Colonia", $Colonia, $CodigoPostal);
                    if (!$ok) {
                        $this->status = "cce:Destinatario:Domicilio:Colonia puede ser texto pero si es numero debe de existir en c_Colonia para el codigo postal $codigoPostal";
                        $this->codigo = "-61 ".$this->status;
                        return false;
                    }
                } // Colonia
                if ($Localidad != "") {
                    $ok = $this->Checa_Catalogo("c_Localidad", $Localidad, $Estado);
                    if (!$ok) {
                        $this->status = "cce:Destinatario:Domicilio:Localidad es opcional, pero si se coloca debe de existir en c_Localidad para estado $Estado";
                        $this->codigo = "-62 ".$this->status;
                        return false;
                    }
                } // Localidad
                $ok = $this->Checa_Catalogo("c_Municipio", $Municipio, $Estado);
                if (!$ok) {
                    $this->status = "cce:Destinatario:Domicilio:Municipio es obligatorio y debe de existir en c_Municipio para estado $Estado";
                    $this->codigo = "-63 ".$this->status;
                    return false;
                } // Municipio
                $c_CP = $this->Obten_Catalogo("c_CP", $CodigoPostal);
                if (sizeof($c_CP)==0) {
                    $this->status = "cce:Destinatario:Domicilio:CodigoPostal es obligatorio y debe de existir en c_CP";
                    $this->codigo = "-66 ".$this->status;
                    return false;
                }
                $c_Estado = trim($c_CP["c_estado"]);
                $c_Municipio = trim($c_CP["c_municipio"]);
                $c_Localidad = trim($c_CP["c_localidad"]);
                if ($c_Estado!==$Estado) {
                    $this->status = "cce:Destinatario:Domicilio:Estado indicado {$Estado} no corresponde al codigo postal $CodigoPostal {$c_Estado}";
                    $this->codigo = "-66 ".$this->status;
                    return false;
                }
                if ($c_Municipio!==$Municipio) {
                    $this->status = "cce:Destinatario:Domicilio:Municipio indicado {$Municipio} no corresponde al codigo postal $CodigoPostal {$c_Municipio}";
                    $this->codigo = "-66 ".$this->status;
                    return false;
                }
                if ($Localidad!="" && $c_Localidad!==$Localidad) {
                    $this->status = "cce:Destinatario:Domicilio:Localidad indicada {$Localidad} no corresponde al codigo postal $CodigoPostal {$c_Localidad}";
                    $this->codigo = "-66 ".$this->status;
                    return false;
                }
            } else { // Pais != "MEX"
                // echo "regex=$regex_cp cp=$C
                if ($regex_cp!="") { // Valida formato
                    $aux = "/$regex_cp/";
                    $ok = preg_match($aux,$CodigoPostal);
                    if (!$ok) {
                        $this->status = "Receptor:codigoPostal debe de cumplir con el formato {$regex_cp} del pais {$Pais}";
                        $this->codigo = "-65 ".$this->status;
                        return false;
                    }
                }
            } // Pais == "MEX" o no
        } // Si hay destinario
        if ($nb_Conceptos != $nb_Mercancias) {
                $this->status = "cfdi:Conceptos no tiene la misma cantidad de elementos que cce:Mercancias";
                $this->codigo = "-71 ".$this->status;
                return false;
        }
        $hash=array();
        $hay_muestra = false; $suma_muestra = 0;
        $cantidad_3 = 0; $cantidad_0 = 0;
        for ($i=0; $i<$nb_Conceptos; $i++) {
            $Concepto = $Conceptos->item($i);
            $noIdentificacion = $Concepto->getAttribute("noIdentificacion");
            if ($noIdentificacion=="") {
                $this->status = "cfdi:Concepto:noIdentificacion es obligatorio";
                $this->codigo = "-71 ".$this->status;
                return false;
            }
            if (array_key_exists($noIdentificacion,$hash)) {
                $this->status = "cfdi:Concepto:noIdentificacion no se debe de repetir {$noIdentificacion}";
                $this->codigo = "-71 ".$this->status;
                return false;
            }
            $hash[$noIdentificacion]=true;
            $esta=false;
            for ($j=0; $j<$nb_Mercancias; $j++) {
                $Mercancia = $Mercancias->item($j);
                $NoIdentificacion = $Mercancia->getAttribute("NoIdentificacion");
                if ($noIdentificacion==$NoIdentificacion) {
                    $esta=true;
                    $CantidadAduana = $Mercancia->getAttribute("CantidadAduana");
                    $UnidadAduana = $Mercancia->getAttribute("UnidadAduana");
                    $ValorUnitarioAduana = $Mercancia->getAttribute("ValorUnitarioAduana");
                    $FraccionArancelaria = $Mercancia->getAttribute("FraccionArancelaria");
                    $ValorDolares = $Mercancia->getAttribute("ValorDolares");
                    $FraccionArancelaria = $Mercancia->getAttribute("FraccionArancelaria");
                    $cantidad = $Concepto->getAttribute("cantidad");
                    $unidad = $Concepto->getAttribute("unidad");
                    $importe = $Concepto->getAttribute("importe");
                    $valorUnitario = $Concepto->getAttribute("valorUnitario");
                    if ($FraccionArancelaria=="98010001") {
                        $hay_muestra = true;
                        $suma_muestra += $ValorDolares;
                    }
                    if ($CantidadAduana=="" && $UnidadAduana=="" && $ValorUnitarioAduana=="") {
                        $cantidad_0++;
                    } elseif ($CantidadAduana!="" && $UnidadAduana!="" && $ValorUnitarioAduana!="") {
                        $cantidad_3++;
                    } else {
                        $this->status = "cce:Mercancia:CantidadAduana,UnidadAduana, ValorUnitarioAduana Si se registra alguno de estos atributos entonces deben existir los tres atributos.";
                        $this->codigo = "-82 ".$this->status;
                        return false;
                    }
                    if ($UnidadAduana!="" && $UnidadAduana!="99") {
                        if ($ValorUnitarioAduana <= 0) {
                            $this->status = "cce:Mercancia:ValorUnitarioAduana debe de ser mayor que cero si UnidadAduana no es {99}";
                            $this->codigo = "-84 ".$this->status;
                            return false;
                        }
                    }
                    if ($CantidadAduana=="") {
                        $regex = "[0-9]{1,14}(.([0-9]{1,3}))?";
                        $aux="/$regex/";
                        $ok = preg_match($aux,$cantidad);
                        if (!$ok) {
                            $this->status = "cfdi:Concepto:cantidad no cumple con el formato {$regex}";
                            $this->codigo = "-73 ".$this->status;
                            return false;
                        }
                        $cantidad = (double)$cantidad;
                        if ($cantidad < 0.001) {
                            $this->status = "cfdi:Concepto:cantidad menor que {0.001}";
                            $this->codigo = "-73 ".$this->status;
                            return false;
                        }
                        $ok = $this->Checa_Catalogo("c_UnidadMedidaAduana", $unidad);
                        if (!$ok) {
                            $this->status = "cfdi:Concepto:unidad debe tener un valor del catálogo c_UnidadMedidaAduana {$unidad}";
                            $this->codigo = "-74 ".$this->status;
                            return false;
                        }
                        $regex = "[0-9]{1,16}(.([0-9]{1,4}))?";
                        $aux="/$regex/";
                        $ok = preg_match($aux,$valorUnitario);
                        if (!$ok) {
                            $this->status = "cfdi:Concepto:valorUnitario no cumple con el formato {$regex}";
                            $this->codigo = "-75 ".$this->status;
                            return false;
                        }
                        $valorUnitario = (double)$valorUnitario;
                        if ($valorUnitario < 0.0001) {
                            $this->status = "cfdi:Concepto:valorUnitario menor que {0.0001}";
                            $this->codigo = "-75 ".$this->status;
                            return false;
                        }
                        if ($UnidadAduana=="99" || $unidad=="99") {
                            /*
                                Por xsd no puede ser cero
                            if ($ValorDolares!=0) {
                                $this->status = "cce:Mercancia:ValorDolares debe de ser cero para undiad {99}";
                                $this->codigo = "-93 ".$this->status;
                                return false;
                            }
                             */
                        } elseif ($ValorDolares==1) { // Por normatividad ???
                        } else {
                            $aux = round(($cantidad * $valorUnitario * $TipoCambio) / $TipoCambioUSD,2);
                            if ($aux != $ValorDolares) {
                                $this->status = "cce:Mercancia:ValorDolares no es el producto de (cantidad * valorUnitaro * TipoCambio) / TipoCambioUSD id={$noIdentificacion}";
                                $this->codigo = "-92 ".$this->status;
                                return false;
                            }
                        }
                    } else { // Si existe Cantidad aduana
                        $aux = round($CantidadAduana * $ValorUnitarioAduana,2);
                        if ($aux != $ValorDolares) {
                            $this->status = "cce:Mercancia:ValorDolares no es el producto de (CantidadAduana * ValorUnitarioAduana id={$noIdentificacion}";
                            $this->codigo = "-91 ".$this->status;
                            return false;
                        }
                    } // Existe cantidad aduana
                    $aux = (double)$cantidad * (double)$valorUnitario;
                    if ($aux != $importe) {
                        $this->status = "cfdi:Concepto:importe no es igual a cantidad multiplicada por valorUnitario Id =$noIdentificacion}";
                        $this->codigo = "-76 ".$this->status;
                        return false;
                    }
                    if ( ($UnidadAduana=="99" || $unidad=="99") &&
                         $FraccionArancelaria != "") {
                        $this->status = "cce:Mercancia:FraccionArancelaria no debe de existir para unidad {99} Id=$noIdentificacion}";
                        $this->codigo = "-77 ".$this->status;
                        return false;
                    }
                } // mercancia de cada concepto
            }  // Para cada mercancia
            if (!$esta) {
                $this->status = "cce:Mercancia:NoIdentificacion no existe NoIdentificacion con valor {$noIdentificacion} de Conceptos";
                $this->codigo = "-72 ".$this->status;
                return false;
            }
        } // Para cada concepto
        if ($hay_muestra) {
            $descuento = (double)$Comprobante->getAttribute("descuento");
            if ($suma_muestra > $descuento) {
                $this->status = "cfdi:Documento:descuento dene de ser mayor o igual que la suma de muestra {98010001}";
                $this->codigo = "-81 ".$this->status;
                return false;
            }
        }
        if ($cantidad_0 > 0 && $cantidad_3 > 0) {
            $this->status = "cce:Mercancia:CantidadAduana, UnidadAduana,ValorUnitarioAduana sin un elemento dfe Mercancia tiene los tres atributos, todos los elmentos de mercancia lo deben de tener";
            $this->codigo = "-83 ".$this->status;
            return false;
        }
        return $ok;
    }
    // }}}
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
}
