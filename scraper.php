<?php

require 'scraperwiki.php';
	$lettersArray = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','XYZ');
	/*
	foreach ($lettersArray as $value) {
		print ('Verarbeite Buchstaben ' . $value . "\n");
		ripBhidByLetter($value);
	}
	*/
	ripBhidByLetter("C");

function ripBhidByLetter($letter){
	$pathToOverviewByLetter = 'http://service-bw.de/zfinder-bw-web/authorities.do?action=search&letter=';
	$bhidpattern = '/bhid=([0-9]*)&/m';
	
	$matchesBhid;
	$output = scraperwiki::scrape("$pathToOverviewByLetter" . $letter);
        preg_match_all($bhidpattern, $output, $matchesBhid);
        
        foreach ($matchesBhid[1] as $value){
        	ripBeidByBhid ($value);
        }
}

function ripBeidByBhid($bhid) {
	$pathToResult 		= 'http://service-bw.de/zfinder-bw-web/authorities.do?bhid=';
	$beidpattern = '/beid=([0-9]*)&/m';
	$matchesBeid;
	$output = scraperwiki::scrape("$pathToResult" . $bhid);
        preg_match_all($beidpattern, $output, $matchesBeid);
        
        foreach ($matchesBeid[1] as $value){
        	ripByBeid ($value);
        }
	
}



function ripByBeid ($beid){
	$pathToEntry		= 'http://service-bw.de/zfinder-bw-web/authorities.do?beid=';
	
	$namepattern = '/<h1>(.*)<\/h1>/smiU';
	$faxpattern = '/<td>Fax:<\/td><td>(.*)<\/td>/smiU';
	$telefonpattern = '/<td>Telefon:<\/td><td>(.*)<\/td>/smiU';
	$emailpattern = '/<td>E-Mail:<\/td><td><a href="(.*)"/imUs';
	$emailpattern2 = '/href="mailto:(.*)">.*<\/a>/imUs';
	$adresspattern1 = '/<h3>Hausanschrift:<\/h3><div class="column in_content"><p>(.*)<\/p><\/div>/smiU';
	$adresspattern2 = '/<h3>Postanschrift:<\/h3><p>(.*)<\/p><table>/smiU';
	$wwwpattern = '/<td>Homepage:<\/td><td><a href="(.*)"/smiU';

	$output = scraperwiki::scrape("$pathToEntry" . $beid);
	$output = mb_convert_encoding($output, 'UTF-8', mb_detect_encoding($output, 'UTF-8, ISO-8859-1', true));
	    
        preg_match($namepattern, $output, $temp);
        $name = (isset($temp[1])) ? str_replace(';', ',',trim(preg_replace('/\s+/', ' ', $temp[1]))) : '';
        
        preg_match($faxpattern, $output, $temp);
        $fax = (isset($temp[1])) ? trim(preg_replace('/\s+/', ' ', $temp[1])) : '';
        
        preg_match($telefonpattern, $output, $temp);
        $telefon = (isset($temp[1])) ? trim(preg_replace('/\s+/', ' ', $temp[1])) : '';
        
        preg_match($emailpattern, $output, $temp);
	$tempData =  html_entity_decode($temp[1]);
        $email =  (isset($temp[1])) ? trim(preg_replace('/\s+/', ' ', $tempData)) : '';
	if (strlen(trim($email)) == 0) {
		preg_match($emailpattern2, $output, $temp);
		$tempData =  html_entity_decode($temp[1]);
       		$email =  (isset($temp[1])) ? trim(preg_replace('/\s+/', ' ', $tempData)) : '';
	}
        
        preg_match($adresspattern1, $output, $temp);
        $adress1 = (isset($temp[1])) ? str_replace(';',',',trim(preg_replace('/\s+/', ' ', $temp[1]))) : '';
        $adress1 = str_ireplace('<br />', ',', $adress1);
	$adress1 = strip_tags($adress1);

        preg_match($adresspattern2, $output, $temp);
        $adress2 = (isset($temp[1])) ? str_replace(';',',',trim(preg_replace('/\s+/', ' ', $temp[1]))) : '';
        $adress2 = str_ireplace('<br />', ',', $adress2);
	$adress2 = strip_tags($adress2);
	
	$adress = (isset($temp[1])) ? $adress2 : $adress1;


        preg_match($wwwpattern, $output, $temp);
        $url = (isset($temp[1])) ? trim(preg_replace('/\s+/', ' ', $temp[1])) : '';

	$phonestring = (strlen(trim($telefon)) != 0) ? 'Telefon: ' . trim($telefon) : '';
	$faxstring = (strlen(trim($fax)) != 0) ? 'Fax: ' . trim($fax) : '';
	$contactconnector = (strlen($phonestring) > 0 && strlen($faxstring) > 0) ? ', ': '';
	$contact = $phonestring . $contactconnector . $faxstring;
	
	scraperwiki::save_sqlite(array('data'), array('name' => $name,'email' => $email, 'address' => $adress, 'contact' => $contact, 'jurisdiction__slug' => 'baden-wuerttemberg', 'other_names' => '', 'description' => '', 'topic__slug' => '', 'parent__name' => '', 'classification' => '', 'url' => $url, 'website_dump' => '', 'request_note' => $beid));
      	print $name . "\n";
}
