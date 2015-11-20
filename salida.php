<?php
//Salida de Exportacion
include('../../config.inc.php');
include('../../classes/forms.class.php');
include('../../classes/tables.class.php');
include('../../classes/filemanager.class.php');
class exportacion extends appDefault{
  public $sendDir;
  public $frs,$fcc,$i,$fd;
  public $cabconte='CABCONTE.txt';
  public $reseRr01Name='reserr01.txt';
  public $impCtr01Name='IMPCTR01.txt';
  public $detCtr01Name='DETCTR01.txt';
  public function __construct(){
    parent::__construct();
    global $SENDDIR;
    $this->sendDir=$SENDDIR;  
    $this->fcc=new fileManager();  
    $this->fi=new fileManager();  
    $this->fd=new fileManager();  
  }
  public function buscar(){
    $f=new complexForm('frmBuscar','Envio Exportaci&oacute;n - Buscar Exportaci&oacute;n');
    $f->addBody('<table><tr><td>Exportaci&oacute;n</td><td>'.$f->genEdit('anio',date('Y'),'',4,4).'-'.$f->genEdit('manifiesto').'</td></tr></table>');
    $f->addButton('btnBuscar','submit','Buscar');
    $f->addButton('btnSalir','button','Salir',array('onClick'=>'"window.location.href=\''.$this->getPrevPage().'\'"'));
    $this->insertObject($f);
    $this->addBody('[&'.$f->getName().'&]');
    $f->setInitFocus('manifiesto');
    if ($this->getVar('btnBuscar')){
      $rs=$this->db->Execute('select * from sp_aduListaEnvioEx(?,?)',array($_REQUEST['anio'],$_REQUEST['manifiesto']));
      $data=$rs->getArray();
      $final=$rs->FieldCount();
      for ($i=0;$i<$rs->RecordCount();$i++){
        $data[$i][$final]=sprintf('<a href="salida_exportacion.php?opc=insert&codigo_aduManifiesto=%s&cp=%s">Envio</a>',$data[$i][0],$data[$i][1]);
      }
      global $tableProperty;
      $t=new Table('tb',$data,array('Exportaci&oacute;n','C/P','Documento de Transporte','Estado','Opciones'),10,$tableProperty);
      $this->insertObject($t);
      $this->addBody('[&'.$t->getName().'&]');
    }
  }

