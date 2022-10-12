<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022, Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\HibpLoginCheck\Listener;

use OCA\HibpLoginCheck\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Http\Client\IClientService;
use OCP\Notification\IManager;
use OCP\User\Events\PostLoginEvent;
use Psr\Log\LoggerInterface;

class PostLoginListener implements IEventListener {
	private LoggerInterface $logger;
	private IClientService $clientService;
	private IManager $notificationManager;

	public function __construct(LoggerInterface $logger, IClientService $clientService, IManager $notificationManager) {
		$this->logger = $logger;
		$this->clientService = $clientService;
		$this->notificationManager = $notificationManager;
	}

	public function handle(Event $event): void {
		if (!($event instanceof PostLoginEvent)) {
			{
				return;
			}
		}

		$client = $this->clientService->newClient();

		$hash = sha1($event->getPassword());
		$range = substr($hash, 0, 5);
		$needle = strtoupper(substr($hash, 5));

		try {
			$response = $client->get(
				'https://api.pwnedpasswords.com/range/' . $range,
				[
					'timeout' => 1, // 1 second is plenty
					'headers' => [
						'Add-Padding' => 'true'
					]
				]
			);
		} catch (\Exception $e) {
			$this->logger->info("Could not contat HIBP", ["exception" => $e]);
			return;
		}

		$result = $response->getBody();
		$result = preg_replace('/^([0-9A-Z]+:0)$/m', '', $result);

		if (strpos($result, $needle) == false) {
			return;
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp(Application::APP_NAME)
			->setUser($event->getUser()->getUID())
			->setDateTime(new \DateTime())
			->setObject('hibplogincheck', 'login')
			->setSubject('hibplogincheck');
		$this->notificationManager->notify($notification);
	}
}
