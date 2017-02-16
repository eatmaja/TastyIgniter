<?php
/**
 * TastyIgniter
 *
 * An open source online ordering, reservation and management system for restaurants.
 *
 * @package       TastyIgniter
 * @author        SamPoyigi
 * @copyright (c) 2013 - 2016. TastyIgniter
 * @link          http://tastyigniter.com
 * @license       http://opensource.org/licenses/GPL-3.0 The GNU GENERAL PUBLIC LICENSE
 * @since         File available since Release 1.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hub Manager Class
 *
 * @category       Libraries
 * @package        TastyIgniter\Libraries\Hub_manager.php
 * @link           http://docs.tastyigniter.com
 */
class Hub_manager
{

	protected $carteKey;
	protected $cachePrefix;
	protected $cacheLife;
	protected $downloadsPath;

	public function __construct()
	{
		$this->CI =& get_instance();

		$this->CI->load->driver('cache');
		$this->cachePrefix = 'hub_manager_';
		$this->downloadsPath = storage_path('temp/hub');

		$this->setSecurity();
	}

	public function setCacheLife($period = null)
	{
		$this->cacheLife = $period * 60 * 60;

		return $this;
	}

	public function listItems($filter = [])
	{
		$cacheFile = $this->cachePrefix . 'items_' . md5(serialize($filter));
		if (!$items = $this->CI->cache->file->get($cacheFile)) {
			$items = $this->requestRemoteData('items', $filter);

			if (!empty($items) AND is_array($items))
				$this->CI->cache->file->save($cacheFile, $items, $this->cacheLife);
		}

		return $items;
	}

	public function getDetail($itemType, $itemName)
	{
		return $this->requestRemoteData("{$itemType}/detail", ['name' => serialize($itemName)]);
	}

	public function getDetails($itemType, $itemNames = [])
	{
		return $this->requestRemoteData("{$itemType}/details", ['names' => serialize($itemNames)]);
	}

	public function requestUpdateList($itemNames, $force = FALSE)
	{
		$itemNames = serialize($itemNames);

		$cacheFile = $this->cachePrefix . 'updates_' . md5($itemNames);

		if ($force OR !$response = $this->CI->cache->file->get($cacheFile)) {
			$response = $this->requestRemoteData('core/check', ['names' => $itemNames]);

			if (is_array($response)) {
				$response['check_time'] = time();
				$this->CI->cache->file->save($cacheFile, $response, $this->cacheLife);
			}
		}

		if (is_array($response))
			$response = $this->buildMetaArray($response);

		return $response;
	}

	public function applyInstallOrUpdate($itemNames = [])
	{
		$response = $this->requestRemoteData('core/apply', ['names' => serialize($itemNames)]);

		if (is_array($response))
			$response = $this->buildMetaArray($response);

		return $response;
	}

	public function downloadFile($fileType, $filePath, $fileHash, $params = [])
	{
		return $this->requestRemoteFile("{$fileType}/download", [
			'name' => serialize($params)
		], $filePath, $fileHash);
	}

	public function buildMetaArray($response)
	{
		if (isset($response['type']))
			$response = ['items' => [$response]];

		if (isset($response['items'])) {
			$extensions = [];
			foreach ($response['items'] as $item) {
				if ($item['type'] == 'extension' AND
					(!Modules::find_extension($item['type']) OR Modules::is_disabled($item['code']))
				) {
					if (isset($item['tags']))
						arsort($item['tags']);

					$extensions[$item['code']] = $item;
				}
			}

			unset($response['items']);
			$response['extensions'] = $extensions;
		}

		return $response;
	}

	public function setSecurity()
	{
		$this->carteKey = is_null($carteKey = $this->CI->config->item('carte_key')) ? md5('NULL') : $carteKey;
	}

	/**
	 * @return Installer
	 */
	public function getInstaller()
	{
		return $this->CI->installer;
	}

	protected function requestRemoteData($url, $params = [])
	{
		$response = get_remote_data(TI_ENDPOINT . $url, $this->buildPostData($params));
		$response = @json_decode($response, TRUE);

		if (is_null($response)) {
			log_message('error', 'Server error, try again');

			return 'Server error, try again';
		}

		if (isset($response['status']) AND isset($response['message'])) {
			log_message('error', isset($response['message']) ? $response['message'] : '');

			return isset($response['message']) ? $response['message'] : '';
		}

		return $response;
	}

	protected function requestRemoteFile($url, $params = [], $filePath, $fileHash)
	{
		if (!is_dir($fileDir = dirname($filePath)))
			throw new Exception("Downloading failed, download path not found.");

		$fileName = basename($fileDir);
		$fileStream = fopen($filePath, 'w+');

		get_remote_data(TI_ENDPOINT . $url, $this->buildPostData($params, ['FILE' => $fileStream]));

		if (file_exists($filePath)) {
			$fileSha = sha1_file($filePath);
			if ($fileHash != $fileSha) {
				log_message('error', "{$fileName} file hash mismatch: {$fileHash} (expected) vs {$fileSha} (actual)");
				@unlink($filePath);
				throw new Exception("Downloading {$fileName} failed, check error log.");
			}
		}

		return TRUE;
	}

	protected function buildPostData($params = [], $options = [])
	{
		$options['USERAGENT'] = $this->CI->agent->agent_string();
		$options['REFERER'] = page_url();
		$options['AUTOREFERER'] = TRUE;
		$options['FOLLOWLOCATION'] = 1;

		if (empty($options['TIMEOUT']))
			$options['TIMEOUT'] = 3600;

		$info = $this->getInstaller()->getSysInfo();
		$params['version'] = $info['ver'];
		$params['server'] = base64_encode(serialize($info));

		if (isset($params['filter']))
			$params['filter'] = $params['filter'];

		if (!empty($params))
			$options['POSTFIELDS'] = $params;

		if ($this->carteKey)
			$options['HTTPHEADER'][] = TI_CARTE_AUTH . ": {$this->carteKey}";

		$options['HTTPHEADER'][] = TI_SIGN_REQUEST . ": " . $this->createSignature($params, $this->carteKey);

		return $options;
	}

	protected function createSignature($postData, $carteKey)
	{
		return base64_encode(hash_hmac('sha256', serialize($postData), $carteKey));
	}
}