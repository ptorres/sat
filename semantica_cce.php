<?php
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
        $this->status = "Inicia Validacion de semantica cce";
        $this->codigo = "0 ".$this->status;
        /// Validaciones del Comprobante CFDI 3.2
        $Comprobante = $this->xml_cfd->getElementsByTagName('Comprobante')->item(0);
        $version = $Comprobante->getAttribute("version");
        if ($version != "3.2") {
            $this->status = "El valor del atributo cfdi:Comprobante:version debe de ser 3.2";
            $this->codigo = "140 ".$this->status;
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
            $this->status = "El valor del atributo cfdi:subTotal {$subTotal} debe de ser igual a la suma de los atributos importe {$suma} por cada [Concepto]";
            $this->codigo = "141 ".$this->status;
            return false;
        }
        $cce = $Comprobante->getElementsByTagName('ComercioExterior')->item(0);
        // $this->conn->debug=true;
        $Moneda = $Comprobante->getAttribute("Moneda");
        if ($Moneda=="") {
            $this->status = "El valor del atributo cfdi:Moneda es requerido para este complemento";
            $this->codigo = "142 ".$this->status;
            return false;
        }
        $ok = $this->Checa_Catalogo("c_Moneda", $Moneda);
        if (!$ok) {
            $this->status = "El atributo cfdi:Moneda debe de contener un valor del catalogo c_Moneda";
            $this->codigo = "143 ".$this->status;
            return false;
        }
        $TipoCambio = $Comprobante->getAttribute("TipoCambio");
        $valor = (double)$TipoCambio;
        if ($valor==0) {
            $this->status = "El atributo cfdi:Comprobante:TipoCambio es requerido";
            $this->codigo = "144 ".$this->status;
            return false;
        }
        $regex = "[0-9]{1,14}(\.([0-9]{1,6}))?";
        $aux = "/^$regex$/A";
        $ok = preg_match($aux,$TipoCambio);
        if (!$ok) {
            $this->status = " El atributo cfdi:Comprobante:TipoCambio debe cumplir con el patron [0-9]{1,14}(.([0-9]{1,6}))?.";
            $this->codigo = "145 ".$this->status;
            return false;
        }
        $TipoOperacion = $cce->getAttribute("TipoOperacion");
        $tipoDeComprobante = $Comprobante->getAttribute("tipoDeComprobante");
        if ( ($TipoOperacion=="A" || $TipoOperacion=="2") && 
              $tipoDeComprobante !== "ingreso") {
            $this->status = "El valor del atributo cfdi:Comprobante:tipoDeComprobante debe de ser {ingreso} cuando el valor del atribuito cce:ComercioExterior:TipoOperacion sea {A} o {2}";
            $this->codigo = "146 ".$this->status;
            return false;
        }
        $descuento = (double)$Comprobante->getAttribute("descuento");
        $total = (double)$Comprobante->getAttribute("total");
        $Impuestos = $Comprobante->getElementsByTagName('Impuestos')->item(0);
        $totalImpuestosTrasladados = (double)$Impuestos->getAttribute("totalImpuestosTrasladados");
        $totalImpuestosRetenidos = (double)$Impuestos->getAttribute("totalImpuestosRetenidos");
        $suma = $subTotal - $descuento + $totalImpuestosTrasladados - $totalImpuestosRetenidos;
        if ($suma !== $total) {
            $this->status = "El valor del atributo cfdi:Comprobante:total {$total} debe ser igual a la suma del cfdi:Comprobante:subTotal, menos el cfdi:Comprobante:descuento, mas los impuestos trasladados (cfdi:Comprobante:Impuestos:totalImpuestosTrasladados), menos los impuestos retenidos (cfdi:Comprobante:Impuestos:totalImpuestosRetenidos) {$suma}.";
            $this->codigo = "147 ".$this->status;
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
                $this->status = "El atributo [pais] de los nodos DomicilioFiscal y/o ExpedidoEn debe contener la clave {MEX}.";
                $this->codigo = "148 ".$this->status;
                return false;
            }
            $estado = $nodo->getAttribute("estado");
            // $this->conn->debug=true;
            $ok = $this->Checa_Catalogo("c_Estado", $estado, $pais);
            if (!$ok) {
                $this->status = "El atributo [estado] {$estado} de los nodos DomicilioFiscal y/o ExpedidoEn debe contener una clave del catalogo c_Estado donde la columna c_Pais tenga el valor {MEX}.";
                $this->codigo = "149 ".$this->status;
                return false;
            }
            $municipio = $nodo->getAttribute("municipio");
            $ok = $this->Checa_Catalogo("c_Municipio", $municipio, $estado);
            if (!$ok) {
                $this->status = "El atributo [municipio] de los nodos DomicilioFiscal y/o ExpedidoEn debe contener una clave del catalogo c_Municipio donde la columna c_Estado sea igual a la clave registrada en el atributo [estado].";
                $this->codigo = "150 ".$this->status;
                return false;
            }
            $localidad = $nodo->getAttribute("localidad");
            // $this->conn->debug=true;
            if ($localidad != "") {
                $ok = $this->Checa_Catalogo("c_Localidad", $localidad, $estado);
                if (!$ok) {
                    $this->status = "El atributo [localidad] de los nodos DomicilioFiscal y/o ExpedidoEn debe contener una clave del catalogo c_Localidad donde la columna c_Estado sea igual a la clave registrada en el atributo [estado].";
                    $this->codigo = "151 ".$this->status;
                    return false;
                }
            }
            $colonia = trim($nodo->getAttribute("colonia"));
            $codigoPostal = $nodo->getAttribute("codigoPostal");
            if (is_numeric($colonia) and strlen($colonia)==4) {
                $ok = $this->Checa_Catalogo("c_Colonia", $colonia, $codigoPostal);
                if (!$ok) {
                    $this->status = "El atributo [colonia] {$colonia} de los nodos DomicilioFiscal y/o ExpedidoEn debe contener una clave del catalogo c_Colonia donde la columna c_CP sea igual a la clave registrada en el atributo [codigoPostal] {$codigoPostal}.";
                    $this->codigo = "152 ".$this->status;
                    return false;
                }
            }
            $c_CP = $this->Obten_Catalogo("c_CP", $codigoPostal);
            if ($c_CP===FALSE) {
                $this->status = "El atributo [codigoPostal] de los nodos DomicilioFiscal y/o ExpedidoEn debe contener una clave del catalogo c_CP, donde la columna clave c_Estado sea igual a la clave registrada en el atributo [estado], la columna clave c_Municipio sea igual a la clave registrada en el atributo [municipio], y si existe el atributo [localidad], que la columna clave c_Localidad sea igual a la clave registrada en el atributo [localidad].";
                $this->codigo = "153 ".$this->status;
                return false;
            }
            // var_dump($c_CP);
            $c_Estado = trim($c_CP["c_estado"]);
            $c_Municipio = trim($c_CP["c_municipio"]);
            $c_Localidad = trim($c_CP["c_localidad"]);
            if ($c_Estado!==$estado) {
                $this->status = "Emisor:estado indicado {$estado} no corresponde al codigo postal $codigoPostal {$c_Estado}";
                $this->codigo = "153 ".$this->status;
                return false;
            }
            if ($c_Municipio!==$municipio) {
                $this->status = "Emisor:municipio indicado {$municipio} no corresponde al codigo postal $codigoPostal {$c_Municipio}";
                $this->codigo = "153 ".$this->status;
                return false;
            }
            if ($localidad!="" && $c_Localidad!==$localidad) {
                $this->status = "Emisor:localidad indicada {$localidad} no corresponde al codigo postal $codigoPostal {$c_Localidad}";
                $this->codigo = "153 ".$this->status;
                return false;
            }
        }
        $Receptor = $Comprobante->getElementsByTagName('Receptor')->item(0);
        $rfcReceptor = $Receptor->getAttribute("rfc");
        if ($rfcReceptor!="XEXX010101000") {
            $this->status = "El valor del atributo cfdi:Comprobante:Receptor:rfc debe ser {XEXX010101000}.";
            $this->codigo = "154 ".$this->status;
            return false;
        }
        $nombre = $Receptor->getAttribute("nombre");
        if (strlen($nombre)<=0) {
            $this->status = "El atributo cfdi:Comprobante:Receptor:nombre es requerido.";
            $this->codigo = "155 ".$this->status;
            return false;
        }
        $Domicilios = $Receptor->getElementsByTagName('Domicilio');
        if ($Domicilios->length==0) {
            $this->status = "El nodo cfdi:Comprobante:Receptor:Domicilio es requerido.";
            $this->codigo = "156 ".$this->status;
            return false;
        }
        $Domicilio = $Domicilios->item(0);
        $pais = $Domicilio->getAttribute("pais");
        $ok = $this->Checa_Catalogo("c_Pais", $pais);
        if ($pais=="MEX" || !$ok) {
            $this->status = "El atributo [pais] del nodo cfdi:Comprobante:Receptor:Domicilio debe contener un valor del catalogo c_Pais y debe ser diferente de {MEX}. {$pais}";
            $this->codigo = "157 ".$this->status;
            return false;
        }
        $estado = $Domicilio->getAttribute("estado");
        if ($pais=="ZZZ" || $this->Cuenta_Catalogo("c_Estado",$pais)==0) {
            // ok
        } else {
            $ok = $this->Checa_Catalogo("c_Estado", $estado, $pais);
            if (!$ok) {
                $this->status = "El atributo [estado] del nodo cfdi:Comprobante:Receptor:Domicilio debe contener una clave del catalogo c_Estado donde la columna c_Pais sea igual al valor registrado en el atributo [pais], siempre y cuando el valor del atributo [pais] sea distinto de {ZZZ} o su valor exista en la columna c_Pais del catalogo c_Estado.";
                $this->codigo = "158 ".$this->status;
                return false;
            }
        }
        $c_Pais = $this->Obten_Catalogo("c_Pais", $pais);
        $regex_cp = trim($c_Pais["regex_cp"]);
        $codigoPostal = $Domicilio->getAttribute("codigoPostal");
        if ($regex_cp=="") { // solo valida que exista
            if ($codigoPostal=="") {
                $this->status = "El atributo [codigoPostal] del nodo cfdi:Comprobante:Receptor:Domicilio es requerido.";
                $this->codigo = "160 ".$this->status;
                return false;
            }
        } else { // Valida que cumpla con el formato
            $aux = "/$regex_cp/";
            $ok = preg_match($aux,$codigoPostal);
            if (!$ok) {
                $this->status = "El atributo [codigoPostal] del nodo cfdi:Comprobante:Receptor:Domicilio debe cumplir con el patron especificado ($regex_cp) en el catalogo c_Pais para el pais indicado en el atributo [pais] ($pais).";
                $this->codigo = "159 ".$this->status;
                return false;
            }
        }
        /// Validaciones del complemento
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
                $this->status = "Si la clave registrada es {A} en el atributo cce:ComercioExterior:TipoOperacion, no deben existir los atributos [ClaveDePedimento], [CertificadoOrigen], [NumCertificadoOrigen], [NumExportadorConfiable], [Incoterm], [Subdivision], [TipoCambioUSD] y  [TotalUSD], ni el nodo [Mercancias].";
                $this->codigo = "161 ".$this->status;
                return false;
             }
        }
        if ($TipoOperacion=="1" || $TipoOperacion=="2") {
            if ($ClaveDePedimento=="" || $CertificadoOrigen=="" ||
                $Incoterm=="" || $Subdivision=="" || $TipoCambioUSD=="" ||
                $TotalUSD=="" || $nb_Mercancias==0) 
            {
                $this->status = "Si la clave registrada es {1} o {2} en el atributo cce:ComercioExterior:TipoOperacion, deben existir los atributos [ClaveDePedimento], [CertificadoOrigen], [Incoterm], [Subdivision], [TipoCambioUSD] y [TotalUSD], asi como el nodo [Mercancias].";
                $this->codigo = "162 ".$this->status;
                return false;
             }
        }
        if ($CertificadoOrigen=="0" && $NumCertificadoOrigen!="") {
            $this->status = "Si el valor del atributo cce:ComercioExterior:CertificadoOrigen es cero, no debe registrarse el atributo [NumCertificadoOrigen].";
            $this->codigo = "163 ".$this->status;
            return false;
        }
        $suma=0.0;
        for ($i=0; $i<$nb_Mercancias; $i++) {
            $Mercancia = $Mercancias->item($i);
            $ValorDolares = (double)$Mercancia->getAttribute("ValorDolares");
            $suma += $ValorDolares;
            // echo "suma=$suma valo=$ValorDolares nb=$nb_Mercancias\n";
        }
        if (abs($TotalUSD - $suma) > 0.001) {
            $this->status = "El atributo cce:ComercioExterior:TotalUSD ($TotalUSD) no coincide con la suma de los valores del atributo [ValorDolares] de las mercancias ($suma).";
            $this->codigo = "164 ".$this->status;
            return false;
        }
        list($enteros,$decimales) = explode(".",$TotalUSD);
        if (strlen($decimales) != 2) {
            $this->status = "El atributo cce:ComercioExterior:TotalUSD no tiene dos decimales.";
            $this->codigo = "165 ".$this->status;
            return false;
        }
        $Emisores = $cce->getElementsByTagName('Emisor');
        if ($Emisores->length > 0) {
            $Emisor = $Emisores->item(0);
            $curp = $Emisor->getAttribute("Curp");
            if (strlen($rfcEmisor)==12 && $curp != "") {
                $this->status = "El atributo cce:ComercioExterior:Emisor:Curp no debe existir cuando la longitud del valor del atributo [rfc] del nodo cfdi:Comprobante:Emisor es de longitud 12.";
                $this->codigo = "166 ".$this->status;
                return false;
            }
        }
        $Receptor = $cce->getElementsByTagName('Receptor')->item(0);
        $NumRegIdTrib = $Receptor->getAttribute("NumRegIdTrib");
        $lista_taxid = trim($c_Pais["lista_taxid"]);
        $regex_taxid = trim($c_Pais["regex_taxid"]);
        //if ($lista_taxid!="") { // Si tiene lista, busca en catalogo
        $cant = $this->Cuenta_Catalogo("c_Taxid",$pais);
        if ($cant>0) { // Si hay algo en la lista, busca en catalogo
            $ok = $this->Checa_Catalogo("c_Taxid",$NumRegIdTrib,$pais);
            if (!$ok) {
                $this->status = "El valor del atributo cce:ComercioExterior:Receptor:NumRegIdTrib no es valido. No esta en lista c_Taxid";
                $this->codigo = "167 ".$this->status;
                return false;
            }
        } else if ($regex_taxid!="") { // Valida solo formato, no en lista
            $aux = "/$regex_taxid/";
            $ok = preg_match($aux,$NumRegIdTrib);
            if (!$ok) {
                $this->status = "El valor del atributo cce:ComercioExterior:Receptor:NumRegIdTrib no es valido. No coincide con formato $regex_taxid).";
                $this->codigo = "167 ".$this->status;
                return false;
            }
        }
        $Destinatarios = $cce->getElementsByTagName('Destinatario');
        if ($Destinatarios->length > 0) {
            $Destinatario = $Destinatarios->item(0);
            $NumRegIdTrib = $Destinatario->getAttribute("NumRegIdTrib");
            $Rfc = $Destinatario->getAttribute("Rfc");
            if ($NumRegIdTrib=="" && $Rfc=="") {
                $this->status = "Debe existir al menos uno de los atributos [NumRegIdTrib] o [Rfc] en el nodo cce:ComercioExterior:Destinartario.";
                $this->codigo = "168 ".$this->status;
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
                //if ($lista_taxid!="") { // Si tiene lista, busca en catalogo
                if ($this->Cuenta_Catalogo("c_Taxid",$pais)>0) { // Si hay registros , busca en catalogo
                    $ok = $this->Checa_Catalogo("c_Taxid",$NumRegIdTrib,$Pais);
                    if (!$ok) {
                        $this->status = "El valor del atributo cce:ComercioExterior:Destinatario:NumRegIdTrib no es valido. No esta en catalogo c_Taxid";
                        $this->codigo = "169 ".$this->status;
                        return false;
                    }
                } else if ($regex_taxid!="") { // Valida solo formato, no en lista
                    $aux = "/$regex_taxid/";
                    $ok = preg_match($aux,$NumRegIdTrib);
                    if (!$ok) {
                        $this->status = "El valor del atributo cce:ComercioExterior:Destinatario:NumRegIdTrib no es valido. No coincide con formato";
                        $this->codigo = "169 ".$this->status;
                        return false;
                    }
                }
            } // NumRegIdTrib != ""
            if ($Rfc=="XAXX010101000" || $Rfc=="XEXX010101000") {
                $this->status = "El atributo cce:ComercioExterior:Destinatario:Rfc no debe ser rfc generico {XAXX010101000} ni {XEXX010101000}.";
                $this->codigo = "170 ".$this->status;
                return false;
            }
            if ($Pais=="ZZZ" || $this->Cuenta_Catalogo("c_Estado",$Pais)==0) {
                // ok
            } else {
                $ok = $this->Checa_Catalogo("c_Estado", $Estado, $Pais);
                if (!$ok) {
                    $this->status = "El valor del atributo cce:ComercioExterior:Destinatario:Domicilio:Estado debe contener una clave del catalogo de estados c_Estado, donde la columna c_Pais sea igual a la clave de pais registrada en el atributo [Pais] cuando la clave es distinta de {ZZZ} y existe en la columna c_Pais.";
                    $this->codigo = "176 ".$this->status;
                    return false;
                }
            } // Estado
            if ($Pais == "MEX") { // Con mexico se valida, demas es texto libre
                if (strlen($Colonia)==4 && is_numeric($Colonia)) {
                    $ok = $this->Checa_Catalogo("c_Colonia", $Colonia, $CodigoPostal);
                    if (!$ok) {
                        $this->status = "El atributo cce:ComercioExterior:Destinatario:Domicilio:Colonia no tiene uno de los valores permitidos. Catalogo c_Colonia";
                        $this->codigo = "172 ".$this->status;
                        return false;
                    }
                } // Colonia
                if ($Localidad != "") {
                    $ok = $this->Checa_Catalogo("c_Localidad", $Localidad, $Estado);
                    if (!$ok) {
                        $this->status = "El valor del atributo cce:ComercioExterior:Destinatario:Domicilio:Localidad debe contener una clave del catalogo de localidades (c_Localidad), donde la columna c_estado sea igual a la clave registrada en el atributo [Estado] cuando la clave de pais es {MEX}.";
                        $this->codigo = "174 ".$this->status;
                        return false;
                    }
                } // Localidad
                $ok = $this->Checa_Catalogo("c_Municipio", $Municipio, $Estado);
                if (!$ok) {
                    $this->status = "El valor del atributo cce:ComercioExterior:Destinatario:Domicilio:Municipio debe contener una clave del catalogo de municipios (c_Municipio), donde la columna c_estado sea igual a la clave registrada en el atributo [Estado].";
                    $this->codigo = "175 ".$this->status;
                    return false;
                } // Municipio
                $c_CP = $this->Obten_Catalogo("c_CP", $CodigoPostal);
                if (sizeof($c_CP)==0) {
                    $this->status = "cce:Destinatario:Domicilio:CodigoPostal es obligatorio y debe de existir en c_CP";
                    $this->codigo = "178 ".$this->status;
                    return false;
                }
                $c_Estado = trim($c_CP["c_estado"]);
                $c_Municipio = trim($c_CP["c_municipio"]);
                $c_Localidad = trim($c_CP["c_localidad"]);
                if ($c_Estado!==$Estado) {
                    $this->status = "cce:Destinatario:Domicilio:Estado indicado {$Estado} no corresponde al codigo postal $CodigoPostal {$c_Estado}";
                    $this->codigo = "178 ".$this->status;
                    return false;
                }
                if ($c_Municipio!==$Municipio) {
                    $this->status = "cce:Destinatario:Domicilio:Municipio indicado {$Municipio} no corresponde al codigo postal $CodigoPostal {$c_Municipio}";
                    $this->codigo = "178 ".$this->status;
                    return false;
                }
                if ($Localidad!="" && $c_Localidad!==$Localidad) {
                    $this->status = "cce:Destinatario:Domicilio:Localidad indicada {$Localidad} no corresponde al codigo postal $CodigoPostal {$c_Localidad}";
                    $this->codigo = "178 ".$this->status;
                    return false;
                }
            } else { // Pais != "MEX"
                // echo "regex=$regex_cp cp=$C
                if ($regex_cp!="") { // Valida formato
                    $aux = "/$regex_cp/";
                    $ok = preg_match($aux,$CodigoPostal);
                    if (!$ok) {
                        $this->status = "El valor del atributo cce:ComercioExterior:Destinatario:Domicilio:CodigoPostal debe cumplir con el patron especificado en el catalogo de paises publicado en el portal del SAT para cuando la clave de pais sea distinta de {MEX}.";
                        $this->codigo = "177 ".$this->status;
                        return false;
                    }
                }
            } // Pais == "MEX" o no
        } // Si hay destinario
        if ($nb_Conceptos != $nb_Mercancias) {
                $this->status = "cfdi:Conceptos no tiene la misma cantidad de elementos que cce:Mercancias";
                $this->codigo = "179 ".$this->status;
                return false;
        }
        $hash=array();
        $hay_muestra = false; $suma_muestra = 0;
        $cantidad_3 = 0; $cantidad_0 = 0;
        for ($i=0; $i<$nb_Conceptos; $i++) {
            $Concepto = $Conceptos->item($i);
            $noIdentificacion = $Concepto->getAttribute("noIdentificacion");
            if ($noIdentificacion=="") {
                $this->status = "Todos los conceptos registrados en el elemento cfdi:Comprobante:Conceptos deben tener registrado el atributo cfdi:Comprobante:Conceptos:Concepto:noIdentificacion.";
                $this->codigo = "179 ".$this->status;
                return false;
            }
            if (array_key_exists($noIdentificacion,$hash)) {
                $this->status = "El valor del atributo cfdi:Comprobante:Conceptos:Concepto:noIdentificacion no se debe repetir en todos los conceptos registrados en el elemento cfdi:Comprobante:Conceptos.";
                $this->codigo = "180 ".$this->status;
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
                        $suma_muestra += $importe;
                    }
                    if ($CantidadAduana=="" && $UnidadAduana=="" && $ValorUnitarioAduana=="") {
                        $cantidad_0++;
                    } elseif ($CantidadAduana!="" && $UnidadAduana!="" && $ValorUnitarioAduana!="") {
                        $cantidad_3++;
                    } else {
                        $this->status = "Si se registra alguno de los atributos CantidadAduana, UnidadAduana o ValorUnitarioAduana, entonces deben existir los tres.";
                        $this->codigo = "188 ".$this->status;
                        return false;
                    }
                    if ($UnidadAduana!="" && $UnidadAduana!="99") {
                        if ($ValorUnitarioAduana <= 0) {
                            $this->status = "El valor del atributo cce:ComercioExterior:Mercancias:Mercancia:ValorUnitarioAduana debe ser mayor que cero cuando el valor del atributo cce:ComercioExterior:Mercancias:Mercancia:UnidadAduana es distinto de {99} que corresponde a los servicios.";
                            $this->codigo = "190 ".$this->status;
                            return false;
                        }
                    }
                    if ($CantidadAduana=="") {
                        // No existe cantidad aduana
                        $regex = "[0-9]{1,14}(\.([0-9]{1,3}))?";
                        $aux="/^$regex$/A";
                        $ok = preg_match($aux,$cantidad);
                        if (!$ok) {
                            $this->status = "Si no existe el atributo  cce:ComercioExterior:Mercancias:Mercancia:CantidadAduana entonces el valor del atributo cfdi:Comprobante:Conceptos:Concepto:cantidad debe tener como valor minimo incluyente {0.001} y debe cumplir con el patron [0-9]{1,14}(.([0-9]{1,3}))?. No cumple con el formato";
                            $this->codigo = "182 ".$this->status;
                            return false;
                        }
                        $cantidad = (double)$cantidad;
                        if ($cantidad < 0.001) {
                            $this->status = "Si no existe el atributo  cce:ComercioExterior:Mercancias:Mercancia:CantidadAduana entonces el valor del atributo cfdi:Comprobante:Conceptos:Concepto:cantidad debe tener como valor minimo incluyente {0.001} y debe cumplir con el patron [0-9]{1,14}(.([0-9]{1,3}))?. Menor que {0.001}";
                            $this->codigo = "182 ".$this->status;
                            return false;
                        }
                        $ok = $this->Checa_Catalogo("c_UnidadMedidaAduana", $unidad);
                        if (!$ok) {
                            $this->status = "Si no existe el atributo  cce:ComercioExterior:Mercancias:Mercancia:CantidadAduana entonces el valor del atributo cfdi:Comprobante:Conceptos:Concepto:unidad debe tener un valor del catalogo c_UnidadMedidaAduana ($unidad).";
                            $this->codigo = "183 ".$this->status;
                            return false;
                        }
                        $regex = "[0-9]{1,16}(\.([0-9]{1,4}))?";
                        $aux="/^$regex$/A";
                        $ok = preg_match($aux,$valorUnitario);
                        if (!$ok) {
                            $this->status = "Si no existe el atributo  cce:ComercioExterior:Mercancias:Mercancia:CantidadAduana entonces el valor del atributo cfdi:Comprobante:Conceptos:Concepto:valorUnitario debe tener como valor minimo incluyente {0.0001}, debe cumplir con el patron [0-9]{1,16}(.([0-9]{1,4}))? y debe estar redondeado a la cantidad de decimales que soporte la moneda en la que se expresan las cantidades del comprobante. No cumple con el formato {$regex}";
                            $this->codigo = "184 ".$this->status;
                            return false;
                        }
                        $valorUnitario = (double)$valorUnitario;
                        if ($valorUnitario < 0.0001) {
                            $this->status = "Si no existe el atributo  cce:ComercioExterior:Mercancias:Mercancia:CantidadAduana entonces el valor del atributo cfdi:Comprobante:Conceptos:Concepto:valorUnitario debe tener como valor minimo incluyente {0.0001}, debe cumplir con el patron [0-9]{1,16}(.([0-9]{1,4}))? y debe estar redondeado a la cantidad de decimales que soporte la moneda en la que se expresan las cantidades del comprobante. Menor que {0.0001}";
                            $this->codigo = "184 ".$this->status;
                            return false;
                        }
                        if ($unidad=="99") {
                            // Servicio no valida importe en dolares .....
                            //    creo???? 7/jun/2016
                        } else {
                            $aux1 = (double)($cantidad * $valorUnitario * $TipoCambio);
                            $aux = round($aux1 / (double)$TipoCambioUSD,2);
                            if ($aux != $ValorDolares) {
                                $this->status = "El valor del atributo ComercioExterior:Mercancias:Mercancia:ValorDolares ($ValorDolares) no cumple con los valores permitidos. (cantidad * precio * tipo)/TipoCambioUSD ($aux = ($cantidad * $valorUnitario * $TipoCambio) / $TipoCambioUSD) ";
                                $this->codigo = "191 ".$this->status;
                                return false;
                            }
                        }
                        // fin de no existe cantidad aduana
                    } else { 
                        // Si existe Cantidad aduana
                        $aux = round($CantidadAduana * $ValorUnitarioAduana,2);
                        if (abs($aux - $ValorDolares) > 0.001) {
                            $this->status = "El valor del atributo ComercioExterior:Mercancias:Mercancia:ValorDolares no cumple con los valores permitidos. No es el producto de (CantidadAduana * ValorUnitarioAduana id={$noIdentificacion}";
                            $this->codigo = "191 ".$this->status;
                            return false;
                        }
                    } // Existe cantidad aduana
                    if ($UnidadAduana=="99" || $unidad=="99") {
                        //    Por xsd no puede ser cero
                        //      por faq se valida que sea 0.01
                        if ($ValorDolares!=0.01) {
                            $this->status = "El valor del atributo ComercioExterior:Mercancias:Mercancia:ValorDolares no cumple con los valores permitidos. . Debe de ser cero (0.01) para unidad {99}";
                            $this->codigo = "191 ".$this->status;
                            return false;
                        }
                    }
                    $aux = (double)$cantidad * (double)$valorUnitario;
                    if (abs($aux - $importe) > 0.001) {
                        $this->status = "El valor del atributo cfdi:Comprobante:Conceptos:Concepto:importe de cada concepto registrado, debe ser igual al valor del atributo cfdi:Comprobante:Conceptos:Concepto:cantidad multiplicado por el valor del atributo cfdi:Comprobante:Conceptos:Concepto:valorUnitario redondeado a la cantidad de decimales que soporte la moneda en la que se expresa el CFDI.";
                        $this->codigo = "185 ".$this->status;
                        return false;
                    }
                    if ( ($UnidadAduana=="99" || $unidad=="99") &&
                         $FraccionArancelaria != "") {
                        $this->status = "No debe existir el atributo cce:ComercioExterior:Mercancias:Mercancia:FraccionArancelaria cuando el atributo cce:ComercioExterior:Mercancias:Mercancia:UnidadAduana o el atributo cfdi:Comprobante:Conceptos:Concepto:unidad tienen el valor {99} que corresponde a los servicios.";
                        $this->codigo = "186 ".$this->status;
                        return false;
                    }
                } // mercancia de cada concepto
            }  // Para cada mercancia
            if (!$esta) {
                $this->status = "Por cada concepto registrado en el elemento cfdi:Comprobante:Conceptos, debe existir una mercancia en el complemento cce:ComercioExterior, donde el atributo cce:ComercioExterior:Mercancias:Mercancia:NoIdentificacion sea igual al atributo cfdi:Comprobante:Conceptos:Concepto:noIdentificacion.";
                $this->codigo = "181 ".$this->status;
                return false;
            }
        } // Para cada concepto
        if ($hay_muestra) {
            $descuento = (double)$Comprobante->getAttribute("descuento");
            if ($suma_muestra > $descuento) {
                $this->status = "El valor del atributo cfdi:Comprobante:descuento debe ser mayor o igual  a la suma del atributo cce:ComercioExterior:Mercancias:Mercancia:ValorDolares de todos los elementos Mercancia que tengan la fraccion arancelaria {98010001} (Importaciones o exportaciones de muestras y muestrarios) convertido a la moneda en la que se expresa el comprobante.";
                $this->codigo = "187 ".$this->status;
                return false;
            }
        }
        if ($cantidad_0 > 0 && $cantidad_3 > 0) {
            $this->status = "Existe uno o mas elementos  cce:ComercioExterior:Mercancias:Mercancia que no tienen los atributos CantidadAduana, UnidadAduana y ValorUnitarioAduana.";
            $this->codigo = "189 ".$this->status;
            return false;
        }
        $this->status = "Validacion de semantica cce correcta";
        $this->codigo = "0 ".$this->status;
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
