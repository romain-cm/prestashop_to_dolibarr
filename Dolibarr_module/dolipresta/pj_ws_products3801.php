<?php
/*
* 2015 PJ CONSEIL
*
* NOTICE OF LICENSE
*
* This source file is subject to License
* You may not distribute this module even for free
*
* @author PJ CONSEIL
* @version RC2
*/
if (! defined("NOCSRFCHECK"))    define("NOCSRFCHECK",'1');

set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Load Dolibarr environment
$res=0;
// Try master.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/master.inc.php");
// Try master.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/master.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/master.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/master.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/master.inc.php");
// Try master.inc.php using relative path
if (! $res && file_exists("../../master.inc.php")) $res=@include("../../master.inc.php");
if (! $res && file_exists("../../../master.inc.php")) $res=@include("../../../master.inc.php");
if (! $res) die("Include of master fails");
require_once NUSOAP_PATH.'/nusoap.php';        // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

/**
* Ecrit l'image du produit sur le disque
*/
function ecrireImageProduit($product, $imagesB64, $imagesName, $productRef) 
{
	global $conf;
	if ($product_id != "" && $imagesB64 != "" && $imagesName != "") {
		/* 
		creation du chemin
		*/
		$sdir = $conf->service->multidir_output[$conf->entity];
		if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
			$pdir = get_exdir($product->id,2,0,0,$product,'product').$product->id ."/photos/";
		else
			$pdir .= $productRef.'/';

		$imgPath = $sdir."/".$pdir;

		//on efface tout ce qu'il y a dans le répertoire en cas d'update
		if (is_dir($imgPath)) {
			if ($dh = opendir($imgPath)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..'){
						unlink($imgPath.$file); 
					}
				}
				closedir($dh);
			}
		}
		else  mkdir($imgPath, 0777, true);
		
		/*
		ecriture du fichier
		*/
		$fileContent = base64_decode($imagesB64);
		$fichierAEcrire = $imgPath.$imagesName;
		
		if (!file_exists($fichierAEcrire)) {	
			$inF = fopen($fichierAEcrire,'a+');
			fputs($inF, $fileContent);
			fclose($inF);
		}
	}
	return 0;
}


dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
    print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrProductOrService',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
    'authentication',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
    	'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
    	'login' => array('name'=>'login','type'=>'xsd:string'),
        'password' => array('name'=>'password','type'=>'xsd:string'),
        'entity' => array('name'=>'entity','type'=>'xsd:string')
    )
);
// Define WSDL Return object
$server->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label','type'=>'xsd:string')
    )
);

$productorservice_fields = array(
    'id' => array('name'=>'id','type'=>'xsd:string'),
    'ref' => array('name'=>'ref','type'=>'xsd:string'),
    'ref_ext' => array('name'=>'ref_ext','type'=>'xsd:string'),
    'type' => array('name'=>'type','type'=>'xsd:string'),
    'label' => array('name'=>'label','type'=>'xsd:string'),
    'description' => array('name'=>'description','type'=>'xsd:string'),
    'date_creation' => array('name'=>'date_creation','type'=>'xsd:dateTime'),
    'date_modification' => array('name'=>'date_modification','type'=>'xsd:dateTime'),
    'note' => array('name'=>'note','type'=>'xsd:string'),
    'status_tobuy' => array('name'=>'status_tobuy','type'=>'xsd:string'),
    'status_tosell' => array('name'=>'status_tosell','type'=>'xsd:string'),
    'barcode' => array('name'=>'barcode','type'=>'xsd:string'),
    'barcode_type' => array('name'=>'barcode_type','type'=>'xsd:string'),
    'country_id' => array('name'=>'country_id','type'=>'xsd:string'),
    'country_code' => array('name'=>'country_code','type'=>'xsd:string'),
    'customcode' => array('name'=>'customcode','type'=>'xsd:string'),

    'price_net' => array('name'=>'price_net','type'=>'xsd:string'),
    'price' => array('name'=>'price','type'=>'xsd:string'),
    'price_min_net' => array('name'=>'price_min_net','type'=>'xsd:string'),
    'price_min' => array('name'=>'price_min','type'=>'xsd:string'),

    'price_base_type' => array('name'=>'price_base_type','type'=>'xsd:string'),

    'vat_rate' => array('name'=>'vat_rate','type'=>'xsd:string'),
    'vat_npr' => array('name'=>'vat_npr','type'=>'xsd:string'),
    'localtax1_tx' => array('name'=>'localtax1_tx','type'=>'xsd:string'),
    'localtax2_tx' => array('name'=>'localtax2_tx','type'=>'xsd:string'),

    'stock_alert' => array('name'=>'stock_alert','type'=>'xsd:string'),
    'stock_real' => array('name'=>'stock_real','type'=>'xsd:string'),
    'stock_pmp' => array('name'=>'stock_pmp','type'=>'xsd:string'),
    'warehouse_ref' => array('name'=>'warehouse_ref','type'=>'xsd:string'),		// Used only for create or update to set which warehouse to use for stock correction if stock_real differs from database

    'canvas' => array('name'=>'canvas','type'=>'xsd:string'),
    'import_key' => array('name'=>'import_key','type'=>'xsd:string'),

    'dir' => array('name'=>'dir','type'=>'xsd:string'),
    'images' => array('name'=>'images','type'=>'tns:ImagesArray')
);

