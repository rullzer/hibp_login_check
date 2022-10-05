<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\TwoFactorReminder\Service;

use OC\Authentication\TwoFactorAuth\ProviderLoader;
use OCA\TwoFactorReminder\AppInfo\Application;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IUser;
use OCP\Notification\IManager;

class Check2FA {

	/** @var IRegistry */
	private $registry;

	/** @var IManager */
	private $notificationManager;

	/** @var ProviderLoader */
	private $providerLoader;

	public function __construct(IRegistry $registry, IManager $notificationManager, ProviderLoader $providerLoader) {
		$this->registry = $registry;
		$this->notificationManager = $notificationManager;
		$this->providerLoader = $providerLoader;
	}

	public function processUser(IUser $user) {
		$states = $this->registry->getProviderStates($user);

		$enabled = array_reduce(array_values($states), function(bool $carry, bool $item) {
			return $carry || $item;
		}, false);

		if ($enabled) {
			//Nothing to do
			return;
		}

		$possibleProviders = $this->providerLoader->getProviders($user);

		$possibleProviders = array_filter($possibleProviders, function(IProvider $provider) {
			return $provider->getId() !== 'backup_codes';
		});

		// No provider except the backup codes continue
		if (count($possibleProviders) === 0) {
			// Nothing to do
			return;
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp(Application::APP_NAME)
			->setUser($user->getUID())
			->setDateTime(new \DateTime())
			->setObject('2fa', 'setup')
			->setSubject('2FASetup');
		$this->notificationManager->notify($notification);
	}
}
