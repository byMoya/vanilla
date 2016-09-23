<?php
/**
 * @author Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package Core
 * @since 2.0
 */


/**
 * Class OAuth2PluginBase
 *
 * Base class to be extended by any plugin that wants to use Oauth2 protocol for SSO.
 * Will eventually be moved to a library that will be included by composer.
 */
class Gdn_OAuth2 extends Gdn_Pluggable {

    /** @var string token provider by authenticator  */
    protected $accessToken;

    /** @var string key for GDN_UserAuthenticationProvider table  */
    protected $providerKey = null;

    /** @var  string passing scope to authenticator */
    protected $scope;

    /** @var string content type for API calls */
    protected $defaultContentType = 'application/x-www-form-urlencoded';

    /** @var array stored information to connect with provider (secret, etc.) */
    protected $provider = [];

    /** @var array optional additional get parameters to be passed in the authorize_uri */
    protected $authorizeUriParams = [];

    /** @var array optional additional post parameters to be passed in the accessToken request */
    protected $requestAccessTokenParams = [];

    /** @var array optional additional get params to be passed in the request for profile */
    protected $profileRequestParams = [];

    /** @var  @var string optional set the settings view */
    protected $settingsView;

    /**
     * Set up OAuth2 access properties.
     *
     * @param string $providerKey Fixed key set in child class.
     * @param bool|string $accessToken Provided by the authentication provider.
     */
    public function __construct($providerKey, $accessToken = false) {
        $this->providerKey = $providerKey;
        $this->provider = provider();
        if ($accessToken) {
            // We passed in a connection
            $this->accessToken = $accessToken;
        }
    }


    /**
     * Setup
     */
    public function setUp() {
        $this->structure();
    }


    /**
     * Create the structure in the database.
     */
    public function structure() {
        // Make sure we have the OAuth2 provider.
        $provider = $this->provider();
        if (!$provider['AuthenticationKey']) {
            $model = new Gdn_AuthenticationProviderModel();
            $provider = [
                'AuthenticationKey' => $this->providerKey,
                'AuthenticationSchemeAlias' => $this->providerKey,
                'Name' => $this->providerKey,
                'AcceptedScope' => 'profile',
                'ProfileKeyEmail' => 'email', // Can be overwritten in settings, the key the authenticator uses for email in response.
                'ProfileKeyPhoto' => 'picture',
                'ProfileKeyName' => 'displayname',
                'ProfileKeyFullName' => 'name',
                'ProfileKeyUniqueID' => 'user_id'
            ];

            $model->save($provider);
        }
    }


    /**
     * Check if there is enough data to connect to an authentication provider.
     *
     * @return bool True if there is a secret and a client_id, false if not.
     */
    public function isConfigured() {
        $provider = $this->provider();
        return $provider['AssociationSecret'] && $provider['AssociationKey'];
    }


    /**
     * Check if an access token has been returned from the provider server.
     *
     * @return bool True of there is an accessToken, fals if there is not.
     */
    public function isConnected() {
        if (!$this->accessToken) {
            return false;
        }
        return true;
    }


    /**
     * Check authentication provider table to see if this is the default method for logging in.
     *
     * @return bool Return the value of the IsDefault row of GDN_UserAuthenticationProvider .
     */
    public function isDefault() {
        $provider = $this->provider();
        return $provider['IsDefault'];
    }


    /**
     * Renew or return access token.
     *
     * @param bool|string $newValue Pass existing token if it exists.
     *
     * @return bool|string|null String if there is an accessToken passed or found in session, false or null if not.
     */
    public function accessToken($newValue = false) {
        if (!$this->isConfigured() && $newValue === false) {
            return false;
        }

        if ($newValue !== false) {
            $this->accessToken = $newValue;
        }

        // If there is no token passed, try to retrieve one from the user's attributes.
        if ($this->accessToken === null) {
            $this->accessToken = valr($this->getProviderKey().'.AccessToken', Gdn::session()->User->Attributes);
        }

        return $this->accessToken;
    }