//Retreive all extrafield for product
// fetch optionals attributes and labels
$extrafields=new ExtraFields($db);
$extralabels=$extrafields->fetch_name_optionals_label('product',true);
if (count($extrafields)>0) {
	$extrafield_array = array();
}
foreach($extrafields->attribute_label as $key=>$label)
{
	$type =$extrafields->attribute_type[$key];
	if ($type=='date' || $type=='datetime') {$type='xsd:dateTime';}
	else {$type='xsd:string';}

	$extrafield_array['options_'.$key]=array('name'=>'options_'.$key,'type'=>$type);
}
$productorservice_fields=array_merge($productorservice_fields,$extrafield_array);

// Define other specific objects
$server->wsdl->addComplexType(
    'product',
    'complexType',
    'struct',
    'all',
    '',
     $productorservice_fields
);


/*
 * Image of product
 */
$server->wsdl->addComplexType(
	'ImagesArray',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'image' => array(
		'name' => 'image',
		'type' => 'tns:image',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
		)
	)
);

/*
 * An image
 */
$server->wsdl->addComplexType(
	'image',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'photo' => array('name'=>'photo','type'=>'xsd:string'),
		'photo_vignette' => array('name'=>'photo_vignette','type'=>'xsd:string'),
		'imgWidth' => array('name'=>'imgWidth','type'=>'xsd:string'),
		'imgHeight' => array('name'=>'imgHeight','type'=>'xsd:string')
	)
);


// Define other specific objects
$server->wsdl->addComplexType(
    'filterproduct',
    'complexType',
    'struct',
    'all',
    '',
    array(
        //'limit' => array('name'=>'limit','type'=>'xsd:string'),
		'type' => array('name'=>'type','type'=>'xsd:string'),
	    'status_tobuy' => array('name'=>'status_tobuy','type'=>'xsd:string'),
	    'status_tosell' => array('name'=>'status_tosell','type'=>'xsd:string'),
    )
);

$server->wsdl->addComplexType(
    'ProductsArray2',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'product' => array(
            'name' => 'product',
            'type' => 'tns:product',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    )
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.


// Register WSDL
$server->register(
    'getProductOrService',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string','lang'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','product'=>'tns:product'),
    $ns,
    $ns.'#getProductOrService',
    $styledoc,
    $styleuse,
    'WS to get product or service'
);