  public function insert(){
    $id=$this->getVar('codigo_aduManifiesto');
    $cp=$this->getVar('cp');
    $recepcion=(int)substr($id,8);
    $empresa=$this->db->Execute('select codigoAlmacen_empEmpresa,codigoTacna_empEmpresa from empEmpresa');
    $periodo=$this->db->Execute('select * from VW_ALMPERIODOACTIVO');
    $data=$this->db->Execute('select * from sp_envGetExportacion(?,?)',array($id,$cp));
    $via=$this->db->Execute('select codigo_aduViaTrans,desc_aduViaTrans from aduViaTrans order by numero_aduViaTrans');
    $doc=$this->db->Execute('select id_aduDocDec,desc_aduDocDec from aduDOCDEC order by id_aduDocDec');
    $nave=$this->db->Execute('select placa_logDuim from logDuim where codigo_aduManifiesto=?',array($id));

    //Extraemos la version Antigua
    $rs=$this->db->Execute("SELECT E.HAGENTE_ADUENVIO,E.FAGENTE_ADUENVIO,E.CODIGO_ADUDUA,E.RUC_ADUENVIO,D.CODIGO_EXTDESPACHADOR, P.NOMBRESAPELLIDOS_GENPERSONA FROM ADUENVIO e LEFT JOIN EXTDESPACHADOR D ON E.ID_EXTDESPACHADOR=D.ID_GENPERSONA LEFT JOIN GENPERSONA P ON D.ID_GENPERSONA=P.ID_GENPERSONA WHERE E.CODIGO_ADUMANIFIESTO=?",array($id));
      //$rs=$this->db->Execute("SELECT HAGENTE_ADUENVIO,FAGENTE_ADUENVIO,CODIGO_ADUDUA,RUC_ADUENVIO FROM ADUENVIO WHERE CODIGO_ADUMANIFIESTO=?",array($id));
    //echo $id;
    //echo $rs->RecordCount();
    if ($rs->RecordCount()>0){
      $fagente=dbDateToNormalDate($rs->Fields('FAGENTE_ADUENVIO'));
      $hagente=$rs->Fields('HAGENTE_ADUENVIO');
      $cdespachador=$rs->Fields('CODIGO_EXTDESPACHADOR');
      $ndespachador=$rs->Fields('NOMBRESAPELLIDOS_GENPERSONA');
      $ruc=$rs->Fields('RUC_ADUENVIO');
      $dec=explode('-',$rs->Fields('CODIGO_ADUDUA'));
      if ($rs->Fields('CODIGO_ADUDUA')=='') $dec=array($empresa->Fields('codigoTacna_empEmpresa'),$periodo->fields[0],'40','');
      else $dec=explode('-',$rs->Fields('CODIGO_ADUDUA'));
    }
    else {
      $ruc='';
      $fagente=date('d/m/Y');
      $hagente=date('H:i:s');
      $cdespachador='';
      $ndespachador='';
      $dec=array($empresa->Fields('codigoTacna_empEmpresa'),$periodo->fields[0],'40','');
      //      echo 'entro';
    }


    $f=new complexForm('frm','Envio Salida de Exportaci&oacute;n - '.$id,'','post');
    $f->addBody($f->genHidden('opc','guardar').$f->genHidden('id',$id).$f->genHidden('cp',$cp).
'<table>
	<tr>
		<td>Recepci&oacute;n</td>
		<td>'.$f->genInteger('recepcion',$recepcion).'</td>
		<td>Fecha de Salida</td>
		<td>'.$f->genDateEdit('fech_sali','').'</td>
		<td>Hora de Salida</td>
		<td>'.$f->genTimeEdit('hora_sali','').'</td>
	</tr>
	<tr>
		<td>Despachador</td><td colspan="3">'.$f->genEdit('cdespachador',$cdespachador,'',4,4).'-'.$f->genEdit('ndespachador',$ndespachador,'',100,40).'</td>
		<td>Placa</td>
		<td>'.$f->genEdit('nave',$nave->Fields('placa_logDuim')).'</td>
	</tr>
	<tr>
		<td>Declaraci&oacute;n</td>
		<td colspan="3">'.$f->genEdit('dec1',$dec[0],'',3,3).' '.$f->genInteger('dec2',$dec[1],'',4,4).' '.$f->genEdit('dec3',$dec[3]).' '
                //.$f->genEdit('dec4',$dec[2],'',3,3)
                .$f->genSelectList('dec4', 
                                   array('40 - EXP.DEFINITIVA',
					 '51 - EXP.TEMP.PARA RE IMPORTACIONES EN MISMO ESTADO',
					 '52 - EXP.TEMP.PARA PERFECCIONAMIENTO PASIVO',
					 '48 - EXP.SIMPLIFICADA'),
                                   array(40,51,52,48),
                   $dec[2]).'
		</td><td>Ult. Recepci&oacute;n</td>
		<td>'.$f->genCheck('urecepcion','',1,1).'</td>
	</tr>
	<tr>
		<td>Declarante</td>
		<td colspan="3">'.$f->genSelectListArray('ID_ADUDOCDEC',$doc->getArray(),4).'</td>
		<td>N&uacute;mero</td><td>'.$f->genEdit('numero',$ruc).'</td>
	</tr>
	<tr>
		<td>Tipo de Transporte</td>
		<td>'.$f->genSelectList('tipo_cnt', 
                                   array('C - CONTENEDOR',
					 'P - PALLET',
					 'B - BULTO'),
                                   array('C','P','B'),
                   'C').'</td>
		<td>C&oacute;digo de Operaci&oacute;n</td>
		<td>'.$f->genSelectList('codi_opera', 
                                   array('01 - DUA DE EXPORTACION',
					 '13 - EXPORTACION SIMPLIFICADA'),
                                   array('01','13'),
                   '01').'</td>
		<td>Tipo Ingreso</td>
		<td>'.$f->genSelectList('tipo_ingre', 
                                   array('1 - CONTENEDOR INGRESO LLENO',
					 '2 - CONTENEDOR SE LLENO EN TERMINAL',
					 '0 - BULTOS'),
                                   array('1','2','0'),
                   '1').'</td>
		
	</tr>
	<tr>
		<td>V&iacute;a de Transporte</td>
		<td>'.$f->genSelectListArray('ID_ADUVIATRANS',$via->getArray(),$data->Fields('procedencia_aduManifiesto')).'</td>
		<td>Almacen</td><td colspan="2">'.$f->genStatic('alm1',$empresa->Fields('codigoAlmacen_empEmpresa'),4).' '.$f->genEdit('alm2').'</td>
	</tr>
	<tr>
		<td>Bultos</td>
		<td>'.$f->genInteger('bultos',$data->Fields('nroBultos_aduManifiesto')).'</td>
		<td>Peso</td>
		<td>'.$f->genFloat('peso',$data->Fields('pesoBultos_aduManifiesto')).'
		<td>Tara</td>
		<td>'.$f->genEdit('tara','').'
	</tr>
	<tr>
		<td>Contenedor</td>
		<td>'.$f->genEdit('contenedor',$data->Fields('marcaContenedor_aduCartaPorte')).'</td>
		<td>Precinto</td>
		<td>'.$f->genEdit('precinto','').'</td>
		<td>Condici&oacute;n</td>
		<td>'.$f->genSelectList('ccontenedor',array('FCL','LCL'),array('FCL','LCL')).'</td>
	</tr>
	<tr>
		<td>Tama&ntildeo del Contenedor</td>
		<td>'.$f->genSelectList('tamano', 
                                   array('10 - PIES',
					 '20 - PIES',
					 '00 - OTROS(BULTOS Y PALLETS)'),
                                   array('10','20','00'),
                   '10').'</td>
		<td>Precinto Externo</td>
		<td>'.$f->genEdit('n_pre_otro','').'</td>
		<td>Pa&iacute;s Destino</td>
		<td>'.$f->genEdit('pais_desti','').'</td>
	</tr>
	<tr>
		<td>Descripci&oacute;n</td>
		<td colspan=5>'.$f->genMemo('desc',$data->Fields('descMercaderia_aduCartaPorte'),5,80).'</td>
	</tr>
