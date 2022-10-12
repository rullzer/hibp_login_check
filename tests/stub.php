<?php

namespace OC\Authentication\TwoFactorAuth {
	use OCP\Authentication\TwoFactorAuth\IProvider;
	use OCP\IUser;

	class ProviderLoader {
		/**
		 * @return IProvider[]
		 */
		public function getProviders(IUser $user): array {
		}
	}
}
