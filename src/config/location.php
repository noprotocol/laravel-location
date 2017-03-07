<?php

return [

	/*
	 * Set the Google API key
	 *
	 */
	'google-key' => env('GOOGLE_KEY', ''),


	/*
	 * Set the default language
	 * NULL will set the default app locale
	 */
	'language' => NULL,


	/*
	 * Set the default HTTP client
	 *
	 * options: curl (maybe guzzle will be added)
	 */
	'client' => 'curl',


	/*
	 * Set the url to request
	 *
	 */
	'google-request-url' => 'https://maps.google.com/maps/api/geocode/json',


	/*
	 * Provide a default user agent. Make it yours if you need.
	 *
	 */
	'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.115 Safari/537.36',


	/*
	 * Define ip's that need to recieve the default template on IP request
	 *
	 */
	'ip-exceptions' => [
		'::1', 
		'127.0.0.1'
	],


	/*
	 * Define a default template for a localhost ip request
	 *
	 */
	'default-template-localhost' => [
		'latitude' => 52.385288,
		'longitude' => 4.885361,
		'country' => '',
		'region' => '',
		'city' => '',
		'street' => '',
		'street_number' => '',
		'postal_code' => '',
	],

];