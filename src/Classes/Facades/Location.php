<?php

namespace Noprotocol\LaravelLocation\Classes\Facades;

use Noprotocol\LaravelLocation\Classes\Curl;
use Exception;
use StdClass;
use Request;
use Log;
use App;

class Location 
{
	/**
	 * If there is an error it'll be in here
	 */
	private $error = null;

	/**
	 * The complete response will be given here (array)
	 */
	private $response = null;


	/**
	 * If there is an error it'll be in here
	 */
	private $locale = null;

	/**
	 * Default values
	 */
	private $locationData = [
		'latitude' => '',
		'longitude' => '',
		'country' => '',
		'region' => '',
		'city' => '',
		'street' => '',
		'street_number' => '',
		'postal_code' => '',
	];

	/**
	 * Url variables
	 */
	private $urlVariables = [];

	/**
	 * The array that holds the data
	 */
	private $returnLocationData = [];



	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->returnLocationData = $this->locationData;
	}

	/**
	 * Set the locale in. ISO (nl, nl_NL, en, en_GB, etc.)
	 */
	public function locale($locale)
	{
		$this->locale = $locale;

		return $this;
	}

	/**
	 * Get the coordinates from a postal code
	 *
	 * @param string
	 * @param string/integer
	 * @return $this 
	 */
	public function postalcodeToCoordinates($postalCode, $number = '')
	{
		$this->reset();

		$this->returnLocationData = array_merge($this->returnLocationData, ['postal_code' => $postalCode, 'street_number' => $number]);

		$this->urlAddPostcode($postalCode);

		$this->updateResponseWithResults($this->gateway());

		return $this;
	}

	/**
	 * Get coordinates from an address
	 *
	 * @param array
	 * @return $this 
	 */
	public function addressToCoordinates(array $address = [])
	{
		$this->reset();

		$this->returnLocationData = array_merge($this->returnLocationData, $address);

		$this->urlAddAddress($address);

		$this->updateResponseWithResults($this->gateway());

		return $this;
	}

	/**
	 * Get the address from coordinates
	 *
	 * @param array
	 * @return $this 
	 */
	public function coordinatesToAddress(array $coordinates = [])
	{
		$this->reset();

		$this->returnLocationData = array_merge($this->returnLocationData, $coordinates);

		$this->urlAddCoordinates($coordinates);

		$this->updateResponseWithResults($this->gateway());

		return $this;
	}

	/**
	 * Get coordinates from an ip
	 *
	 * @param string (ip)
	 * @return $this
	 */
	public function ipToCoordinates($ip = null)
	{
		if(! $ip) {
			$ip = Request::ip();
		}

		$this->url = 'http://ipinfo.io/'.$ip.'/geo';

		$client = new Curl;

		if(in_array($ip, config('location.ip-exceptions'))) {
			$this->returnLocationData = array_merge($this->returnLocationData, config('location.default-template-localhost'));
		}
		else {
			$response = $this->jsonToArray($client->get($this->url));

			list($latitude, $longitude) = explode(',', $response['loc']);

			$this->returnLocationData['latitude'] = $latitude;
			$this->returnLocationData['longitude'] = $longitude;
		}

		return $this;
	}


	/**
	 * Return the results
	 */
	public function get($toObject = false)
	{		
		if($this->error) {
			Log::error('Could not get location. There was an error.', [
	            'error' => $this->error,
	        ]);
		}

		if($toObject) {
			$response = new StdClass;

			foreach($this->returnLocationData as $key => $value) {
				$response->{$key} = $value;
			}
		}
		else {
			$response = $this->returnLocationData;
		}

		$this->locale = null;
		$this->urlVariables = [];
		$this->returnLocationData = $this->locationData;

		return $response;
	}

	/**
	 * Returns the error variable
	 */
	public function error()
	{
		return $this->error;
	}

	/**
	 * Returns the response variable
	 */
	public function response()
	{
		return $this->response;
	}

	/**
	 * Get the data from the gateway
	 * New gateways (like Guzzle) can be added here and can be chosen from the config
	 */
	private function gateway()
	{
		if(config('location.client') == 'curl') {
			$client = new Curl;

			return $client->get($this->buildUrl());
		}

		throw new Exception('HTTP client not supported');
	}

	/**
	 * Add the locale to the request
	 */
	private function addLanguage()
	{
		if($this->locale) {
			$this->urlVariables = array_merge($this->urlVariables, ['language' => $this->locale]);
		}
		elseif(config('location.language')) {
			$this->urlVariables = array_merge($this->urlVariables, ['language' => config('location.language')]);
		}
		else {
			$this->urlVariables = array_merge($this->urlVariables, ['language' => App::getLocale()]);
		}
	}

	/**
	 * Add the request variables for the postal code request
	 */
	private function urlAddPostcode($postalCode)
	{
		$this->urlVariables = ['address' => $postalCode];
	}

	/**
	 * Add the request variables for the address request
	 */
	private function urlAddAddress($address)
	{
		$this->urlVariables = ['address' => implode(' ', array_values($address))];
	}

	/**
	 * Add the request variables for the coordinates request
	 */
	private function urlAddCoordinates($coordinates)
	{
		if($coordinates) {
			$this->urlVariables = ['latlng' => $coordinates['latitude'].','.$coordinates['longitude']];
		}
		elseif($this->returnLocationData['latitude'] && $this->returnLocationData['longitude']) {
			$this->urlVariables = ['latlng' => $this->returnLocationData['latitude'].','.$this->returnLocationData['longitude']];
		}
		else {
			throw new Exception('No coordinates supplied');
		}
	}

	/**
	 * Build the request url
	 */
	private function buildUrl()
	{
		$this->addLanguage();

		$variables = '';

		foreach($this->urlVariables as $variable => $value) {
			if(! $variables) {
				$variables .= '?'.$variable.'='.urlencode($value);
			}
			else {
				$variables .= '&'.$variable.'='.urlencode($value);
			}
		}

		return config('location.google-request-url').$variables;
	}

	/**
	 * fill the response with usefull data as far as we can find
	 */
	private function updateResponseWithResults($json) 
	{
		$response = $this->jsonToArray($json);

		if(isset($response['results'][0])) {

			$this->response = $response['results'][0];

			if(! $this->returnLocationData['country']) {
				$this->returnLocationData['country'] = $this->findInGoogleSet($response, ['country']);
			}

			if(! $this->returnLocationData['region']) {
				$this->returnLocationData['region'] = $this->findInGoogleSet($response, ['administrative_area_level_1']);
			}
			
			if(! $this->returnLocationData['city']) {
				$this->returnLocationData['city'] = $this->findInGoogleSet($response, ['administrative_area_level_2']);
			}
			
			if(! $this->returnLocationData['street']) {
				$this->returnLocationData['street'] = $this->findInGoogleSet($response, ['route']);
			}
			
			if(! $this->returnLocationData['street_number']) {
				$this->returnLocationData['street_number'] = $this->findInGoogleSet($response, ['street_number']);
			}
			
			if(! $this->returnLocationData['postal_code']) {
				$this->returnLocationData['postal_code'] = $this->findInGoogleSet($response, ['postal_code']);
			}
			
			if(! $this->returnLocationData['latitude']) {
				$this->returnLocationData['latitude'] = $response['results'][0]['geometry']['location']['lat'];
			}
			
			if(! $this->returnLocationData['longitude']) {
				$this->returnLocationData['longitude'] = $response['results'][0]['geometry']['location']['lng'];
			}
		}
		else {
			$this->error = 'No results';
		}
	}

	/**
	 * Find a value in a response from google
	 *
	 * @param array (googles response)
	 * @param array (attributes to find)
	 * @return string
	 */
	private function findInGoogleSet($response, array $find = [])
	{
		try {
			foreach($response['results'][0]['address_components'] as $data) {
				foreach($data['types'] as $key) {
					if(in_array($key, $find)) {
						return $data['long_name'];
					}
				}
			}
			return '';
		}
		catch(Exception $e) {
			$this->error = $e;
			return '';
		}
		
	}


	/**
	 * Convert json string to an array if the syntax is right
	 *
	 * @param string (json)
	 * @return array|null
	 */
	private function jsonToArray($json)
	{
		try {
			$data = json_decode($json, true);
			if(is_array($data)) {
				return $data;
			}
			else {
				$this->error = 'The given data string was not json';
				return [];
			}
		}
		catch(Exception $e) {
			$this->error = $e;
		}
	}

	private function reset()
	{
		$this->error = null;
		$this->response = null;
	}