    /**
     * Set access token received from provider.
     *
     * @param string $accessToken Retrieved from provider to authenticate communication.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setAccessToken($accessToken) {
        $this->accessToken = $accessToken;
        return $this;
    }


    /**
     * Set provider key used to access settings stored in GDN_UserAuthenticationProvider.
     *
     * @param string $providerKey Key to retrieve provider data hardcoded into child class.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setProviderKey($providerKey) {
        $this->providerKey = $providerKey;
        return $this;
    }


    /**
     * Set scope to be passed to provider.
     *
     * @param string $scope.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setScope($scope) {
        $this->scope = $scope;
        return $this;
    }


    /**
     * Set additional params to be added to the get string in the AuthorizeUri string.
     *
     * @param string $params.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setAuthorizeUriParams($params) {
        $this->authorizeUriParams = $params;
        return $this;
    }


    /**
     * Set additional params to be added to the post array in the accessToken request.
     *
     * @param string $params.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setRequestAccessTokenParams($params) {
        $this->requestAccessTokenParams = $params;
        return $this;
    }


    /**
     * Set additional params to be added to the get string in the getProfile request.
     *
     * @param string $params.
     *
     * @return $this Return this object for chaining purposes.
     */
    public function setGetProfileParams($params) {
        $this->getProfileParams = $params;
        return $this;
    }



    /** ------------------- Provider Methods --------------------- */

    /**
     *  Return all the information saved in provider table.
     *
     * @return array Stored provider data (secret, client_id, etc.).
     */
    public function provider() {
        if (!$this->provider) {
            $this->provider = Gdn_AuthenticationProviderModel::getProviderByKey($this->providerKey);
        }

        return $this->provider;
    }


    /**
     *  Get provider key.
     *
     * @return string Provider key.
     */
    public function getProviderKey() {
        return $this->providerKey;
    }



    /** ------------------- Settings Related Methods --------------------- */