</table>');
    $f->addButton('btnEnviar','submit','Enviar');
    $f->addButton('btnDerivar','submit','Derivar');
    $f->addButton('btnGuardar','submit','Guardar');
    $f->addButton('btnSalir','button','Salir',array('onClick'=>'"window.location.href=\'salida_exportacion.php\'"'));
    $this->insertObject($f);
    $this->addBody('[&'.$f->getName().'&]');
  }
  public function guardar($codigo_aduManifiesto,$cp){
    if (isset($_REQUEST['btnDerivar']))
    {
	$table = '
	CREATE TABLE ENVIO_EXPORTACION(
  CODIGO_ADUDUA DUA NOT NULL,
  CODIGO_ADUMANIFIESTO MANIFIESTO,
  ID_EXTEMPRESA Integer,
  ID_EXTAGENCIA Integer,
  FOB_ADUDUA Decimal(10,2),
  CIF_ADUDUA Decimal(10,2),
  NROBULTOS_ADUDUA NUMERO DEFAULT 0,
  PESOBULTOS_ADUDUA PESO DEFAULT 0.00,
  NROTOTALBULTOS_ADUDUA NUMERO DEFAULT 0,
  PESOTOTALBULTOS_ADUDUA PESO DEFAULT 0.00,
  REGIMEN_ADUDUA Varchar(2),
  APROBGERENCIA_ADUDUA "BOOLEAN" DEFAULT 0,
  APROBCAJA_ADUDUA "BOOLEAN" DEFAULT 0,
  CODIGO_ADUCARTAPORTE CARTAPORTE,
  ID_ADUCARTAPORTE Numeric(18,0),
  NUMERO_ADUDUA Integer,
  ANIO_ADUDUA Varchar(4),
  PRIMARY KEY (CODIGO_ADUDUA)
);
	';
	$query = "INSERT INTO SALIA_EXPORTACION (CODI_ADUAN, ANO_ORDEN, NUME_ORDEN, ANO_PRESE, TIPO_CNT, NUME_CNT, ANO_DOCASO, CODI_REGI, CODI_OPERA, NUM_DOCASO, TIPO_DOCUM, LIBR_TRIBU, PESO_NETO, TARA, N_PRE_ADUA, N_PRE_OTRO, TAMANO, TIPO_INGRE, CODI_CONDI, DESC_MERC, NAVE, ANO_MANIF, NUME_MANIF, MOTI_ANULA, FECH_SALI, HORA_SALI, ADU_DOCASO, PAIS_DESTI, COD_ANULA, ULT_TRANS) VALUES ()";
	echo $query;

	$table_test = '
	CREATE TABLE TEST(
        NAME Varchar(14),
        AGE Integer
        );';
	$query_test = "INSERT INTO TEST (NAME, AGE) VALUES ('Vallenova', 2)";
        $result = $this->db->Execute($query_test);

    }
    else
    {
    //print_r($_REQUEST);
    $formatCabconte=array('CODI_ADUAN'=>3, 'ANO_ORDEN'=>4, 'NUME_ORDEN'=>6, 'ANO_PRESE'=>4, 'TIPO_CNT'=>1, 'NUME_CNT'=>15, 'ANO_DOCASO'=>4, 'CODI_REGI'=>2, 'CODI_OPERA'=>2, 'NUM_DOCASO'=>6, 'TIPO_DOCUM'=>1, 'LIBR_TRIBU'=>11, 'PESO_NETO'=>11.2 , 'TARA'=>11.2, 'N_PRE_ADUA'=>15, 'N_PRE_OTRO'=>15, 'TAMANO'=>2.0, 'TIPO_INGRE'=>1, 'CODI_CONDI'=>3, 'DESC_MERC'=>250, 'NAVE'=>50, 'ANO_MANIF'=>4, 'NUME_MANIF'=>5, 'MOTI_ANULA'=>250, 'FECH_SALI'=>8, 'HORA_SALI'=>8, 'ADU_DOCASO'=>3, 'PAIS_DESTI'=>2, 'COD_ANULA'=>3, 'ULT_TRANS'=>2 );
    
    $formatReserr01=array('CODI_ADUAN'=>3, 'ANO_ORDEN'=>4, 'NUME_ORDEN'=>6, 'CODI_REGI'=>2, 'CODI_ERROR'=>4, 'NUME_SERIE'=>4, 'DESC_ADVER'=>100, 'NUME_ITEM'=>4);

    $formatImpCtr01=array('AGENTE'=>4, 'FECHA'=>8, 'NUM_REG'=>6.0, 'TOTAL_FOB'=>15.3, 'PESO'=>15.3, 'CASILLA'=>16, 'CLAVE'=>4, 'NUM_REGB'=>6.0, 'TIPO_OPER'=>1, 'CLAVE_ELE'=>16, 'RUC'=>11, 'CEMPSOFT'=>4 );
    $formatImpCtr01b=array('AGENTE'=>4, 'FECHA'=>8, 'NUM_REG'=>6.0, 'TOTAL_FOB'=>15, 'PESO'=>15, 'CASILLA'=>16, 'CLAVE'=>4, 'NUM_REGB'=>6.0, 'TIPO_OPER'=>1, 'CLAVE_ELE'=>16, 'RUC'=>11, 'CEMPSOFT'=>4 );

    $formatDetCtr01=array('CODI_ADUAN'=>3, 'ANO_ORDEN'=>4, 'NUME_ORDEN'=>6, 'CODI_REGI'=>2, 'TIPO_TRANS'=>2, 'ANO_PRESE'=>4, 'NUM_CORRE'=>6);
    $directorio=$this->sendDir.'/'.$codigo_aduManifiesto;
    $fcc=&$this->fcc;
  
    $fcc->makeDir($directorio); 
  
    //$fcc->openFile($directorio.'/'.$this->cabconte);
    $fcc->openFile($this->cabconte);
    $fcc->setFormat($formatCabconte);
  
    //$frs=&$this->frs;
    //$frs->openFile($directorio.'/'.$this->reseRr01Name);
    //$frs->setFormat($formatReserr01);
  
    $rs=$this->db->Execute('select codigoTacna_empEmpresa,CODIGOALMACEN_EMPEMPRESA, CASILLAELEC_EMPEMPRESA, CLAVE_EMPEMPRESA from empEmpresa');
    $codCiudad=$rs->fields[0];
    $codEmpresa=$rs->fields[1];
    $casillaElectronica=$rs->Fields('CASILLAELEC_EMPEMPRESA');
    $clave=$rs->Fields('CLAVE_EMPEMPRESA');
    $rs->Close();
    //$rs=$this->db->Execute('select CODIGO_ALMPERIODO from ALMPERIODO where ESTADO_ALMPERIODO=1');
    $rs=$this->db->Execute('select CODIGO_ALMPERIODO from VW_ALMPERIODOACTIVO');
    $periodo=$rs->fields[0];
    $rs->Close(); 
    //dataCC
    $dataCC=array();
    $dataRS=array();
	$dataCC['CODI_ADUAN']=$codCiudad;
	$dataCC['ANO_ORDEN']=$periodo;
	$dataCC['NUME_ORDEN']=$this->getVar('recepcion');
	$dataCC['ANO_PRESE']=date('Y');
	$dataCC['TIPO_CNT']= $this->getVar('tipo_cnt');
	$dataCC['NUM_CNT']=$this->getVar('contenedor');
	$dataCC['ANO_DOCASO']=$this->getVar('dec2');
	$dataCC['CODI_REGI']=$this->getVar('dec4');
	$dataCC['CODI_OPERA']= $this->getVar('codi_opera');
	$dataCC['NUM_DOCASO']=$this->getVar('dec3');
	$dataCC['TIPO_DOCUM']=$this->getVar('ID_ADUDOCDEC');
	$dataCC['LIBR_TRIBU']=$this->getVar('numero');
	$dataCC['PESO_NETO']=$this->getVar('peso');
	$dataCC['TARA']=$this->getVar('tara');
	if(trim($this->getVar('precinto')) == '') $dataCC['N_PRE_ADUA']='SIN PRESINTO';//CONSULTAR DE DONDE SACA EL PRECINTO
	else $dataCC['N_PRE_ADUA']=$this->getVar('precinto');
	if(trim($this->getVar('n_pre_otro')) == '') $dataCC['N_PRE_OTRO']='SIN PRESINTO';//CONSULTAR DE DONDE SACA EL PRECINTO
	else $dataCC['N_PRE_OTRO']=$this->getVar('n_pre_otro');
	$dataCC['TAMANO']=$this->getVar('tamano');
	//$dataCC['TIPO_INGRE']=//POR DEFINIR DROPDOWN
	$dataCC['CODI_CONDI']=$this->getVar('ccontenedor');
	$dataCC['DESC_MERC']=str_replace("\r\n","",$this->getVar('desc'));
	$dataCC['NAVE'] = $this->getVar('nave');
	 
	//dataCC['ANO_MANIF']=
	//dataCC['NUM_MANIF']=
	//dataCC['NOTI_ANULA']=
	//dataCC['FECH_SALI']=
	//dataCC['HORA_SALI']=
	$dataCC['ADU_DOCASO']=$this->getVar('dec2');
	//dataCC['PAIS_DESTI']=//POR DEFINIR TEXTBOX
	//dataCC['COD_ANULA']=
	
	if ($dataCC['CODI_REGI']=='40'){
		if (isset($_REQUEST['urecepcion'])) $dataCC['ULT_TRANS']='01';
    		else  $dataCC['ULT_TRANS']='00';
	}else{
		$dataCC['ULT_TRANS']='00';
	}
	$fcc->writeFormat($dataCC); 
	

    $dataRS['CODI_ADUAN']=$codCiudad;
    $dataRS['ANO_ORDEN']=$periodo;
    /*    $cp=$this->db->Execute('select cp.id_aduCartaPorte,cp.codigo_aduCartaPorte, cp.nroBultosMani_aduCartaPorte, cp.NroBultos_aduCartaPorte, cp.NROBULTOSBUEN_ADUCARTAPORTE, cp.nroBultosMal_aducartaPorte, cp.pesoBultosMani_aduCartaPorte, cp.pesoBultos_aduCartaPorte, cp.PESOBULTOSBUEN_ADUCARTAPORTE, cp.pesoBultosMal_aduCartaPorte, e.razon_extEmpresa, cp.descMercaderia_aduCartaPorte, d.CODIGODOCHIJA_LOGDUIMDET  from logDuimDet d inner join aduCartaPorte cp on d.id_aduCartaPorte=cp.id_aduCartaPorte inner join extEmpresa e on cp.id_extEmpresa=e.id_extEmpresa where d.codigo_aduManifiesto=?',array($codigo_aduManifiesto));*/
    $cp=$this->db->Execute("select cp.id_aduCartaPorte,cp.codigo_aduCartaPorte, cp.nroBultosMani_aduCartaPorte, cp.NroBultos_aduCartaPorte, cp.NROBULTOSBUEN_ADUCARTAPORTE, cp.nroBultosMal_aducartaPorte, cp.pesoBultosMani_aduCartaPorte, cp.pesoBultos_aduCartaPorte, cp.PESOBULTOSBUEN_ADUCARTAPORTE, cp.pesoBultosMal_aduCartaPorte, e.razon_extEmpresa, cp.descMercaderia_aduCartaPorte, '' AS CODIGODOCHIJA_LOGDUIMDET  from vw_adumanifiestocartaporte mcp inner join aduCartaPorte cp on mcp.id_aducartaporte=cp.id_aducartaporte inner join extEmpresa e on cp.id_extEmpresa=e.id_extEmpresa where mcp.codigo_aduManifiesto=? and mcp.id_aducartaporte=?",array($codigo_aduManifiesto,$cp));

    $fd=&$this->fd;
    //$fd->openFile($directorio.'/'.$this->detCtr01Name);
    $fd->openFile($this->detCtr01Name);
    $fd->setFormat($formatDetCtr01);  
    $dataDet=array();
    $dataDet['CODI_ADUAN']=$codCiudad;
    $dataDet['ANO_ORDEN']=$periodo;
    $dataDet['TIPO_TRANS']=26;
    //Guardamos la DUA  
    $dua=$_REQUEST['dec1'].'-'.$_REQUEST['dec2'].'-'.$_REQUEST['dec4'].'-'.$_REQUEST['dec3'];
    $this->db->Execute('execute procedure SP_ENVSETENVIO(?,?,?,?,?,?,?)',array($codigo_aduManifiesto,normalDateToDbDate($_REQUEST['fagente']),$_REQUEST['hagente'],$_REQUEST['cdespachador'],$_REQUEST['ndespachador'],$dua,$_REQUEST['numero']));
  
	
	
    while (!$cp->EOF){
      //$numero=$this->db->Execute('select numero from SP_ENVGETNUMEROENVIOEX');
      $numOrden=$this->getVar('recepcion');	
      //$numOrden=$numero->fields[0];    
      //$numero->Close();    
      //      $this->db->Execute('execute procedure SP_ENVACTNUMEROENVIOEX');    

    
      $dataRS['NUME_ORDEN']=$numOrden;
      //$frs->writeFormat($dataRS);
        
      $dataDet['NUME_ORDEN']=$numOrden;
      $dataDet['CODI_REGI']=$this->getVar('dec4');//
      $dataDet['ANO_PRESE']=date('Y');//
      $fd->writeFormat($dataDet);
    
      $cp->MoveNext();
    }
    $fi=&$this->fi;
    //$fi->openFile($directorio.'/'.$this->impCtr01Name);
    $fi->openFile($this->impCtr01Name);
    $fi->setFormat($formatImpCtr01);
    $dataImp=array();
    $dataImp['AGENTE']=$codEmpresa;
    $dataImp['FECHA']=$fi->dbDateToPlainDate(date('Y-m-d'));
    $dataImp['NUM_REG']=$cp->RecordCount();
    $dataImp['TOTAL_FOB']='0.000';
    //$mani->Fields('nroBultos_aduManifiesto');
    //    $dataImp['PESO']=$mani->Fields('pesoBultos_aduManifiesto');
    $dataImp['PESO']=$this->getVar('peso');
    $dataImp['CASILLA']=$casillaElectronica;
    $dataImp['CLAVE']=$clave;
    $dataImp['TIPO_OPER']=7;
    $fi->writeFormat($dataImp);
    //AGREGAR 2DA FILA
    //$fi->setFormat($formatImpCtr01b);
    //$dataImp['FECHA']='';
    //$dataImp['TOTAL_FOB']='';
    //$dataImp['PESO']='';
    //$dataImp['CASILLA']='';
    //$dataImp['CLAVE']='';
    //$fi->writeFormat($dataImp);
    //$this->setBody('');
    //$this->setFooter('');
    exec("chmod a+rwx ./exportacion/poliza.zip");
    exec("rm ./exportacion/poliza.zip");
    exec(sprintf("zip ./exportacion/poliza.zip %s %s %s",$fd->filename,$fcc->filename,$fi->filename));
    exec("chmod a+rwx ./exportacion/poliza.zip");
    if (isset($_REQUEST['btnEnviar']))
      $this->Enviar("exportacion/poliza.zip",$codigo_aduManifiesto);
    elseif (isset($_REQUEST['btnGuardar'])){
      $this->addBody('<script type="text/javascript">window.open("exportacion/poliza.zip","","")</script>');
      $this->addBody("Si no se pudo guardar haga click Derecho <a href='exportacion/poliza.zip'>aqui</a> y seleccione Guardar Como (Save As..)");
      $this->addBody("<br/><input type='button' value='Salir' onClick='window.location.href=\"salida_exportacion.php\"'/>");
    }
    //$this->addBody('<script type="text/javascript">window.location.href="exportacion4.php?opc=enviar"</script>');
    // $this->addBody('<script type="text/javascript">window.location.href="exportacion/poliza.zip"</script>');
    //$this->addBody('<script type="text/javascript">window.location.href="exportacion.zip.php?id='.$codigo_aduManifiesto.'&estado=1&file[0]='.$fcc->filename.'&file[1]='.$fd->filename.'&file[2]='.$fi->filename.'"</script>');
    }
  }
  public function Enviar($filename,$id){
	
    chmod($filename, 0777);
    $f=fopen($filename,'r');
    $archivo=fread($f,filesize($filename));
    $soap=new SoapClient('http://www.aduanet.gob.pe:80/ws-ad-pd/ws-ad-pd?WSDL');
    $numeroEnvio=$soap->__call('enviaArchivoWebService',
			       array('toperador'=>'T',
				     'operador'=>'3162',
				     'clave'=>'ae4348*',
				     'aduana'=>'172',
				     'archivoEnvioByte'=>$archivo,
				     'nombreArchivo'=>'poliza.zip'
				     )
			       );
    if (is_soap_fault($numeroEnvio))
      $this->addBody("Error al enviar el archivo");
    else{		
      $this->addBody("Envio del Manifiesto $id: <font color='red'>".$numeroEnvio."</font><br/>");
      $numeroEnvio=substr($numeroEnvio,0,strpos($numeroEnvio,':'));	
      $this->db->Execute("update aduenvio set nroaduana_aduenvio=? where codigo_adumanifiesto=?",array($numeroEnvio,$id));
    }
    $this->addBody("<br/>Descargar el Archivo <a href='exportacion/poliza.zip' target='blank'>AQUI</a>");
    $this->addBody("<br/><input type='button' value='Salir' onClick='window.location.href=\"exportacion4.php\"'/>");
  }
  public function Run(){
    switch ($this->getVar('opc')){
    case 'guardar':$this->guardar($this->getVar('id'),$this->getVar('cp'));break;
    case 'insert':$this->insert();break;
      //case 'enviar':$this->Enviar('exportacion/poliza.zip');
    default: $this->buscar();break;
    }
    parent::Run();
  }
}
$exportacion=new exportacion();
//$exportacion->db->debug=true;
$exportacion->Run();
?>