// 	/**
// 	 * Switch to detect if its a chain or a direct call
// 	 *
// 	 * @param float (optional for chaining)
// 	 * @param float (optional for chaining)
// 	 * @return $this
// 	 */
// 	public function coordinatesToAddress($latitude = false, $longitude = false)
// 	{
// 		if(! $latitude || ! $longitude) {
// 			$latitude = $this->array['latitude'];
// 			$longitude = $this->array['longitude'];
// 		}
// 		else {
// 			$this->array['latitude'] = $latitude;
// 			$this->array['longitude'] = $longitude;
// 		}

// 		return $this->runCoordinatesToAddress($latitude, $longitude);
// 	}


// 	/**
// 	 * Get an address from coordinates
// 	 *
// 	 * @param float (optional for chaining)
// 	 * @param float (optional for chaining)
// 	 * @return $this
// 	 */
// 	private function runCoordinatesToAddress($latitude, $longitude)
// 	{
// 		$this->url = $this->addKey('https://maps.google.com/maps/api/geocode/json?latlng='.$latitude.','.$longitude);

// 		$response = $this->jsonToArray($this->gateway());

// 		if(array_key_exists('status', $response) && $response['status'] != 'ZERO_RESULTS') {
// 			$this->array['city'] = $this->findInGoogleSet($response, ['locality', 'administrative_area_level_2']);
// 			$this->array['country'] = $this->findInGoogleSet($response, ['country']);
// 			$this->array['region'] = $this->findInGoogleSet($response, ['administrative_area_level_1']);
// 			$this->array['street'] = $this->findInGoogleSet($response, ['route']);
// 		}
// 		else {
// 			$this->tried++;