    /**
     * Allow child class to over-ride or add form fields to settings.
     *
     * @return array Form fields to appear in settings dashboard.
     */
    protected function getSettingsFormFields() {
        $formFields = [
            'RegisterUrl' => ['LabelCode' => 'Register Url', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the endpoint to be appended to the base domain to direct a user to register.'],
            'SignOutUrl' => ['LabelCode' => 'Sign Out Url', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the endpoint to be appended to the base domain to log a user out.'],
            'AcceptedScope' => ['LabelCode' => 'Request Scope', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the scope to be sent with Token Requests.'],
            'ProfileKeyEmail' => ['LabelCode' => 'Email', 'Options' => ['Class' => 'InputBox'], 'Description' => 'The Key in the JSON array to designate Emails'],
            'ProfileKeyPhoto' => ['LabelCode' => 'Photo', 'Options' => ['Class' => 'InputBox'], 'Description' => 'The Key in the JSON array to designate Photo.'],
            'ProfileKeyName' => ['LabelCode' => 'Display Name', 'Options' => ['Class' => 'InputBox'], 'Description' => 'The Key in the JSON array to designate Display Name.'],
            'ProfileKeyFullName' => ['LabelCode' => 'Full Name', 'Options' => ['Class' => 'InputBox'], 'Description' => 'The Key in the JSON array to designate Full Name.'],
            'ProfileKeyUniqueID' => ['LabelCode' => 'User ID', 'Options' => ['Class' => 'InputBox'], 'Description' => 'The Key in the JSON array to designate UserID.']
        ];
        return $formFields;
    }


    /**
     * Create a controller to deal with plugin settings in dashboard.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function settingsController_dashboard_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $model = new Gdn_AuthenticationProviderModel();

        /* @var Gdn_Form $form */
        $form = new Gdn_Form();
        $form->setModel($model);
        $sender->Form = $form;

        if (!$form->AuthenticatedPostBack()) {
            $provider = Gdn_AuthenticationProviderModel::GetProviderByKey($this->getProviderKey());
            $form->setData($provider);
        } else {

            $form->setFormValue('AuthenticationKey', $this->getProviderKey());

            $sender->Form->validateRule('AssociationKey', 'ValidateRequired', 'You must provide a unique AccountID.');
            $sender->Form->validateRule('AssociationSecret', 'ValidateRequired', 'You must provide a Secret');
            $sender->Form->validateRule('AuthorizeUrl', 'isUrl', 'You must provide a complete URL in the Authorize Url field.');
            $sender->Form->validateRule('TokenUrl', 'isUrl', 'You must provide a complete URL in the Token Url field.');

            // To satisfy the AuthenticationProviderModel, create a BaseUrl.
            $baseUrlParts = parse_url($form->getValue('AuthorizeUrl'));
            $baseUrl = (val('scheme', $baseUrlParts) && val('host', $baseUrlParts)) ? val('scheme', $baseUrlParts).'://'.val('host', $baseUrlParts) : null;
            if ($baseUrl) {
                $form->setFormValue('BaseUrl', $baseUrl);
                $form->setFormValue('SignInUrl', $baseUrl); // kludge for default provider
            }
            if ($form->save()) {
                $sender->informMessage(t('Saved'));
            }
        }

        // Set up the form.
        $formFields = [
            'AssociationKey' =>  ['LabelCode' => 'Client ID', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the unique ID of the authentication application.'],
            'AssociationSecret' =>  ['LabelCode' => 'Secret', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the secret provided by the authentication provider.'],
            'AuthorizeUrl' =>  ['LabelCode' => 'Authorize Url', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the endpoint to be appended to the base domain to retrieve the authorization token for a user.'],
            'TokenUrl' => ['LabelCode' => 'Token Url', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the endpoint to be appended to the base domain to retrieve the authorization token for a user.'],
            'ProfileUrl' => ['LabelCode' => 'Profile Url', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => 'Enter the endpoint to be appended to the base domain to retrieve a user\'s profile.']
        ];

        $formFields =$formFields + $this->getSettingsFormFields();

        $formFields['IsDefault'] = ['LabelCode' => 'Make this connection your default signin method.', 'Control' => 'checkbox'];


        $sender->setData('_Form', $formFields);

        $sender->addSideMenu();
        if (!$sender->data('Title')) {
            $sender->setData('Title', sprintf(T('%s Settings'), 'Oauth2 SSO'));
        }

        $view = ($this->settingsView) ? $this->settingsView : 'plugins/'.$this->getProviderKey();

        // Create send the possible redirect URLs that will be required by Oculus and display them in the dashboard.
        // Use Gdn::Request instead of convience function so that we can return http and https.
        $redirectUrls = Gdn::request()->url('/entry/'. $this->getProviderKey(), true, true);
        $sender->setData('redirectUrls', $redirectUrls);

        $sender->render('settings', '', 'plugins/'.$view);
    }



    /** ------------------- Connection Related Methods --------------------- */

    /**
     * Create the URI that can return an authorization.
     *
     * @param array $state Optionally provide an array of variables to be sent to the provider.
     *
     * @return string Endpoint of the provider.
     */
    public function authorizeUri($state = []) {
        $provider = $this->provider();

        $uri = $provider['AuthorizeUrl'];

        $redirect_uri = '/entry/'.$this->getProviderKey();

        $defaultParams = [
            'response_type' => 'code',
            'client_id' => $provider['AssociationKey'],
            'redirect_uri' => url($redirect_uri, true),
            'scope' => $this->scope
        ];
        // allow child class to overwrite or add to the authorize URI.
        $get = array_merge($defaultParams, $this->authorizeUriParams);

        if (is_array($state)) {
            if (is_array($state)) {
                $get['state'] = http_build_query($state);
            }
        }
        return $uri.'?'.http_build_query($get);
    }


    /**
     * Generic API uses ProxyRequest class to fetch data from remote endpoints.
     *
     * @param $uri Endpoint on provider's server.
     * @param string $method HTTP method required by provider.
     * @param array $params Query string.
     * @param array $options Configuration options for the request (e.g. Content-Type).
     *
     * @return mixed|type.
     *
     * @throws Exception.
     * @throws Gdn_UserException.
     */
    protected function api($uri, $method = 'GET', $params = [], $options = []) {
        $proxy = new ProxyRequest();

        // Create default values of options to be passed to ProxyRequest.
        $defaultOptions['ConnectTimeout'] = 10;
        $defaultOptions['Timeout'] = 10;

        $headers = [];

        // Optionally over-write the content type
        if ($contentType = val('Content-Type', $options, $this->defaultContentType)) {
            $headers['Content-Type'] = $contentType;
        }

        // Obtionally add proprietary required Authorization headers
        if ($headerAuthorization = val('Authorization-Header-Message', $options, null)) {
            $headers['Authorization'] = $headerAuthorization;
        }

        // Merge the default options with the passed options over-writing default options with passed options.
        $proxyOptions = array_merge($defaultOptions, $options);

        $proxyOptions['URL'] = $uri;
        $proxyOptions['Method'] = $method;

        $this->log('Proxy Request Sent in API', ['headers' => $headers, 'proxyOptions' => $proxyOptions, 'params' => $params]);

        $response = $proxy->request(
            $proxyOptions,
            $params,
            null,
            $headers
        );

        // Extract response only if it arrives as JSON
        if (stripos($proxy->ContentType, 'application/json') !== false) {
            $this->log('API JSON Response', ['response' => $response]);
            $response = json_decode($proxy->ResponseBody, true);
        }

        // Return any errors
        if (!$proxy->responseClass('2xx')) {
            if (isset($response['error'])) {
                $message = 'Request server says: '.$response['error_description'].' (code: '.$response['error'].')';
            } else {
                $message = 'HTTP Error communicating Code: '.$proxy->ResponseStatus;
            }
            $this->log('API Response Error Thrown', ['response' => json_decode($response)]);
            throw new Gdn_UserException($message, $proxy->ResponseStatus);
        }

        return $response;
    }


    /**
     * Register a call back function so that multiple plugins can use it as an entry point on SSO
     * This endpoint is executed on /entry/[provider] and is used as the redirect after making an
     * initial request to log in to an authentication provider.
     *
     * @param $sender
     */
    public function gdn_pluginManager_afterStart_handler($sender) {
        $sender->registerCallback("entryController_{$this->providerKey}_create", [$this, 'entryEndpoint']);
    }


    /**
     * Create a controller to handle entry request.
     *
     * @param Gdn_Controller $sender.
     * @param $code string Retrieved from the response of the authentication provider, used to fetch an authentication token.
     * @param $state string Values passed by us and returned in the response of the authentication provider.
     *
     * @throws Exception.
     * @throws Gdn_UserException.
     */
    public function entryEndpoint($sender, $code, $state) {
        if ($error = $sender->Request->get('error')) {
            throw new Gdn_UserException($error);
        }

        Gdn::session()->stash($this->getProviderKey()); // remove any stashed provider data.

        $response = $this->requestAccessToken($code);
        if (!$response) {
            throw new Gdn_UserException('The OAuth server did not return a valid response.');
        }

        if (!empty($response['error'])) {
            throw new Gdn_UserException($response['error_description']);
        } elseif (empty($response['access_token'])) {
            throw new Gdn_UserException('The OAuth server did not return an access token.', 400);
        } else {
            $this->accessToken($response['access_token']);
        }

        $this->log('Getting Profile', []);
        $profile = $this->getProfile();
        $this->log('Profile', $profile);

        if ($state) {
            parse_str($state, $state);
        } else {
            $state = ['r' => 'entry', 'uid' => null, 'd' => 'none'];
        }
        switch ($state['r']) {
            case 'profile':
                // This is a connect request from the user's profile.
                $user = Gdn::userModel()->getID($state['uid']);
                if (!$user) {
                    throw notFoundException('User');
                }
                // Save the authentication.
                Gdn::userModel()->saveAuthentication([
                    'UserID' => $user->UserID,
                    'Provider' => $this->getProviderKey(),
                    'UniqueID' => $profile['id']]);

                // Save the information as attributes.
                $attributes = [
                    'AccessToken' => $response['access_token'],
                    'Profile' => $profile
                ];

                Gdn::userModel()->saveAttribute($user->UserID, $this->getProviderKey(), $attributes);

                $this->EventArguments['Provider'] = $this->getProviderKey();
                $this->EventArguments['User'] = $sender->User;
                $this->fireEvent('AfterConnection');

                redirect(userUrl($user, '', 'connections'));
                break;
            case 'entry':
            default:

                // This is an sso request, we need to redispatch to /entry/connect/[providerKey] which is Base_ConnectData_Handler() in this class.
                Gdn::session()->stash($this->getProviderKey(), ['AccessToken' => $response['access_token'], 'Profile' => $profile]);
                $url = '/entry/connect/'.$this->getProviderKey();

                //pass the target if there is one so that the user will be redirected to where the request originated.
                if ($target = val('target', $state)) {
                    $url .= '?Target='.urlencode($target);
                }
                redirect($url);
                break;
        }
    }


    /**
     * Inject into the process of the base connection.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != $this->getProviderKey()) {
            return;
        }

        // Retrieve the profile that was saved to the session in the entry controller.
        $savedProfile = Gdn::session()->stash($this->getProviderKey(), '', false);
        if (Gdn::session()->stash($this->getProviderKey(), '', false)) {
            $this->log('Base Connect Data Profile Saved in Session', ['profile' => $savedProfile]);
        }
        $profile = val('Profile', $savedProfile);
        $accessToken = val('AccessToken', $savedProfile);

        trace($profile, 'Profile');
        trace($accessToken, 'Access Token');

        /* @var Gdn_Form $form */
        $form = $sender->Form; //new Gdn_Form();

        // Create a form and populate it with values from the profile.
        $originaFormValues = $form->formValues();
        $formValues = array_replace($originaFormValues, $profile);
        $form->formValues($formValues);
        trace($formValues, 'Form Values');

        // Save some original data in the attributes of the connection for later API calls.
        $attributes = [];
        $attributes[$this->getProviderKey()] = [
            'AccessToken' => $accessToken,
            'Profile' => $profile
        ];
        $form->setFormValue('Attributes', $attributes);

        $sender->EventArguments['Profile'] = $profile;
        $sender->EventArguments['Form'] = $form;

        $this->log('Base Connect Data Before OAuth Event', ['profile' => $profile, 'form' => $form]);

        // Throw an event so that other plugins can add/remove stuff from the basic sso.
        $sender->fireEvent('OAuth');

        SpamModel::disabled(true);
        $sender->setData('Trusted', true);
        $sender->setData('Verified', true);
    }


    /**
     * Request access token from provider.
     *
     * @param string $code code returned from initial handshake with provider.
     *
     * @return mixed Result of the API call to the provider, usually JSON.
     */
    public function requestAccessToken($code) {
        $provider = $this->provider();
        $uri = $provider['TokenUrl'];

        $defaultParams = [
            'code' => $code,
            'client_id' => $provider['AssociationKey'],
            'redirect_uri' => url('/entry/'. $this->getProviderKey(), true),
            'client_secret' => $provider['AssociationSecret'],
            'grant_type' => 'authorization_code',
            'scope' => $this->scope
        ];

        $post = array_merge($defaultParams, $this->requestAccessTokenParams);

        $this->log('Before calling API to request access token', ['requestAccessToken' => ['targetURI' => $uri, 'post' => $post]]);

        return $this->api($uri, 'POST', $post);
    }


    /**
     *   Allow the admin to input the keys that their service uses to send data.
     *
     * @param array $rawProfile profile as it is returned from the provider.
     *
     * @return array Profile array transformed by child class or as is.
     */
    public function translateProfileResults($rawProfile = []) {
        $provider = $this->provider();
        $translatedKeys = [
            val('ProfileKeyEmail', $provider, 'email') => 'Email',
            val('ProfileKeyPhoto', $provider, 'picture') => 'Photo',
            val('ProfileKeyName', $provider, 'displayname') => 'Name',
            val('ProfileKeyFullName', $provider, 'name') => 'FullName',
            val('ProfileKeyUniqueID', $provider, 'user_id') => 'UniqueID'
        ];

        $profile = arrayTranslate($rawProfile, $translatedKeys, true);

        $profile['Provider'] = $this->providerKey;

        return $profile;
    }


    /**
     * Get profile data from authentication provider through API.
     *
     * @return array User profile from provider.
     */
    public function getProfile() {
        $provider = $this->provider();

        $uri = $this->requireVal('ProfileUrl', $provider, 'provider');

        $defaultParams = array(
            'access_token' => $this->accessToken()
        );

        $requestParams = array_merge($defaultParams, $this->profileRequestParams);

        $rawProfile = $this->api($uri, 'GET', $requestParams);

        $profile = $this->translateProfileResults($rawProfile);

        $this->log('getProfile API call', ['ProfileUrl' => $uri, 'Params' => $requestParams, 'RawProfile' => $rawProfile, 'Profile' => $profile]);

        return $profile;
    }



    /** ------------------- Buttons, linking --------------------- */

    /**
     * Redirect to provider's signin page if this is the default behaviour.
     *
     * @param EntryController $sender.
     * @param EntryController $args.
     *
     * @return mixed|bool Return null if not configured.
     */
    public function entryController_overrideSignIn_handler($sender, $args) {
        $provider = $args['DefaultProvider'];
        if ($provider['AuthenticationSchemeAlias'] != $this->getProviderKey() || !$this->isConfigured()) {
            return;
        }

        $url = $this->authorizeUri(array('target' => $args['Target']));
        $args['DefaultProvider']['SignInUrl'] = $url;
    }


    /**
     * Inject a sign-in icon into the ME menu.
     *
     * @param Gdn_Controller $sender.
     * @param Gdn_Controller $args.
     */
    public function base_beforeSignInButton_handler($sender, $args) {
        if (!$this->isConfigured() || $this->isDefault()) {
            return;
        }

        echo ' '.$this->signInButton('icon').' ';
    }


    /**
     * Inject sign-in button into the sign in page.
     *
     * @param EntryController $sender.
     * @param EntryController $args.
     *
     * @return mixed|bool Return null if not configured
     */
    public function entryController_signIn_handler($sender, $args) {
        if (!$this->isConfigured()) {
            return;
        }
        if (isset($sender->Data['Methods'])) {
            // Add the sign in button method to the controller.
            $method = [
                'Name' => $this->getProviderKey(),
                'SignInHtml' => $this->signInButton()
            ];

            $sender->Data['Methods'][] = $method;
        }
    }


    /**
     * Create signup button specific to this plugin.
     *
     * @param string $type Either button or icon to be output.
     *
     * @return string Resulting HTML element (button).
     */
    public function signInButton($type = 'button') {
        $target = Gdn::request()->post('Target', Gdn::request()->get('Target', url('', '/')));
        $url = $this->authorizeUri(['target' => $target]);
        $result = socialSignInButton('OAuth2', $url, $type, ['rel' => 'nofollow', 'class' => 'default', 'title' => t('Sign in with OAuth2')]);
        return $result;
    }


    /** ------------------- Helper functions --------------------- */

    /**
     * Extract values from arrays.
     *
     * @param string $key Needle.
     * @param array $arr Haystack.
     * @param string $context Context to make error messages clearer.
     *
     * @return mixed Extracted value from array.
     *
     * @throws Exception.
     */
    function requireVal($key, $arr, $context = null) {
        $result = val($key, $arr);
        if (!$result) {
            throw new \Exception("Key {$key} missing from {$context} collection.", 500);
        }
        return $result;
    }


    public function log($message, $data) {
        if (c('Vanilla.SSO.Debug')) {
            Logger::event(
                'sso_logging',
                Logger::INFO,
                $message,
                $data
            );
        }
    }
}
