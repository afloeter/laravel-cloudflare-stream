# laravel-cloudflare-stream
Manage Cloudflare Stream with ease by using this handy PHP API wrapper. The `laravel-cloudflare-stream` package gives ability to...

* ✓ **List your videos**
    * optionally using parameters to filter results
        * *after*
        * *before*
        * *include_counts*
        * *search*
        * *limit*
        * *asc*
        * *status*
* ✓ **Details of your videos**
    * Meta information (read / write)
    * Video name (read / write)
    * Require signed URLs (read-only)
    * Width and height (read-only)
* ✓ **Get embed code of your videos**
    * With or without signed URLs
    * Add attributes to embed code
        * *controls*
* ✓ **Get playback URLs of your videos**
    * With or without signed token
* ✓ **Generate signed tokens for your videos**
* ✓ **Delete your videos**

Feel free to check out the Cloudflare Stream [documentation](https://developers.cloudflare.com/stream/) and [API documentation](https://api.cloudflare.com/#stream-videos-properties) for further information.

## Installation

### Step 1: Install using Composer
Add the following to your root `composer.json` and install with `composer install` or `composer update`.

    {
      "require": {
        "afloeter/laravel-cloudflare-stream": "~1.0.0"
      }
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/afloeter/laravel-cloudflare-stream"
        }
    ]

...or use `composer require afloeter/laravel-cloudflare-stream` in your console after just adding the repository to your composer.json file.

### Step 2: Publish the config file for Laravel projects
Publish the config file with `php artisan vendor:publish --provider="AFloeter\CloudflareStreamServiceProvider"`.

### Step 3: Add informationen to Laravel's `.env` file
Add the following lines to your root `.env` file of your Laravel instance.

    CLOUDFLARE_STREAM_ACCOUNT_ID=
    CLOUDFLARE_STREAM_AUTH_KEY=
    CLOUDFLARE_STREAM_AUTH_EMAIL=
    CLOUDFLARE_STREAM_PRIVATE_KEY_ID=
    CLOUDFLARE_STREAM_PRIVATE_KEY_TOKEN=

Complete the following information.

* `CLOUDFLARE_STREAM_ACCOUNT_ID` is your [Cloudflare account](https://dash.cloudflare.com/) ID.
* `CLOUDFLARE_STREAM_AUTH_KEY` is your [Cloudflare API key](https://dash.cloudflare.com/profile/api-tokens).
* `CLOUDFLARE_STREAM_AUTH_EMAIL` is the email address of your [Cloudflare account](https://dash.cloudflare.com/profile).

Leave `CLOUDFLARE_STREAM_PRIVATE_KEY_ID` and `CLOUDFLARE_STREAM_PRIVATE_KEY_TOKEN` blank if you don't use signed URLs at all.

* `CLOUDFLARE_STREAM_PRIVATE_KEY_ID` is the ID of your signing key
* `CLOUDFLARE_STREAM_PRIVATE_KEY_TOKEN` is the related RSA private key.

Otherwise: Check the [documentation](https://developers.cloudflare.com/stream/security/signed-urls/) on [how to create a signing key and get RSA private key](https://developers.cloudflare.com/stream/security/signed-urls/#creating-a-signing-key) in PEM format.

## Usage

### Laravel
If you have done the `vendor:publish` step, your credentials will be grabbed from the `config/cloudflare-stream.php` and / or `.env` file. So, you can use `CloudflareStreamLaravel()` without providing your information once again.

    use AFloeter\CloudflareStream\CloudflareStreamLaravel;
    
    ...
    
    $cfs = new CloudflareStreamLaravel();
    $listOfVideos = $cfs->list();
    
    ...

### Generic PHP
If you are on composer-enabled projects use `CloudflareStream()`. Without composer try requiring `src/CloudflareStream.php` directly into your project.

    use AFloeter\CloudflareStream\CloudflareStream;
    
    ...
    
    $cfs = new CloudflareStream($accountId, $authKey, $authEMail);
    $listOfVideos = $cfs->list();
    
    ...

If you are using signed URLs for your videos, simply add the `$privateKey` and `$privateKeyToken` variables.

    use AFloeter\CloudflareStream\CloudflareStream;
    
    ...
    
    $cfs = new CloudflareStream($accountId, $authKey, $authEMail, $privateKey, $privateKeyToken);
    $signedToken = $cfs->getSignedToken($videoId);
    
    ...

## To Do
It's planned to add support to...

* **Upload a video**
    * From a URL
    * Using a single HTTP request
    * Using `ankitpokhrel/tus-php`
* **User uploads**
    * Create a video and get authenticated direct upload URL 
* **Create and revoke signing keys.**
* **Add, get and remove `.vtt` caption files.**
* **Set, get and remove allowed origins**

## Changelog

All notable changes to `laravel-cloudflare-stream` will be documented here.

### 1.0.0 - 2020-06-12
* initial release

## License
`laravel-cloudflare-stream` is distributed under the terms of the [MIT License](LICENSE).
