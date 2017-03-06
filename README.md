# Location reverse script



### PHP requirements:

- PHP Curl


### To do:

- Create Guzzle support switch


### Why not yet:
Guzzle continously changes it's working. 


## Make it work

Add to app/config => providers
```
Noprotocol\LaravelLocation\LocationServiceProvider::class,
```

Add to app/config => aliases
```
'Location' => Noprotocol\LaravelLocation\Facades\LocationFacade::class,
```

To get the configuration run:
```
php artisan vendor:publish --tag=location
```

If you have a Google key add a line to your .env file:
```
GOOGLE_KEY=[key]
```
Script will work out of the box without a key, but it has limited requests. 
Please look at Google documentation hell to see how what the limit rating is.


Example:
```
$location = Location::getAddressByLatLong(52.385616, 4.884808)->get();

// result
$location->country = 'Netherlands';
$location->city = 'Amsterdam';
$location->street = 'Nieuwe Teertuinen';
$location->number = '25';
$location->country = 'Netherlands';
$location->doesntexcist = NULL;
```