# asset-downloader-lib
This repo provides a React hook for initiating a request with the server to download a registered asset. It is available as an NPM package:

```
npm install -S https://github.com/reactr-io/asset-downloader-lib.git
```

The React Hook is a client-side library for interfacing with our WordPress REST API backend, which is where you register your assets, and where the files are fetched.

```
composer require https://github.com/reactr-io/asset-downloader-lib.git @dev
```

# Getting Started

## What's an asset?

An asset is a url that you wish to download on the server. This asset could be a ZIP, PDF, image, or something else.

Why fetch it on the server and not the client? Security - you might not wish to disclose on the client the url of the resource, or the path on the server where the asset is being downloaded to.

An asset is registered server-side using an array of keys to uniquely identify it.

## How do I register the assets?

This library is expected to be consumed by a WordPress plugin or theme. In your PHP code you would register an asset using:

```
$keys = ['reactr-theme', 'demo-content'];
$data = [
    'url'   => 'https://foobar.com/zips/reactr/demo.xml'
];

\ReactrIO\AssetDownloader\AssetManager::add($keys, $data);
```

## What endpoint do I send the HTTP request to?

Add the following to your PHP code:

```
\ReactrIO\AssetDownloader\Endpoint::get_instance('requestImagelyAsset');
```

The above will register requestImagelyAsset as an endpoint for the WordPress REST API.

# Where is this used?

The asset-downloader-lib is used by the [reactr-theme](https://github.com/reactr-io/reactr-theme)