// 			$this->error = 'Could not get data from service.';

// 			if($this->tryAgain()) {
// 				sleep($this->tryTimeout);

// 				$this->coordinatesToAddress($latitude, $longitude);
// 			}
// 		}

// 		return $this;
// 	}


// 	/**
// 	 * Get coordinates from address data
// 	 *
// 	 * @param array (country, city, region)
// 	 * @return $this
// 	 */
// 	public function addressToCoordinates(array $attributes = [])
// 	{

// 		// build the url
// 		$this->url = 'https://maps.google.com/maps/api/geocode/json?address=';

// 		if(array_key_exists('country', $attributes) && array_key_exists('city', $attributes)) {
// 			$this->url .= urlencode($attributes['city'].','.$attributes['country']);
// 		}
// 		else {
// 			$this->url .= urlencode($attributes['city']);
// 		}

// 		if(array_key_exists('region', $attributes)) {
// 			$this->url .= '&region='.urlencode($attributes['region']);
// 		}

// 		$this->array = array_merge($this->array, $attributes);

// 		$this->addKey($this->url);

// 		$response = $this->jsonToArray($this->gateway());

// 		if(array_key_exists('status', $response) && $response['status'] != 'ZERO_RESULTS') {
// 			$this->array['latitude'] = @$response['results'][0]['geometry']['location']['lat'];
// 			$this->array['longitude'] = @$response['results'][0]['geometry']['location']['lng'];
// 		}
// 		else {
// 			$this->tried++;

// 			$this->error = 'Could not get data from service';

// 			if($this->tryAgain()) {
// 				sleep($this->tryTimeout);

// 				$this->addressToCoordinates($attributes);
// 			}
// 		}

// 		return $this;
// 	}


// 	public function addressToCoordinatesHeroku(array $attributes = []) 
// 	{
//     	// build the url
// 		$this->url = 'http://np-maps-api.herokuapp.com/points/location?';

//     	if(array_key_exists('country', $attributes) && array_key_exists('city', $attributes)) {
// 			$this->url .= 'center='.urlencode($attributes['city'].','.$attributes['country']);
// 		}
// 		elseif(array_key_exists('center', $attributes)) {
// 			$this->url .= 'center='.urlencode($attributes['center']);
// 		}
// 		else {
// 			$this->url .= urlencode($attributes['city']);
// 		}

// 		if(array_key_exists('region', $attributes)) {
// 			$this->url .= '&region='.urlencode($attributes['region']);
// 		}
// // dd($this->url);
// 		$this->array = array_merge($this->array, $attributes);

//         // $url = 'http://np-maps-api.herokuapp.com/points/location?center='.urlencode($city.','.$region);

//         $response = $this->jsonToArray($this->gateway());
// // dd($response);
// 		if(array_key_exists('latitude', $response) && @$response['message'] != 'ZERO_RESULTS') {
// 			$this->array['latitude'] = @$response['latitude'];
// 			$this->array['longitude'] = @$response['longitude'];
// 		}
// 		else {
// 			$this->tried++;

// 			$this->error = 'Could not get data from service';

// 			if($this->tryAgain()) {
// 				sleep($this->tryTimeout);

// 				$this->addressToCoordinates($attributes);
// 			}
// 		}

// 		return $this;
//     }


// 	/**
// 	 * Get an address from coordinates
// 	 *
// 	 * @param float (optional for chaining)
// 	 * @param float (optional for chaining)
// 	 * @return $this
// 	 */
// 	private function runCoordinatesToAddressHeroku($latitude = false, $longitude = false)
// 	{
// 		if($this->array['latitude'] && $this->array['longitude']) {
// 			$latitude = $this->array['latitude'];
// 			$longitude = $this->array['longitude'];
// 		}
// 		else {
// 			$this->url = $this->addKey('http://maps.google.com/maps/api/geocode/json?latlng='.$latitude.','.$longitude);
// 		}
 
