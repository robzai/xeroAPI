<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Xeroapi\Autoload;
use League\OAuth2\Client\provider\GenericProvider;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Api\IdentityApi;
use GuzzleHttp\Client;
use App\Storage;
use XeroAPI\XeroPHP\Api\AccountingApi;
use Illuminate\Support\Facades\Storage as LaravelStorage;


class XeroController extends Controller
{

	private $provider;
	// Storage Class uses sessions for storing access token (demo only)
	private $storage;

	public function __construct() {
        $this->provider = new GenericProvider([
		    'clientId'                => config('xero.clientId'),   
		    'clientSecret'            => config('xero.clientSecret'),
		    'redirectUri'             => config('xero.redirectUri'),
		    'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
		    'urlAccessToken'          => 'https://identity.xero.com/connect/token',
		    'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
		]);

		$this->storage = new Storage();
    }

    public function login() {

		// If we don't have an authorization code then get one
		if (!isset($_GET['code'])) {
		    $options = [
		        'scope' => ['openid email profile offline_access accounting.contacts.read accounting.settings']
		    ];

		    // Fetch the authorization URL from the provider; this returns the urlAuthorize option and generates and applies any necessary parameters (e.g. state).
		    $authorizationUrl = $this->provider->getAuthorizationUrl($options);

		    // Get the state generated for you and store it to the session.
		    $_SESSION['oauth2state'] = $this->provider->getState();

		    // Redirect the user to the authorization URL.
		    header('Location: ' . $authorizationUrl);
		    exit();

		  // Check given state against previously stored one to mitigate CSRF attack
		} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
		    unset($_SESSION['oauth2state']);
		    exit('Invalid state');
		} else {
		    //no action
		}
    }

    public function callback() {
		// If we don't have an authorization code then get one
  		if (!isset($_GET['code'])) {
      		header("Location: index.php?error=true");
      		exit();
  		// Check given state against previously stored one to mitigate CSRF attack
  		} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
		    echo "Invalid State";
		    unset($_SESSION['oauth2state']);
		    exit('Invalid state');
		} else {
		    try {
		        // Try to get an access token using the authorization code grant.
		        $accessToken = $this->provider->getAccessToken('authorization_code', [
		            'code' => $_GET['code']
		        ]);
		           
		        $config = Configuration::getDefaultConfiguration()->setAccessToken( (string)$accessToken->getToken() );
		          
		        $config->setHost("https://api.xero.com"); 
		        $identityInstance = new IdentityApi(new Client(),$config);
		       
		        $result = $identityInstance->getConnections();

		        // Save my token, expiration and tenant_id
		        $this->storage->setToken(
		            $accessToken->getToken(),
		            $accessToken->getExpires(),
		            $result[0]->getTenantId(),  
		            $accessToken->getRefreshToken()
		        );
		   
		        header('Location: ' . './fetchdata');
		        exit();
		     
		    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
		        echo "Callback failed";
		        exit();
			}
		}
    }

    public function fetchdata() {
    	$xeroTenantId = (string)$this->storage->getSession()['tenant_id'];

	    if ($this->storage->getHasExpired()) {
		    $newAccessToken = $this->provider->getAccessToken('refresh_token', [
		      'refresh_token' => $this->storage->getRefreshToken()
		    ]);
	    
		    // Save my token, expiration and refresh token
		    $this->storage->setToken(
		      $newAccessToken->getToken(),
		      $newAccessToken->getExpires(), 
		      $xeroTenantId,
		      $newAccessToken->getRefreshToken()
		    );
	    }

	    $config = Configuration::getDefaultConfiguration()->setAccessToken( (string)$this->storage->getSession()['token'] );
		$config->setHost("https://api.xero.com/api.xro/2.0");        

		$apiInstance = new AccountingApi(new Client(), $config);

		// Get data from api
		$apiResponseAccounts = $apiInstance->getAccounts($xeroTenantId);
		$apiResponseContacts = $apiInstance->getContacts($xeroTenantId);


		//By default, the local driver is set to the storage/app directory.
		LaravelStorage::disk('local')->put('accounts.txt', $apiResponseAccounts);
		LaravelStorage::disk('local')->put('contacts.txt', $apiResponseContacts);

		echo("Files have been save to ROOT/DIRECTORY/OF/THE/PROJECT/storage/app, ");

    }

}
