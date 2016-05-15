<?php
// files
$did = "PA162355460";
$tab_file   = $did.".tab";
$vocab_file = $did.".owl";
$data_file  = $did.".rdf";

$pharmgkb_base  = "http://bio2rdf.org/pharmgkb:";
$pharmgkb_vocab = "http://bio2rdf.org/pharmgkb_resource:";
$pharmgkb_data  = "http://bio2rdf.org/pharmgkb_data:$did#";
$vocab_file_uri = "http://translationalmedicineontology.googlecode.com/svn/trunk/data/pharmgkb_raw_data/$did.owl";
$data_file_uri  = "http://translationalmedicineontology.googlecode.com/svn/trunk/data/pharmgkb_raw_data/$did.rdf";

$rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
$rdfs = "http://www.w3.org/2000/01/rdf-schema#";
$owl = "http://www.w3.org/2002/07/owl#";

$patient_type_uri = TypeDeclaration($pharmgkb_vocab,"Patient", $owl.'Class');
$has_value_uri    = TypeDeclaration($pharmgkb_vocab,"has value",$owl."DatatypeProperty");

// create ontology from header
$declare_buf = '';
$fp = fopen($tab_file,"r");
$l = fgets($fp);
$header = explode("\t",trim($l));
foreach($header AS $k => $v) {
	if($v[0] == '"') $v = substr($v,1,-1);
	$header[$k] = $v;
	$header_uri[$k] = TypeDeclaration($pharmgkb_vocab, $v, $owl."Class");
	
	$rel = "relation: $v";
	$rid = TypeDeclaration($pharmgkb_vocab, $rel, $owl.'ObjectProperty');
	
	// restrict the domain and range to the types defined in this file
	$declare_buf .= Triple($rid, $rdfs."domain", $patient_type_uri);
	$declare_buf .= Triple($rid, $rdfs."range", $header_uri[$k]);
}



$uid = 0;$buf = '';
while($l = fgets($fp)) {
	//if($uid >= 100) break;
	$a = explode("\t",trim($l));
	$sid = $a[0];
	$sid_uri = $pharmgkb_base.$sid;
	$buf .= Triple($sid_uri,$rdf."type",$patient_type_uri);
	
	foreach($header AS $k => $v) {
		if(!isset($a[$k])) break; // end of the line
		if($a[$k] == "NA" || $a[$k] == '') continue; // unknown is default in rdf/owl
		if($a[$k][0] == '"') $a[$k] = substr($a[$k],1,-1); // get rid of double quotes, sometimes added by spreadsheet programs
		$a[$k] = addslashes($a[$k]);
		
		
		$rel = "relation: $v";
		$rid = TypeDeclaration($pharmgkb_vocab, $rel, $owl.'ObjectProperty');
		
		// var_dump("id: $a[$k]").PHP_EOL;

		$buf .= SpecialCase($sid_uri,$rid,$uid,$header_uri[$k],$has_value_uri,$k,$v,$a[$k]);
	}
}
fclose($fp);

$vocab_header = Triple($vocab_file_uri,$rdf."type", $owl."Ontology").PHP_EOL.
 LiteralTriple($vocab_file_uri,$rdfs."label", "Vocabulary for PharmGKB data file identified by PA162355460");
$data_header = Triple($data_file_uri,$rdf."type", $owl."Ontology").PHP_EOL.
 LiteralTriple($data_file_uri,$rdfs."label", "RDF version of PharmGKB data file PA162355460");
file_put_contents($vocab_file, $vocab_header.FormatTypeDeclarations().$declare_buf);
file_put_contents($data_file, $data_header.FormatTypeDeclarations().$buf);

// end


function Triple($s,$p,$o) 
{
	return "<$s> <$p> <$o>.";
}
function LiteralTriple($s,$p,$l)
{
	if($l && $l[0] == '"') $l = substr($l,1,-1);
	return '<'.$s.'> <'.$p.'> "'.$l.'".';
}

// makes a variable TypeDeclaration by adding to a global array variable
function TypeDeclaration($base_uri, $string, $type = null, $subClassOf = null) {
	global $declarations;
	
	if($string && $string[0] == '"') $string = substr($string,1,-1);
	$id = $base_uri.md5($string);
	if(!isset($declarations[$id])) {
		$declarations[$id]['label'] = $string;
		if(isset($type)) $declarations[$id]['type'] = $type;
		if(isset($subClassOf)) $declarations[$id]['subClassOf'] = $subClassOf;
	}
	return $id;
}

