<?php

namespace AFloeter\CloudflareStream;

use AFloeter\CloudflareStream\Exceptions\NoCredentialsException;
use AFloeter\CloudflareStream\Exceptions\NoPrivateKeyOrTokenException;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class CloudflareStream
{
    private $accountId;
    private $authKey;
    private $authEMail;
    private $privateKeyId;
    private $privateKeyToken;
    private $guzzle;

    /**
     * CloudflareStream constructor.
     *
     * @param string $accountId
     * @param string $authKey
     * @param string $authEMail
     * @param null $privateKey
     * @param null $privateKeyToken
     * @throws NoCredentialsException
     */
    public function __construct(string $accountId, string $authKey, string $authEMail, $privateKey = null, $privateKeyToken = null)
    {
        if (empty($accountId) || empty($authKey) || empty($authEMail)) {
            throw new NoCredentialsException();
        }

        $this->accountId = $accountId;
        $this->authKey = $authKey;
        $this->authEMail = $authEMail;
        $this->guzzle = new Client([
            'base_uri' => 'https://api.cloudflare.com/client/v4/'
        ]);

        if (!empty($privateKey) && !empty($privateKeyToken)) {
            $this->privateKeyId = $privateKey;
            $this->privateKeyToken = $privateKeyToken;
        }
    }

    /**
     * Get a list of videos.
     *
     * @param array $customParameters
     * @return string
     * @throws GuzzleException
     */
    public function list($customParameters = [])
    {
        // Define standard parameters
        $parameters = [
            'include_counts' => true,
            'limit' => 1000,
            'asc' => false
        ];

        // Set custom parameters
        if (!empty($customParameters) && is_array($customParameters)) {
            $parameters = array_merge($parameters, $customParameters);
        }

        return $this->request('accounts/' . $this->accountId . '/stream?' . http_build_query($parameters))->getBody()->getContents();
    }

    /**
     * Fetch details of a single video.
     *
     * @param string $uid
     * @return string
     * @throws GuzzleException
     */
    public function video(string $uid)
    {
        return $this->request('accounts/' . $this->accountId . '/stream/' . $uid)->getBody()->getContents();
    }

    /**
     * Get embed code. Could be returned with signed token if necessary.
     *
     * @param string $uid
     * @param bool $addControls
     * @param bool $useSignedToken
     * @return string
     * @throws NoPrivateKeyOrTokenException|GuzzleException
     */
    public function embed(string $uid, bool $addControls = false, bool $useSignedToken = true)
    {
        $embed = $this->request('accounts/' . $this->accountId . '/stream/' . $uid . '/embed')->getBody();
        $requireSignedToken = false;

        // Require signed token?
        if ($useSignedToken) {
            $video = json_decode($this->video($uid), true);
            $requireSignedToken = $video['result']['requireSignedURLs'];
        }

        // Add controls attribute?
        if ($addControls) {
            return str_replace('src="' . $uid . '"', 'src="' . ($useSignedToken && $requireSignedToken ? $this->getSignedToken($uid) : $uid) . '" controls', $embed);
        }

        // Signed URL necessary?
        if ($useSignedToken && $requireSignedToken) {
            return str_replace('src="' . $uid . '"', 'src="' . $this->getSignedToken($uid) . '"', $embed);
        }

        // Return embed code
        return $embed;
    }

    /**
     * Delete video.
     *
     * @param string $uid
     * @return string
     * @throws GuzzleException
     */
    public function delete(string $uid)
    {
        return $this->request('accounts/' . $this->accountId . '/stream/' . $uid, 'delete')->getBody()->getContents();
    }

    /**
     * Get meta data for a specific video.
     *
     * @param string $uid
     * @return array
     * @throws GuzzleException
     */
    public function getMeta(string $uid)
    {

        // Get all data
        $data = json_decode($this->video($uid), true);

        // Return meta data
        return $data['result']['meta'];
    }

    /**
     * Set meta data for a specific video.
     *
     * @param string $uid
     * @param array $meta
     * @return string
     * @throws GuzzleException
     */
    public function setMeta(string $uid, array $meta)
    {
        // Merge meta data
        $meta = [
            'meta' => array_merge($this->getMeta($uid), $meta)
        ];

        // Request
        $response = $this->request('accounts/' . $this->accountId . '/stream/' . $uid, 'post', $meta);

        // Return result
        return $response->getBody()->getContents();
    }

    /**
     * Remove meta data by key.
     *
     * @param string $uid
     * @param string $metaKey
     * @return string
     * @throws GuzzleException
     */
    public function removeMeta(string $uid, string $metaKey)
    {

        // Merge meta data
        $meta = [
            'meta' => array_merge($this->getMeta($uid))
        ];

        // Remove key
        if (array_key_exists($metaKey, $meta['meta'])) {
            unset($meta['meta'][$metaKey]);
        }

        // Request
        return $this->request('accounts/' . $this->accountId . '/stream/' . $uid, 'post', $meta)->getBody()->getContents();

    }

    /**
     * Get name of a video.
     *
     * @param string $uid
     * @return string
     * @throws GuzzleException
     */
    public function getName(string $uid)
    {
        $meta = $this->getMeta($uid);
        return $meta['name'];
    }

    /**
     * Rename a video.
     *
     * @param string $uid
     * @param string $name
     * @return string
     * @throws GuzzleException
     */
    public function setName(string $uid, string $name)
    {
        return $this->setMeta($uid, ['name' => $name]);
    }

    /**
     * Set whether a specific video requires signed URLs or not.
     *
     * @param string $uid
     * @param bool $required
     * @return string
     * @throws GuzzleException
     */
    public function setSignedURLs(string $uid, bool $required)
    {
        return $this->request('accounts/' . $this->accountId . '/stream/' . $uid, 'post', [
            'uid' => $uid,
            'requireSignedURLS' => $required
        ])->getBody()->getContents();
    }

    /**
     * Get playback URLs of a specific video.
     *
     * @param string $uid
     * @param bool $useSignedToken
     * @return string
     * @throws GuzzleException
     * @throws NoPrivateKeyOrTokenException
     */
    public function getPlaybackURLs(string $uid, $useSignedToken = true)
    {

        // Get all data
        $video = json_decode($this->video($uid), true);

        // Signed URL necessary?
        if ($useSignedToken && $video['result']['requireSignedURLs']) {

            // Replace uid with signed token
            foreach ($video['result']['playback'] as $key => $value) {
                $video['result']['playback'][$key] = str_replace($uid, $this->getSignedToken($uid), $value);
            }

        }

        // Return playback URLs
        return json_encode($video['result']['playback']);

    }

    /**
     * Get signed token for a video.
     *
     * @param string $uid
     * @param int $addHours
     * @return string
     * @throws NoPrivateKeyOrTokenException
     */
    public function getSignedToken(string $uid, int $addHours = 4)
    {
        if (empty($this->privateKeyId) || empty($this->privateKeyToken)) {
            throw new NoPrivateKeyOrTokenException();
        }

        return JWT::encode([
            'kid' => $this->privateKeyId,
            'sub' => $uid,
            "exp" => time() + ($addHours * 60 * 60)
        ], base64_decode($this->privateKeyToken), 'RS256');
    }

    /**
     * Get width and height of a video.
     *
     * @param string $uid
     * @return string
     * @throws GuzzleException
     */
    public function getDimensions(string $uid)
    {
        // Get all data
        $video = json_decode($this->video($uid), true);

        // Return playback URLs
        return json_encode($video['result']['input']);
    }

    /**
     * Request wrapper function.
     *
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function request(string $endpoint, $method = 'get', $data = [])
    {
        // Define headers.
        $headers = [
            'X-Auth-Key' => $this->authKey,
            'X-Auth-Email' => $this->authEMail,
            'Content-Type' => 'application/json'
        ];

        // Define options for post request method...
        if (count($data) && $method === "post") {
            $options = [
                'headers' => $headers,
                RequestOptions::JSON => $data
            ];
        } // ...or define options for all other request methods
        else {
            $options = [
                'headers' => $headers,
            ];
        }

        return $this->guzzle->request($method, $endpoint, $options);
    }
}
