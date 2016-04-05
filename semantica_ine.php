<?php
class Ine {
    var $xml_cfd;
    var $con;
    var $codigo;
    var $status;
    // {{{ valida : semantica_ine
    function valida($xml_cfd,$conn) {
        $ok = true;
        $this->xml_cfd = $xml_cfd;
        $this->conn = $conn;
        $INE = $this->xml_cfd->getElementsByTagName('INE')->item(0);
        $TipoComite = $INE->getAttribute("TipoComite");
        $TipoProceso = $INE->getAttribute("TipoProceso");
        if ($TipoProceso == "Ordinario" && $TipoComite == "") {
            $this->status = "Atributo TipoProceso: con valor {Ordinario}, debe de existir el atributo ine:TipoComite";
            $this->codigo = "180 ".$this->status;
            return false;
        }
        $Entidades = $INE->getElementsByTagName('Entidad');
        $nb_Entidades = $Entidades->length;
        $tiene_ambito=false;
        for ($i=0; $i<$nb_Entidades; $i++) {
            $Entidad = $Entidades->item($i);
            $Ambito = $Entidad->getAttribute("Ambito");
            if ($Ambito !== "") $tiene_ambito=true;
        }
        $IdContabilidad = $INE->getAttribute("IdContabilidad");
        if ($TipoProceso == "Precampaña" || $TipoProceso == "Campaña") {
            if (!$tiene_ambito) {
                $this->status = "Atributo TipoProceso: con valor {Precampaña} o el valor {Campaña}, debe de existir al menos un elemto Ambito";
                $this->codigo = "181 ".$this->status;
                return false;
            }
            if ($TipoComite !== "") {
                $this->status = "Atributo TipoProceso: con valor {Precampaña} o el valor {Campaña}, no debe de existir TipoComite";
                $this->codigo = "182 ".$this->status;
                return false;
            }
            if ($IdContabilidad !== "") {
                $this->status = "Atributo TipoProceso: con valor {Precampaña} o el valor {Campaña}, no debe de existir IdContabilidad";
                $this->codigo = "183 ".$this->status;
                return false;
            }
        }
        if ($TipoComite == "Ejecutivo Nacional" && $nb_Entidades > 0) {
            $this->status = "Atributo TipoComite, con valor {Ejecutivo Nacional}, no debe de existir ningun elemento Entidad";
            $this->codigo = "184 ".$this->status;
            return false;
        }
        if ($TipoComite == "Ejecutivo Estatal") {
            if ($IdContabilidad !== "") {
               $this->status = "Atributo TipoComite, con valor {Ejecutivo Estatal} no debe de existir IdContabilidad";
                $this->codigo = "185 ".$this->status;
                return false;
            }
            if ($nb_Entidades == 0 || $tiene_ambito) {
               $this->status = "Atributo TipoComite, debe de existir al menos un elemento Entidad y en ningun caso debe de existir Ambito";
                $this->codigo = "186 ".$this->status;
                return false;
            }
        }
        $duplicado = false; $lista="";
        for ($i=0; $i<$nb_Entidades; $i++) {
            $Entidad = $Entidades->item($i);
            $sv_ClaveEntidad = $Entidad->getAttribute("ClaveEntidad");
            $sv_Ambito = $Entidad->getAttribute("Ambito");
            for ($j=$i+1; $j<$nb_Entidades; $j++) {
                $Entidad = $Entidades->item($j);
                $ClaveEntidad = $Entidad->getAttribute("ClaveEntidad");
                $Ambito = $Entidad->getAttribute("Ambito");
                if ($sv_ClaveEntidad==$ClaveEntidad &&
                    $sv_Ambito==$Ambito) {
                        $lista .= "$ClaveEntidad:$Ambito,";
                        $duplicado=true;
                }
            }
        }
        if ($duplicado) {
            $this->status = "No repetir la combinacion de ClaveEntidad con Ambito {$lista}";
            $this->codigo = "187 ".$this->status;
            return false;
        }
        return $ok;
    }
}
    // }}}
