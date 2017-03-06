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