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

namespace OCA\HibpLoginCheck\Notification;

use OCA\HibpLoginCheck\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class HIBPNotification implements INotifier {
	/** @var IFactory */
	private $l10nFactory;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(IFactory $l10nFactory, IURLGenerator $urlGenerator) {
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
	}

	public function getID(): string {
		return Application::APP_NAME;
	}

	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_NAME)->t('Two factor reminder');
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_NAME) {
			throw new \InvalidArgumentException();
		}

		if ($notification->getSubject() !== 'hibplogincheck') {
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get(Application::APP_NAME, $languageCode);

		$notification->setParsedSubject($l->t('Please change your password!'));
		$notification->setRichSubject($l->t('Please change your password!'));
		$notification->setParsedMessage($l->t('Your password appears in a known breach. Please change it.'));
		$notification->setRichMessage($l->t('Your password appears in a known breach. Please change it.'));
		$notification->setLink(
			$this->urlGenerator->linkToRouteAbsolute('settings.PersonalSettings.index', ['section' => 'security'])
		);
		$notification->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.svg')));

		return $notification;
	}
}