function FormatTypeDeclarations()
{
	global $declarations, $rdf, $rdfs;
	$buf = '';
	foreach($declarations AS $k => $v) {
		$buf .= LiteralTriple($k, $rdfs.'label', $v['label']).PHP_EOL;
		if(isset($v['type']))
			$buf .= Triple($k, $rdf.'type', $v['type']).PHP_EOL;
		if(isset($v['subClassOf']))
			$buf .= Triple($k, $rdfs.'subClassOf', $v['subClassOf']).PHP_EOL;
	}
	return $buf;
}

function SpecialCase($sid,$rid,&$uid,$header_uri,$has_value_uri,$k,$h,$v)
{
	global $pharmgkb_vocab,$pharmgkb_data,$owl,$rdf;
	$buf = '';
	
	$addtriples = true;
	$uid++;
	$oid = $pharmgkb_data.$uid; // set the default oid
	$parent_type_uri = TypeDeclaration($pharmgkb_vocab, $h, $owl."Class");
	
	$type_uri = $header_uri;
	switch ($k){
		// gender
		case 3:  // gender
			if($v == 'male' || $v == 'female') {
				$type_uri = TypeDeclaration($pharmgkb_vocab, $v, $owl."Class");
			} else $addtriples=false; // ignore other values
			break;
			
		case 4: // race reported
		case 5: // race OMB
		case 6: // ethnicity reported
		case 7: // ethnicity OMB
			if(stristr($v,"unknown")) {$addtriples=false;break;} // don't bother here
			$a = explode("not",$v);
			$not_label = '';
			if(isset($a[1])) { 
				$not_label = "not ";
				$phrase = substr($v,4);
			} else $phrase = $v;
			$b = explode("or",trim($phrase));
			foreach($b AS $i => $j) {
				$oid = $pharmgkb_data.$uid;
				$buf .= Triple($sid, $rid, $oid).PHP_EOL;
				$type_uri = TypeDeclaration($pharmgkb_vocab, $not_label.trim($j), $owl."Class",$parent_type_uri);
				$buf .= Triple($oid, $rdf."type",$type_uri).PHP_EOL;
				$uid++;
			}
			$uid--;
			$addtriples = false;
			
			break;
		
		case 8: // age
			$type_uri = TypeDeclaration($pharmgkb_vocab, "age between ".$v." years", $owl."Class", $parent_type_uri);
			break;
			
		case 11: // indication for warfarin treatment
			// DVT = 1, PE = 2, Afib/flutter = 3, Heart Valve = 4, Cardiomyopathy/LV Dilation = 5, Stroke = 6, Post-Orthopedic = 7, Other = 8 or NA; multiple indications are separated by semi-colons
			$indications = array(1=>'DVT',2=>'PE',3=>'Afib/flutter',4=>'Heart Valve',5=>'Cardiomyopathy/LV Dilation',6=>'Stroke',7=>'Post-Orthopedic',8=>'Other indication');
			
			$a = explode(";",trim($v));
			foreach($a as $i => $j) {
				$j = trim($j);
				$uid++;
				$oid = $pharmgkb_data.$uid;
				$buf .= Triple($sid, $rid, $oid).PHP_EOL;
				if(strstr($j," or ") || strstr($j,",")) $label = $j;
				else $label = $indications[$j];
				$type_uri = TypeDeclaration($pharmgkb_vocab, $label, $owl."Class", $parent_type_uri);
				$buf .= Triple($oid, $rdf."type",$type_uri).PHP_EOL;
			}
			$addtriples = false;
			break;
		case 12:
		case 16:
			// handle the multiple valued fields
			
			$b = explode(";",trim($v));
			foreach($b AS $i => $d) {
				$c = explode(",",trim($d));
				//var_dump($c);exit;
				foreach($c AS $j => $e) {
					if($e != '') {
						$uid ++;
						$oid = $pharmgkb_data.$uid;
						
						$oid_type = TypeDeclaration($pharmgkb_vocab, trim($e), $owl."Class",$parent_type_uri);
						$buf .= Triple($sid, $rid, $oid).PHP_EOL;
						$buf .= Triple($oid, $rdf."type", $oid_type).PHP_EOL;
					}
				}
			}
			$addtriples = false;
			break;
			
		default:
			
			if($v == "0") {
				$type_uri = TypeDeclaration($pharmgkb_vocab, "No ".$h, $owl."Class");
				
			} else {
				$buf .= LiteralTriple($oid, $has_value_uri, $v).PHP_EOL;
			}
			break;
	}
	if($addtriples) {
		$buf .= Triple($sid, $rid, $oid).PHP_EOL;
		$buf .= Triple($oid, $rdf."type",$type_uri).PHP_EOL;
		//echo $buf;exit;
	}
	
	return $buf;
}