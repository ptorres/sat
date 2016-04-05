-- pac_catalogos
--     Lista de claves para validar los 
drop table pac_catalogos;

create table pac_catalogos (
    cata_cata varchar(30),          -- NOmbre del catalogo
    cata_llave varchar(30),         -- Campo llave para buscar
    cata_prm1  varchar(30) default '', -- Parametro1 para restringir busqueda
    cata_prm2  varchar(30) default '', -- Parametro2 para restringir busqueda
    regex_cp   varchar(80),         -- Expresion regular para codigo postal
    regex_taxid varchar(80),        -- Expresion regular para taxid
    lista_taxid varchar(80),        -- Se valida taxid por lista
    c_CP varchar(5),                -- Codigo postal asociado
    c_Estado varchar(30),           -- Codigo de estado asociado
    c_Municipio varchar(30),        -- Codigo de municipio asociado
    c_Localidad varchar(30),        -- Codigo de Localidad asociado
    descripcion varchar(120),        -- Descripcion (informativa)
    decimales smallint,             -- decimales (asociados)
    primary key (cata_cata, cata_llave, cata_prm1, cata_prm2)
);

