THIS REPO HAS BEEN MOVED WITH UPGRADES FOR 5.5 TO https://github.com/Thorazine/location
Thanks :)

# Laravel Location
Get a complete location set from coordinates, address, postal code or IP. Through the Location Facade you can 
request the Google and IpInfo API to return the address of a visitor on your website.
This script works out of the box, no need for any keys or registrations.


## What you should keep in mind

This script uses the Google an IpInfo API to request information. Especially with the Ip API there is
margin for error. The Google API is quite accurate and does most of the heavy lifting. However, please
don't use this data as fact but rather as indication.


### PHP requirements:
- PHP Curl


### To do:
- Create Guzzle client support switch


### Why not yet:
Guzzle continously changes it's workings. I haven't found the time yet.


## How to make it work
Run:
```
composer require noprotocol/laravel-location
```

Add to app/config => providers
```php
Noprotocol\LaravelLocation\LocationServiceProvider::class,
```

Add to app/config => aliases
```php
'Location' => Noprotocol\LaravelLocation\Facades\LocationFacade::class,
```

Get the configuration:
```
php artisan vendor:publish --tag=location
```

If you have a Google key add a line to your .env file:
```
GOOGLE_KEY=[key]
```

> Script will work out of the box without a key, but it has limited requests. 
> Please look at Google documentation hell to see how what the rate limiting is.


These (quick examples):
```php
$location = Location::locale('nl')->coordinatesToAddress(['latitude' => 52.385288, 'longitude' => 4.885361])->get();

$location = Location::locale('nl')->addressToCoordinates(['country' => 'Nederland', 'street' => 'Nieuwe Teertuinen', 'street_number' => 25])->get();

$location = Location::locale('nl')->postalcodeToCoordinates(['postal_code' => '1013 LV', 'street_number' => '25'])->coordinatesToAddress()->get();

$location = Location::locale('nl')->ipToCoordinates()->coordinatesToAddress()->get(); // if IP resolves properly, which it mostly doesn't
```


Will all result in:
```php
$location['latitude'] = 52.385288,
$location['longitude'] = 4.885361;
$location['country'] = 'Nederland';
$location['region'] = 'Noord-Holland';
$location['city'] = 'Amsterdam';
$location['street'] = 'Nieuwe Teertuinen';
$location['street_number'] = '25';
$location['postal_code'] = '1013 LV';
```

To return it as object set the ```get()``` function to true: ```get(true)```


## Extended example:
```php
try {
	$location = Location::coordinatesToAddress(['latitude' => 52.385288, 'longitude' => 4.885361])->get(true);

	if($error = Location::error()) {
		dd($error);
	}
}
catch(Exception $e) {
	dd($e->getMessage());
}
```

The result is the default template and starts out as empty and gets filled throught the call. So if no data is available 
the result for that entry will be "". After every call the script resets to it's initial settings.


## Chainable functions and their variables

| Functions 					| Values		| Validation	| Type
|-------------------------------|---------------|---------------|---------
| coordinatesToAddress()		| latitude		| required		| float
|								| longitude		| required		| float
| addressToCoordinates()		| country		| recommended	| string
|								| region		| 				| string
|								| city			| recommended	| string
|								| street 		| required		| string
|								| street_number	| required		| string
| postalcodeToCoordinates()		| postal_code	| required		| string
|								| street_number	| recommended	| string
| get()							| true/false	| boolean		| boolean


## Other functions

| Functions 					| Values		| Result
|-------------------------------|---------------|----------------------------------------------
| error()						| none			| Returns any error if there is one
| response()					| none			| Returns the raw response from the Google API



## Debug
With the try catch you can alreay see what you need. But besides this there is also a cached result of the raw response from the 
google API. Please note that this is not the case with the ip request.

```php
$location = Location::coordinatesToAddress(['latitude' => 52.385288, 'longitude' => 4.885361])->get();
Location::response(); // results in raw api response
```
