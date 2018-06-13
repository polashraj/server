<?php
declare (strict_types = 1);
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Provisioning_API\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

class UserConfigController extends OCSController {

	/** @var IConfig */
	protected $config;

	/** @var IAppConfig */
	protected $appConfig;

	/** @var IUserSession */
	private $userSession;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param IAppConfig $appConfig
	 * @param IUserSession $userSession
	 */
	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IAppConfig $appConfig,
								IUserSession $userSession) {
		parent::__construct($appName, $request);
		$this->config      = $config;
		$this->appConfig   = $appConfig;
		$this->userSession = $userSession;
	}

	/**
	 * @param string $app
	 * @param string $key
	 * @param string $defaultValue
	 * @return DataResponse
	 */
	public function getValue(string $app, string $key, string $defaultValue = ''): DataResponse {
		try {
			$this->verifyAppId($app);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['data' => ['message' => $e->getMessage()]], Http::STATUS_FORBIDDEN);
		}

		return new DataResponse([
			'data' => $this->config->getUserValue($this->userSession->getUser()->getUID(), $app, $key, $defaultValue)
		]);
	}

	/**
	 * @PasswordConfirmationRequired
	 * @param string $app
	 * @param string $key
	 * @param string $value
	 * @return DataResponse
	 */
	public function setValue(string $app, string $key, string $value): DataResponse {
		try {
			$this->verifyAppId($app);
			$this->verifyConfigKey($app, $key);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['data' => ['message' => $e->getMessage()]], Http::STATUS_FORBIDDEN);
		}

		$this->config->setUserValue($this->userSession->getUser()->getUID(), $app, $key, $value);

		return new DataResponse();
	}

	/**
	 * @param string $app
	 * @param string $key
	 * @return DataResponse
	 */
	public function deleteKey(string $app, string $key): DataResponse {
		try {
			$this->verifyAppId($app);
			$this->verifyConfigKey($app, $key);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(['data' => ['message' => $e->getMessage()]], Http::STATUS_FORBIDDEN);
		}

		$this->config->deleteUserValue($this->userSession->getUser()->getUID(), $app, $key);

		return new DataResponse();
	}

	/**
	 * @param string $app
	 * @throws \InvalidArgumentException
	 */
	protected function verifyAppId(string $app) {
		if (\OC_App::cleanAppId($app) !== $app) {
			throw new \InvalidArgumentException('Invalid app id given');
		}
	}

	/**
	 * @param string $app
	 * @param string $key
	 * @throws \InvalidArgumentException
	 */
	protected function verifyConfigKey(string $app, string $key) {
		if (in_array($key, ['installed_version', 'enabled', 'types'])) {
			throw new \InvalidArgumentException('The given key can not be set');
		}

		if ($app === 'core') {
			throw new \InvalidArgumentException('The given key can not be set');
		}
	}
}
