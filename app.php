<?php

// get starred URL
$id          = file_get_contents('userid.txt');
$user_name   = explode("\n",$id,2);
$starred_url = 'https://api.github.com/users/' . $user_name[0] . '/starred';

$query = trim($argv[1]);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $starred_url);
curl_setopt($curl, CURLOPT_ENCODING, "");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, true);
$resp = curl_exec($curl);

$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
$header = substr($resp, 0, $header_size);
$resp = substr($resp, $header_size);

curl_close($curl);

// determine rate limit reset
$api_remain_limit = 60;
if (preg_match('/X-RateLimit-Remaining: ([0-9]+)/', $header, $m)) {
	$api_remain_limit = $m[1];
}

$data = json_decode($resp, true);
$xml = "<?xml version=\"1.0\"?>\n<items>\n";


//
// If API liimit is reached, print explanation.
//
if (!$api_remain_limit) {

	preg_match('/X-RateLimit-Reset: ([0-9]+)/', $header, $m1);
	preg_match('/X-RateLimit-Limit: ([0-9]+)/', $header, $m2);

	$reset_in = (int) (($m1[1] - time()) / 60);

    $xml .= "<item arg=\"http://developer.github.com/v3/#rate-limiting\">\n";
	$xml .= "<title>API limit will reset in " . $reset_in . " minutes.</title>\n";
	$xml .= "<subtitle>GitHub restricts the amount of request to " . $m2[1] . " calls per hour.</subtitle>\n";
	$xml .= "<icon>icon.png</icon>\n";
	$xml .= "</item>\n";
}

//
// Search through the results.
//
foreach ($data as $star){
	$url      = $star['html_url'];
	$title    = $star['name'];
	$subtitle = $star['description'];

	$search_string = $star["full_name"] . ' ' . $star['description'];
	$query_matched = stripos($search_string, $query);

	if (!($query_matched === false) ) {
		$icon_url = $star['owner']['avatar_url'];
		$icon     = 'icons/' . $star['owner']['gravatar_id'] . '.png';

		if (!is_file($icon)) {
			file_put_contents($icon, file_get_contents($icon_url));
		}

	    $xml .= "<item arg=\"$url\">\n";
		$xml .= "<title>$title</title>\n";
		$xml .= "<subtitle>$subtitle</subtitle>\n";
		$xml .= "<icon>$icon</icon>\n";
		$xml .= "</item>\n";
	}
}

$xml .="</items>";
echo $xml;