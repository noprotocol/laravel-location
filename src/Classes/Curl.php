<?php

namespace Noprotocol\LaravelLocation\Classes;

use Exception;

class Curl {


	// get curl request
	public function get($url) 
	{
		try {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_USERAGENT, config('location.user-agent'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);

			return $output;
		}
		catch(Exception $e) {
			throw new Exception($e->getMessage());
		}
	}


}