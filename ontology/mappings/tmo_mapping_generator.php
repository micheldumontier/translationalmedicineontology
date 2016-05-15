<?php
$tmo_file = "/code/tmo/ontology/tmo.owl";
$tmo_mapping_dataset_file = "tmo_mapping_dataset.n3";
$tmo_mapping_ontology_file = "tmo_mapping_ontology.n3";

$map = array( 
"medicare" =>  array("label" => "Medicare", "ns" => "http://www4.wiwiss.fu-berlin.de/medicare/resource/medicare/", "dataset"=>true),
"diseasome" => array("label" => "Diseasome", "ns" => "http://www4.wiwiss.fu-berlin.de/diseasome/resource/diseasome/", "dataset"=>true),
"sider" =>     array("label" => "SIDER", "ns" => "http://www4.wiwiss.fu-berlin.de/sider/resource/sider/", "dataset"=>true),
"dailymed" =>  array("label" => "DailyMed", "ns" => "http://www4.wiwiss.fu-berlin.de/dailymed/resource/dailymed/", "dataset"=>true),
"pharmgkb" =>  array("label" => "PharmGKB", "ns" => "http://bio2rdf.org/pharmgkb:", "dataset"=>true),
"drugbank" =>  array("label" => "DrugBank", "ns" => "http://www4.wiwiss.fu-berlin.de/drugbank/resource/drugbank/", "dataset"=>true),
"linkedct" =>  array("label" => "Clinicaltrials.gov", "ns" => "http://data.linkedct.org/resource/linkedct/", "dataset"=>true),
"pchr" =>      array("label" => "Personally Controlled Health Record", "ns" => "tag:eric@w3.org:2009/tmo/translator#", "dataset"=>true),

"GO" => array("label" => "Gene Ontology", "ns" => "http://purl.obolibrary.org/obo/GO_"),
"FHHO" => array("label" => "Family Health History Ontology", "ns" => ""),
"ODGI" => array("label" => "", "ns" => ""),
"IEV" => array("label" => "Event (INOH pathway ontology)", "ns" => ""),
"cto_asyont" =>  array("label" => "Clinical Trial Ontology", "ns" => ""),
"PATO" =>  array("label" => "Phenotype and Quality Ontology", "ns" => ""),
"MI" =>  array("label" => "Molecular Interaction", "ns" => ""),
"BRO" =>  array("label" => "Biomedical Resource Ontology", "ns" => ""),
"CPR" =>  array("label" => "", "ns" => ""),
"foaf" =>  array("label" => "Friend of a Friend", "ns" => ""),
"PRO" =>  array("label" => "Protein Ontology", "ns" => ""),
"FMA" =>  array("label" => "Foundation Model of Anatomy", "ns" => ""),
"DOID" =>  array("label" => "Human Disease Ontology", "ns" => ""),
"dcterms" =>  array("label" => "Dublic Core terms", "ns" => ""),
"NDFRT" =>  array("label" => "National Drug File", "ns" => ""),
"HL7V3.0" =>  array("label" => "Health Level 7: version 3.0", "ns" => ""),
"OCRe_clinical" =>  array("label" => "Ontology for Clinical Research : Clinic", "ns" => ""),
"OCRe_research" =>  array("label" => "Ontology for Clinical Research : Research", "ns" => ""),
"CHEBI" =>  array("label" => "Chemical Entities of Biological Interest", "ns" => "http://purl.obolibrary.org/obo/CHEBI_"),
"EFO" =>  array("label" => "Experimental Factor Ontology", "ns" => ""),
"SO" =>  array("label" => "Sequence Ontology", "ns" => "http://purl.obolibrary.org/obo/SO_"),
"GRO" =>  array("label" => "Gene Regulation Ontology", "ns" => ""),
"LNC" =>  array("label" => "Logical Observation Identifier Names and Codes", "ns" => ""),
"BIRNLex" =>  array("label" => "BIRNLex", "ns" => ""),
"ACGT" =>  array("label" => "ACGT Master Ontology", "ns" => ""),
"OBI" =>  array("label" => "Ontology for Biomedical Investigation", "ns" => "http://purl.obolibrary.org/obo/OBI_"),
"Galen" =>  array("label" => "Open Galen Ontology", "ns" => ""),
"SNOMEDCT" => array("label" => "SNOMED Clinical Terms", "ns" => ""),
"MSH" =>  array("label" => "Medical Subject Headings", "ns" => ""),
"IAO" =>  array("label" => "Information Artifact Ontology", "ns" => "http://purl.obolibrary.org/obo/IAO_"),
"UMLS" =>  array("label" => "Unified Medical Language System", "ns" => ""),
"NCIt" =>  array("label" => "NCI Thesaurus", "ns" => "")
);


// arc2
$arcdir = "/code/arc";
include_once($arcdir.'/ARC2.php');

$parser = ARC2::getRDFParser();
$parser->parse($tmo_file);
$triples = $parser->getTriples();
$index = $parser->getSimpleIndex();

$label_uri = 'http://www.w3.org/2000/01/rdf-schema#label';
$base = 'http://www.w3.org/2001/sw/hcls/ns/transmed/';
$p = 'http://www.geneontology.org/formats/oboInOwl#hasDbXref';

$tmo_mapping_dataset = $tmo_mapping_ontology = "@prefix owl: <http://www.w3.org/2002/07/owl#> .".PHP_EOL;

foreach($index AS $id => $obj) {
 // generate associative array of id -> label
 if(isset($obj[$label_uri])) $label[$id] = $obj[$label_uri][0];
 
 // iterate over the dbxref annotation
 if(isset($obj[$p])) {
   foreach($obj[$p] AS $xref) {
      list($ns,$tid) = explode(":",trim($xref));
	  // how many mappings for the tmo concept
	  $l[$id] = '';
	  // $l_c[$id][] = $ns.":".$tid;
	  
	  // how many entries in each target ns
	  // if(!isset($l_ns [$ns])) $l_ns[$ns] = 1;else $l_ns[$ns] ++;
	  
	  // unique set of target classes
	  // if(!isset($l_t[$ns.":".$tid])) $l_t[$ns.":".$tid] = '';
	  
	  $buf = "<$id> rdfs:subClassOf <http://bio2rdf.org/".strtolower($ns).":$tid> .".PHP_EOL;
	  $buf = "<http://bio2rdf.org/".strtolower($ns).":$tid> owl:equivalentClass <$id> .".PHP_EOL;
	  if(isset($map[$ns]['ns']) && $map[$ns]['ns'] != '') {
	    $buf .= "<$id> owl:equivalentClass <".$map[$ns]['ns']."$tid> .".PHP_EOL;
		$buf .= "<".$map[$ns]['ns']."$tid> owl:equivalentClass <$id> .".PHP_EOL;
	  }
	  
	  if(isset($map[$ns]['dataset'])) $tmo_mapping_dataset .= $buf;
	  else $tmo_mapping_ontology .= $buf;
   }
 }
}

file_put_contents($tmo_mapping_dataset_file,$tmo_mapping_dataset);
file_put_contents($tmo_mapping_ontology_file,$tmo_mapping_ontology);
?>

