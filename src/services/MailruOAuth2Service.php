<?php
/**
 * MailruOAuth2Service class file.
 *
 * Register application: https://api.mail.ru/sites/my
 *
 * @author Maxim Zemskov <nodge@yandex.ru>
 * @link http://github.com/Nodge/yii2-eauth/
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace yareg\eauth\services;

use yareg\eauth\oauth2\Service;

/**
 * Mail.Ru provider class.
 *
 * @package application.extensions.eauth.services
 */
class MailruOAuth2Service extends Service
{

	protected $name = 'mailru';
	protected $title = 'Mail.ru';
	protected $type = 'OAuth2';
	protected $jsArguments = ['popup' => ['width' => 580, 'height' => 400]];

	protected $scopes = [];
	protected $providerOptions = [
		'authorize' => 'https://connect.mail.ru/oauth/authorize',
		'access_token' => 'https://connect.mail.ru/oauth/token',
	];
	protected $baseApiUrl = 'http://www.appsmail.ru/platform/api';

    protected $response;

    /**
     * @return bool
     * @throws \ErrorException
     */
	protected function fetchAttributes() : bool
	{
		$tokenData = $this->getAccessTokenData();

		$data = $this->makeSignedRequest('/', [
			'query' => [
				'uids' => $tokenData['params']['x_mailru_vid'],
				'method' => 'users.getInfo',
				'app_id' => $this->clientId,
			],
		]);

        $this->response = $data[0];

		$this->attributes['id']   = $this->response['uid'];
		$this->attributes['name'] = $this->response['first_name'] . ' ' . $this->response['last_name'];
		$this->attributes['url']  = $this->response['link'];

		return true;
	}

	/**
	 * Returns the protected resource.
	 *
	 * @param string $url url to request.
	 * @param array $options HTTP request options. Keys: query, data, referer.
	 * @param boolean $parseResponse Whether to parse response.
     * @throws \ErrorException
	 * @return mixed the response.
	 */
	public function makeSignedRequest($url, $options = [], $parseResponse = true)
	{
		$token = $this->getAccessTokenData();
		if (isset($token)) {
			$options['query']['secure'] = 1;
			$options['query']['session_key'] = $token['access_token'];
			$params = '';
			ksort($options['query']);
			foreach ($options['query'] as $k => $v) {
				$params .= $k . '=' . $v;
			}
			$options['query']['sig'] = md5($params . $this->clientSecret);
		}
		return parent::makeSignedRequest($url, $options, $parseResponse);
	}

	/**
	 * Returns the error array.
	 *
	 * @param array $response
	 * @return array the error array with 2 keys: code and message. Should be null if no errors.
	 */
	protected function fetchResponseError($response)
	{
		if (isset($response['error'])) {
			return [
				'code' => $response['error']['error_code'],
				'message' => $response['error']['error_msg'],
			];
		}

		return null;
	}
}