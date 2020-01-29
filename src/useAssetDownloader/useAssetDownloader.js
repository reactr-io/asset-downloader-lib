import React, { useState, useEffect } from 'react'

const axios = require('axios').default
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-WP-Nonce'] = wpApiSettings.nonce

/**
 * Will throw an error if the response contains one
 */
const throwErrorDetails = data => {
  if (!data || typeof data !== 'object') {
    const err = new Error('E_ASSET_DOWNLOADER')
    err.context = {
      error_code : 'Fatal',
      error_msg :  'An unexpected error occured. Invalid response'
    }
    throw err
  } else if (data.error_code) {
    const err = new Error('E_ASSET_DOWNLOADER')
    err.context = data
    throw err
  }
  return data
}

const requestAsset = endpointUrl => asset => axios.post(endpointUrl, { asset }).then(throwErrorDetails)

const promiseMap = (arr, f) => arr.reduce(
  (p, item) => p.then(acc => f(item).then(retval => [...acc, retval])),
  Promise.resolve([])
)

const useAssetDownloader = ({ endpointUrl, assets, running = false }) => {
  const [err, setError] = useState(null)
  const [response, setResponse] = useState(null)

  const downloadAssets = () => {
    if (running) {
      promiseMap(assets, requestAsset(endpointUrl))
        .then(setResponse)
        .catch(setError)
    }
  }

  useEffect(downloadAssets, [running])

  return { response, err }
}

export default useAssetDownloader