// 		$response = $this->jsonToArray($this->gateway());

// 		if(array_key_exists('status', $response) && $response['status'] != 'ZERO_RESULTS') {
// 			$this->array['city'] = $this->findInGoogleSet($response, ['locality', 'administrative_area_level_2']);
// 			$this->array['country'] = $this->findInGoogleSet($response, ['country']);
// 			$this->array['region'] = $this->findInGoogleSet($response, ['administrative_area_level_1']);
// 		}
// 		else {
// 			$this->tried++;

// 			$this->error = 'Could not get data from service.';

// 			if($this->tryAgain()) {
// 				sleep($this->tryTimeout);

// 				$this->coordinatesToAddress($latitude, $longitude);
// 			}
// 		}

// 		return $this;
// 	}


// 	/**
// 	 * Get coordinates from an ip
// 	 *
// 	 * @param string (ip)
// 	 * @return $this
// 	 */
// 	public function ipToLocation($ip = false)
// 	{
// 		if(! $ip) {
// 			$ip = Request::ip();
// 		}
// 		$this->url = 'http://ipinfo.io/'.$ip.'/geo';

// 		$response = $this->jsonToArray($this->gateway());

// 		list($latitude, $longitude) = explode(',', $response['loc']);

// 		$this->array['latitude'] = $latitude;
// 		$this->array['longitude'] = $longitude;

// 		return $this;
// 	}


// 	/**
// 	 * Get the data
// 	 *
// 	 * @param string (url)
// 	 * @return mixed
// 	 */
// 	private function gateway()
// 	{
// 		try {
// 			// store request for a week
// 			// $data = Cache::tags(['location', 'tools', 'cms'])->remember(sha1($this->url), 10800, function() {
// 	        	$data = Curl::get($this->url);
// 	        // });

// 	        return $data;
// 	    }
// 	    catch(Exception $e) {
// 	    	Log::error('Could not get location. There was an error.', [
// 	            'data' => $this->url,
// 	            'error' => $e->getMessage(),
// 	        ]);
// 	    	$this->error = $e;
// 	    }
// 	}


// 	/**
// 	 * Add the google key if there is one
// 	 *
// 	 * @param string (url)
// 	 * @return string (url)
// 	 */
// 	private function addKey($url)
// 	{
// 		if($this->googleKey) {
// 			return $url.'&key='.$this->googleKey;
// 		}
// 	}


// 	/**
// 	 * Check to see if we need to request again
// 	 *
// 	 * @return boolean
// 	 */
// 	private function tryAgain()
// 	{
// 		if($this->tried < $this->tries) {
// 			return true;
// 		}
// 	}


// 	/**
// 	 * Convert json string to an array if the syntax is right
// 	 *
// 	 * @param string (json)
// 	 * @return array|null
// 	 */
// 	private function jsonToArray($json)
// 	{
// 		try {
// 			$data = json_decode($json, true);
// 			if(is_array($data)) {
// 				return $data;
// 			}
// 			else {
// 				$this->error = 'The given data string was not json';
// 				return [];
// 			}
// 		}
// 		catch(Exception $e) {
// 			$this->error = $e;
// 		}
// 	}


// 	/**
// 	 * Find a value in a response from google
// 	 *
// 	 * @param array (googles response)
// 	 * @param array (attributes to find)
// 	 * @return string
// 	 */
// 	private function findInGoogleSet($response, array $find = [])
// 	{
// 		try {
// 			foreach($response['results'][0]['address_components'] as $data) {
// 				foreach($data['types'] as $key) {
// 					if(in_array($key, $find)) {
// 						return $data['long_name'];
// 					}
// 				}
// 			}
// 			return '';
// 		}
// 		catch(Exception $e) {
// 			$this->error = $e;
// 			return '';
// 		}
		
// 	}


// 	/**
// 	 * Return the data and reset the class
// 	 *
// 	 * @param boolean (optional)
// 	 * @return array|object
// 	 */
// 	public function get($object = false)
// 	{
// 		if($object) {
// 			return json_decode(json_encode($this->array));
// 		}

// 		if($this->error) {
// 			Log::error('Could not get location. There was an error.', [
// 	            'error' => $this->error,
// 	        ]);
// 		}

// 		$return = $this->array;
// 		$this->array = $this->default;
// 		$this->tried = 0;
// 		$this->url = null;

// 		return $return;
// 	}



}