// Register WSDL
$server->register(
    'createProductOrService',
    // Entry values
    array('authentication'=>'tns:authentication','product'=>'tns:product'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#createProductOrService',
    $styledoc,
    $styleuse,
    'WS to create a product or service'
);

// Register WSDL
$server->register(
    'updateProductOrService',
    // Entry values
    array('authentication'=>'tns:authentication','product'=>'tns:product'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#updateProductOrService',
    $styledoc,
    $styleuse,
    'WS to update a product or service'
);

// Register WSDL
$server->register(
    'deleteProductOrService',
    // Entry values
    array('authentication'=>'tns:authentication','listofid'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','nbdeleted'=>'xsd:int'),
    $ns,
    $ns.'#deleteProductOrService',
    $styledoc,
    $styleuse,
    'WS to delete a product or service'
);

// Register WSDL
$server->register(
    'getListOfProductsOrServices',
    // Entry values
    array('authentication'=>'tns:authentication','filterproduct'=>'tns:filterproduct'),
    // Exit values
    array('result'=>'tns:result','products'=>'tns:ProductsArray2'),
    $ns,
    $ns.'#getListOfProductsOrServices',
    $styledoc,
    $styleuse,
    'WS to get list of all products or services id and ref'
);

// Register WSDL
$server->register(
	'getProductsForCategory',
	// Entry values
	array('authentication'=>'tns:authentication','id'=>'xsd:string','lang'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result','products'=>'tns:ProductsArray2'),
	$ns,
	$ns.'#getProductsForCategory',
	$styledoc,
	$styleuse,
	'WS to get list of all products or services for a category'
);


/**
 * Get produt or service
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @param	string		$ref				Ref of object
 * @param	ref_ext		$ref_ext			Ref external of object
 * @param   string      $lang               Lang to force
 * @return	mixed
 */
function getProductOrService($authentication,$id='',$ref='',$ref_ext='',$lang='')
{
    global $db,$conf,$langs;

    dol_syslog("Function: getProductOrService login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

    $langcode=($lang?$lang:(empty($conf->global->MAIN_LANG_DEFAULT)?'auto':$conf->global->MAIN_LANG_DEFAULT));
    $langs->setDefaultLang($langcode);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters
    if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext)))
    {
        $error++;
        $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
    }

    if (! $error)
    {

    	$langcode=($lang?$lang:(empty($conf->global->MAIN_LANG_DEFAULT)?'auto':$conf->global->MAIN_LANG_DEFAULT));
    	$langs->setDefaultLang($langcode);

        $fuser->getrights();

        if ($fuser->rights->produit->lire || $fuser->rights->service->lire)
        {
            $product=new Product($db);
            $result=$product->fetch($id,$ref,$ref_ext);

            if ($result > 0)
            {
            	$product->load_stock();

            	$dir = (!empty($conf->product->dir_output)?$conf->product->dir_output:$conf->service->dir_output);
            	$pdir = get_exdir($product->id,2,0,0,$product,'product') . $product->id ."/photos/";
            	$dir = $dir . '/'. $pdir;

            	if (! empty($product->multilangs[$langs->defaultlang]["label"]))     		$product->label =  $product->multilangs[$langs->defaultlang]["label"];
            	if (! empty($product->multilangs[$langs->defaultlang]["description"]))     	$product->description =  $product->multilangs[$langs->defaultlang]["description"];
            	if (! empty($product->multilangs[$langs->defaultlang]["note"]))     		$product->note =  $product->multilangs[$langs->defaultlang]["note"];

            	$productorservice_result_fields = array(
	            	'id' => $product->id,
	            	'ref' => $product->ref,
	            	'ref_ext' => $product->ref_ext,
	            	'label' => $product->label,
	            	'description' => $product->description,
	            	'date_creation' => dol_print_date($product->date_creation,'dayhourrfc'),
	            	'date_modification' => dol_print_date($product->date_modification,'dayhourrfc'),
	            	'note' => $product->note,
	            	'status_tosell' => $product->status,
	            	'status_tobuy' => $product->status_buy,
	            	'type' => $product->type,
	            	'barcode' => $product->barcode,
	            	'barcode_type' => $product->barcode_type,
	            	'country_id' => $product->country_id>0?$product->country_id:'',
	            	'country_code' => $product->country_code,
	            	'custom_code' => $product->customcode,

	            	'price_net' => $product->price,
	            	'price' => $product->price_ttc,
	            	'price_min_net' => $product->price_min,
	            	'price_min' => $product->price_min_ttc,
	            	'price_base_type' => $product->price_base_type,
	            	'vat_rate' => $product->tva_tx,
	            	//! French VAT NPR
	            	'vat_npr' => $product->tva_npr,
	            	//! Spanish local taxes
	            	'localtax1_tx' => $product->localtax1_tx,
	            	'localtax2_tx' => $product->localtax2_tx,

	            	'stock_real' => $product->stock_reel,
	            	'stock_alert' => $product->seuil_stock_alerte,
	            	'pmp' => $product->pmp,
	            	'import_key' => $product->import_key,
	            	'dir' => $pdir,
	            	'images' => $product->liste_photos($dir,$nbmax=10)
            	);

                //Retreive all extrafield for thirdsparty
            	// fetch optionals attributes and labels
            	$extrafields=new ExtraFields($db);
            	$extralabels=$extrafields->fetch_name_optionals_label('product',true);
            	//Get extrafield values
            	$product->fetch_optionals($product->id,$extralabels);

            	foreach($extrafields->attribute_label as $key=>$label)
            	{
            		$productorservice_result_fields=array_merge($productorservice_result_fields,array('options_'.$key => $product->array_options['options_'.$key]));
            	}

                // Create
                $objectresp = array(
			    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
			        'product'=>$productorservice_result_fields
                );
            }
            else
            {
                $error++;
                $errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
            }
        }
        else
        {
            $error++;
            $errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
        }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }
	//var_dump($objectresp);exit;
    return $objectresp;
}


/**
 * Create a Product
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Product		$product			Product
 * @return	array							Array result
 */
function createProductOrService($authentication,$product)
{
    global $db,$conf,$langs;

    $now=dol_now();

    dol_syslog("Function: createProductOrService login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters
    if ($product['price_net'] > 0) $product['price_base_type']='HT';
    if ($product['price'] > 0)     $product['price_base_type']='TTC';

    if ($product['price_net'] > 0 && $product['price'] > 0)
    {
        $error++; $errorcode='KO'; $errorlabel="You must choose between price or price_net to provide price.";
    }


    if (! $error)
    {
        include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

        $newobject=new Product($db);
        $newobject->ref=$product['ref'];
        $newobject->ref_ext=$product['ref_ext'];
        $newobject->type=$product['type'];
        $newobject->libelle=$product['label'];    // TODO deprecated
        $newobject->label=$product['label'];
        $newobject->description=$product['description'];
        $newobject->note=$product['note'];
        $newobject->status=$product['status_tosell'];
        $newobject->status_buy=$product['status_tobuy'];
        $newobject->price=$product['price_net'];
        $newobject->price_ttc=$product['price'];
        $newobject->tva_tx=$product['vat_rate'];
        $newobject->price_base_type=$product['price_base_type'];
		$newobject->barcode=$product['barcode'];
		$newobject->barcode_type=$product['barcode_type'];
        $newobject->date_creation=$now;

        $newobject->stock_reel=$product['stock_real'];
        $newobject->pmp=$product['pmp'];
        $newobject->seuil_stock_alert=$product['stock_alert'];

        $newobject->country_id=$product['country_id'];
        if ($product['country_code']) $newobject->country_id=getCountry($product['country_code'],3);
        $newobject->customcode=$product['customcode'];

        $newobject->canvas=$product['canvas'];

        $db->begin();

        $result=$newobject->create($fuser,0);
        if ($result <= 0)        
            $error++;
        		
		if (!$error)
		{
        	// Update stock if stock count is provided and differs from database after creation or update
 			if (isset($product['stock_real']) && $product['stock_real'] != '' && $conf->global->MAIN_MODULE_STOCK = 1) {
 				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
 
 				$savstockreal=$newobject->stock_reel;
 				$newobject->load_stock();		// This overwrite ->stock_reel
 				$getstockreal = $newobject->stock_reel;
 
 				if ($savstockreal != $getstockreal) {
 					$warehouse = new Entrepot($db);
 					$warehouse->fetch(0, $product['warehouse_ref']);
 					if ($warehouse->id > 0)
					{
 						if (($savstockreal - $getstockreal) > 0) {
							//add la diff
 							$result=$newobject->correct_stock($fuser, $warehouse->id, ($savstockreal - $getstockreal), 0, 'Correction from external call (Web Service)', 0, 'WS'.dol_print_date($now,'dayhourlog'));
						}
 						if (($savstockreal - $getstockreal) < 0) {
							// moins la diff
 							$result=$newobject->correct_stock($fuser, $warehouse->id, ($getstockreal - $savstockreal), 1, 'Correction from external call (Web Service)', 0, 'WS'.dol_print_date($now,'dayhourlog'));
 						}
 						if ($result <= 0) {
							$error++;
							$newobject->error='You set a different value for stock, but correction of stock count (before='.$getstockreal.', after='.$savstockreal.') fails with error '.$newobject->error;
						}
						
					} else {
						$error++;
						$newobject->error='You set a different value for stock but we failed to find warehouse '.$product['warehouse_ref'].' to make correction.';
						
					}
				}
			}
		}
		
		
        if (! $error)
        {
			$db->commit();
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
			
			/*
			* creation de l'image
			*/
			$imagesB64 = $product['images']['image']['photo'];
			$imagesName = $product['images']['image']['photo_vignette'];
			ecrireImageProduit($newobject, $imagesB64, $imagesName, $product['ref']);
        }
        else
        {
            $db->rollback();
            $error++;
            $errorcode='KO';
            $errorlabel=$newobject->error;
        }
		
		//etablissement des liens vers les categories si présent
		if (!$error && $product['category_list']) {
			$categorie = new Categorie($db);
			foreach ($product['category_list'] AS $id => $cat) {
				$id_cat = getImportedCateg($cat);
				if ($id_cat >=0){
					$categorie->fetch($id_cat);
					$result = $categorie->add_type($newobject, 'product');
				}
			}
		}

    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}


/**
 * Update a product or service
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Product		$product			Product
 * @return	array							Array result
 */
function updateProductOrService($authentication,$product)
{
    global $db,$conf,$langs;
    
    $now=dol_now();

    dol_syslog("Function: updateProductOrService old login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
		
    // Check parameters
    if ($product['price_net'] > 0){
		$product['price_base_type']='HT';
		$update_price = $product['price_net'];
	}
    if ($product['price'] > 0){
		$product['price_base_type']='TTC';
		$update_price = $product['price'];
	}

    if ($product['price_net'] > 0 && $product['price'] > 0)
    {
        $error++; $errorcode='KO'; $errorlabel="You must choose between price or price_net to provide price.";
    }


    if (! $error)
    {
        include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

        $newobject=new Product($db);
        $newobject->fetch($product['id']);

        if (isset($product['ref']))     $newobject->ref=$product['ref'];
        if (isset($product['ref_ext'])) $newobject->ref_ext=$product['ref_ext'];
        $newobject->type=$product['type'];
        $newobject->libelle=$product['label'];    // TODO deprecated
        $newobject->label=$product['label'];
        $newobject->description=$product['description'];
        $newobject->note=$product['note'];
        $newobject->status=$product['status_tosell'];
        $newobject->status_buy=$product['status_tobuy'];
        $newobject->price=$product['price_net'];
        $newobject->price_ttc=$product['price'];
        $newobject->tva_tx=$product['vat_rate'];
        $newobject->price_base_type=$product['price_base_type'];
		$newobject->barcode=$product['barcode'];
		$newobject->barcode_type=$product['barcode_type'];
        $newobject->date_creation=$now;

        $newobject->stock_reel=$product['stock_real'];
        $newobject->pmp=$product['pmp'];
        $newobject->seuil_stock_alert=$product['stock_alert'];

        $newobject->country_id=$product['country_id'];
        if ($product['country_code']) $newobject->country_id=getCountry($product['country_code'],3);
        $newobject->customcode=$product['customcode'];

        $newobject->canvas=$product['canvas'];
		
		$extrafields=new ExtraFields($db);
		$extralabels=$extrafields->fetch_name_optionals_label('product',true);
		foreach($extrafields->attribute_label as $key=>$label)
		{
			$key='options_'.$key;
			$newobject->array_options[$key]=$product[$key];
		}
		
        $db->begin();

        $result=$newobject->update($newobject->id,$fuser);
        if ($result <= 0)
        {
            $error++;
        }
		else
		{		
			// Update price
			$newobject->updatePrice($update_price, $product['price_base_type'], $fuser);
			
        	// Update stock if stock count is provided and differs from database after creation or update
			if (isset($product['stock_real']) && $product['stock_real'] != '' && $conf->global->MAIN_MODULE_STOCK = 1)
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

				$savstockreal=$newobject->stock_reel;
				$newobject->load_stock();		// This overwrite ->stock_reel
				$getstockreal = $newobject->stock_reel;			

				if ($savstockreal != $getstockreal)
				{	
					$warehouse = new Entrepot($db);
					$warehouse->fetch(0, $product['warehouse_ref']);
									
					if ($warehouse->id > 0)
					{
						if (($savstockreal - $getstockreal) > 0)
						{	
							// add la diff
							$result=$newobject->correct_stock($fuser, $warehouse->id, ($savstockreal - $getstockreal), 0, 'Correction from external call (Web Service)', 0, 'WS'.dol_print_date($now,'dayhourlog'));
						}
						if (($savstockreal - $getstockreal) < 0)
						{
							// moins la diff					
							$result=$newobject->correct_stock($fuser, $warehouse->id, ($getstockreal - $savstockreal), 1, 'Correction from external call (Web Service)', 0, 'WS'.dol_print_date($now,'dayhourlog'));
						}
						if ($result <= 0)
						{
							$error++;
							$newobject->error='You set a different value for stock, but correction of stock count (before='.$getstockreal.', after='.$savstockreal.') fails with error '.$newobject->error;
						}
					}
					else
					{
						$error++;
						$newobject->error='You set a different value for stock but we failed to find warehouse '.$product['warehouse_ref'].' to make correction.';
					}
				}
			}
        }

		//etablissement des liens vers les categories si présent
		if (!$error && $product['category_list']) {
			$categorie = new Categorie($db);
			
			$already_linked = $categorie->containing($newobject->id, 0,'object');
			
			//nettoyage => on ne conserve que les id
			foreach ($already_linked AS $id => $cat) {
				$already_linked[$id] = $cat->id;
			}
			
			foreach ($product['category_list'] AS $id => $cat) {
				//unset de la cat en cours
				$id_cat = getImportedCateg($cat);
				if ($id_cat >=0) {
					if (array_search($id_cat, $already_linked)) {
						unset($already_linked[array_search($id_cat, $already_linked)]);
					} else {
						$categorie->fetch($id_cat);
						$result = $categorie->add_type($newobject, 'product');
					}
				}
			}
			
			//deliage des categories dont le produit ne fait plus partie
			foreach ($already_linked AS $id => $cat) {
				$categorie->fetch($cat);
				$categorie->del_type($newobject, 'product');
			}
		}

        if (! $error)
        {
            $db->commit();
			/*
			* update de l'image
			*/
			$imagesB64 = $product['images']['image']['photo'];
			$imagesName = $product['images']['image']['photo_vignette'];
			ecrireImageProduit($newobject, $imagesB64, $imagesName, $product['ref']);
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
        }
        else
        {
            $db->rollback();
            $error++;
            $errorcode='KO';
            $errorlabel=$newobject->error;
        }

    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}


/**
 * Delete a product or service
 *
 * @param	array		$authentication		Array of authentication information
 * @param	string		$listofidstring		List of id with comma
 * @return	array							Array result
 */
function deleteProductOrService($authentication,$listofidstring)
{
    global $db,$conf,$langs;

    $now=dol_now();

    dol_syslog("Function: deleteProductOrService login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	// User must be defined to user authenticated
    global $user;
    $user=$fuser;

    $listofid=explode(',',trim($listofidstring));
    $listofiddeleted=array();

    // Check parameters
    if (count($listofid) == 0 || empty($listofid[0]))
    {
        $error++; $errorcode='KO'; $errorlabel="List of Id of products or services to delete are required.";
    }

    if (! $error)
    {
    	$firsterror='';

		$db->begin();

    	foreach($listofid as $key => $id)
		{
	        $newobject=new Product($db);
	        $result=$newobject->fetch($id);

	        if ($result == 0)
	        {
	        	$error++;
		        $firsterror='Product or service with id '.$id.' not found';
		        break;
	        }
	        else
			{
		        $result=$newobject->delete();
		        if ($result <= 0)
		        {
		            $error++;
		            $firsterror=$newobject->error;
		            break;
		        }

		        $listofiddeleted[]=$id;
			}
		}

	    if (! $error)
	    {
	        $db->commit();
            //$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''), 'listofid'=>$listofiddeleted);
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''), 'nbdeleted'=>count($listofiddeleted));
	    }
	    else
	    {
	    	$db->rollback();
	        $error++;
	        $errorcode='KO';
	        $errorlabel=$firsterror;
		}
    }

    if ($error)
    {
        //$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel), 'listofid'=>$listofiddeleted);
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel), 'nbdeleted'=>0);
    }
    else if (count($listofiddeleted) == 0)
    {
   		//$objectresp=array('result'=>array('result_code'=>'NOT_FOUND', 'result_label'=>'No product or service with id '.join(',',$listofid).' found'), 'listofid'=>$listofiddeleted);
   		$objectresp=array('result'=>array('result_code'=>'NOT_FOUND', 'result_label'=>'No product or service with id '.join(',',$listofid).' found'), 'nbdeleted'=>0);
    }

    return $objectresp;
}


/**
 * getListOfProductsOrServices
 *
 * @param	array		$authentication		Array of authentication information
 * @param	array		$filterproduct		Filter fields
 * @return	array							Array result
 */
function getListOfProductsOrServices($authentication,$filterproduct)
{
    global $db,$conf,$langs;

    $now=dol_now();

    dol_syslog("Function: getListOfProductsOrServices login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $arrayproducts=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters

    if (! $error)
    {
        $sql ="SELECT rowid, ref, ref_ext";
        $sql.=" FROM ".MAIN_DB_PREFIX."product";
        $sql.=" WHERE entity=".$conf->entity;
        foreach($filterproduct as $key => $val)
        {
        	if ($key == 'type' && $val >= 0)   	$sql.=" AND fk_product_type = ".$db->escape($val);
        	if ($key == 'tosell') 				$sql.=" AND to_sell = ".$db->escape($val);
        	if ($key == 'tobuy')  				$sql.=" AND to_buy = ".$db->escape($val);
        }
		$resql=$db->query($sql);
        if ($resql)
        {
         	$num=$db->num_rows($resql);

         	$i=0;
         	while ($i < $num)
         	{
         		$obj=$db->fetch_object($resql);
         		$arrayproducts[]=array('id'=>$obj->rowid,'ref'=>$obj->ref,'ref_ext'=>$obj->ref_ext);
         		$i++;
         	}
        }
        else
        {
            $error++;
            $errorcode=$db->lasterrno();
            $errorlabel=$db->lasterror();
        }
    }

    if ($error)
    {
        $objectresp = array(
			'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),
        	'products'=>$arrayproducts
        );
    }
    else
    {
        $objectresp = array(
			'result'=>array('result_code' => 'OK', 'result_label' => ''),
        	'products'=>$arrayproducts
        );
    }

    return $objectresp;
}


/**
 * Get list of products for a category
 *
 * @param	array		$authentication		Array of authentication information
 * @param	array		$id					Category id
 * @param	$lang		$lang				Force lang
 * @return	array							Array result
 */
function getProductsForCategory($authentication,$id,$lang='')
{
	global $db,$conf,$langs;

	$langcode=($lang?$lang:(empty($conf->global->MAIN_LANG_DEFAULT)?'auto':$conf->global->MAIN_LANG_DEFAULT));
	$langs->setDefaultLang($langcode);

	dol_syslog("Function: getProductsForCategory login=".$authentication['login']." id=".$id);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);


	if (! $error && !$id)
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id must be provided.";
	}


	if (! $error)
	{
		$langcode=($lang?$lang:(empty($conf->global->MAIN_LANG_DEFAULT)?'auto':$conf->global->MAIN_LANG_DEFAULT));
		$langs->setDefaultLang($langcode);

		$fuser->getrights();

		if ($fuser->rights->produit->lire)
		{
			$categorie=new Categorie($db);
			$result=$categorie->fetch($id);
			if ($result > 0)
			{
				$table = "product";
				$field = "product";
				$sql  = "SELECT fk_".$field." FROM ".MAIN_DB_PREFIX."categorie_".$table;
				$sql .= " WHERE fk_categorie = ".$id;
				$sql .= " ORDER BY fk_".$field." ASC" ;


				dol_syslog("getProductsForCategory get id of product into category sql=".$sql);
				$res  = $db->query($sql);
				if ($res)
				{
					while ($rec = $db->fetch_array($res))
					{
						$obj = new Product($db);
						$obj->fetch($rec['fk_'.$field]);
						if($obj->status > 0 )
						{
							$dir = (!empty($conf->product->dir_output)?$conf->product->dir_output:$conf->service->dir_output);
							$pdir = get_exdir($obj->id,2) . $obj->id ."/photos/";
							$dir = $dir . '/'. $pdir;

							$products[] = array(
						    	'id' => $obj->id,
					   			'ref' => $obj->ref,
					   			'ref_ext' => $obj->ref_ext,
					    		'label' => ! empty($obj->multilangs[$langs->defaultlang]["label"]) ? $obj->multilangs[$langs->defaultlang]["label"] : $obj->label,
					    		'description' => ! empty($obj->multilangs[$langs->defaultlang]["description"]) ? $obj->multilangs[$langs->defaultlang]["description"] : $obj->description,
					    		'date_creation' => dol_print_date($obj->date_creation,'dayhourrfc'),
					    		'date_modification' => dol_print_date($obj->date_modification,'dayhourrfc'),
					            'note' => ! empty($obj->multilangs[$langs->defaultlang]["note"]) ? $obj->multilangs[$langs->defaultlang]["note"] : $obj->note,
					            'status_tosell' => $obj->status,
					            'status_tobuy' => $obj->status_buy,
		                		'type' => $obj->type,
						        'barcode' => $obj->barcode,
						        'barcode_type' => $obj->barcode_type,
		                		'country_id' => $obj->country_id>0?$obj->country_id:'',
						        'country_code' => $obj->country_code,
						        'custom_code' => $obj->customcode,

						        'price_net' => $obj->price,
						        'price' => $obj->price_ttc,
						        'vat_rate' => $obj->tva_tx,

								'price_base_type' => $obj->price_base_type,

						        'stock_real' => $obj->stock_reel,
		                		'stock_alert' => $obj->seuil_stock_alerte,
						        'pmp' => $obj->pmp,
		                		'import_key' => $obj->import_key,
		                		'dir' => $pdir,
		                		'images' => $obj->liste_photos($dir,$nbmax=10)
							);
						}

					}

					// Retour
					$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'products'=> $products
					);

				}
				else
				{
					$errorcode='NORECORDS_FOR_ASSOCIATION'; $errorlabel='No products associated'.$sql;
					$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
					dol_syslog("getProductsForCategory:: ".$c->error, LOG_DEBUG);

				}
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id;
			}
		}
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}
	
	return $objectresp;
}

// function getCateg  retourne la categorie importée
function getImportedCateg($import_key)
{
	$objs = array();
	global $db;

	$sql = "SELECT c.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
	$sql.= " WHERE c.import_key = '".$import_key."'";
	$sql.= " AND c.type = 0";
	
	$resql = $db->query($sql);

	if ($resql)
	{
		$rec = $db->fetch_array($resql);
		return $rec['rowid'];
	}
	else
	{
		$this->error=$db->error().' sql='.$sql;
		return -1;
	}
}

// Return the results.
$server->service(file_get_contents("php://input"));

