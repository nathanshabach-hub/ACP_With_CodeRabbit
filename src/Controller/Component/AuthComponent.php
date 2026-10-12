<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;

class AuthComponent extends Component
{
	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'sessionKey' => 'Auth.User',
	];

	public function allow($actions = []): void
	{
	}

	public function deny($actions = []): void
	{
	}

	public function loggedIn(): bool
	{
		return (bool)$this->user();
	}

	public function user(?string $field = null)
	{
		$request = $this->getController()->getRequest();
		$user = (array)$request->getSession()->read($this->getConfig('sessionKey'));

		if ($field === null || $field === '') {
			return $user ?: null;
		}

		return $user[$field] ?? null;
	}

	public function setUser(array $user): void
	{
		$request = $this->getController()->getRequest();
		$request->getSession()->write($this->getConfig('sessionKey'), $user);
	}

	public function logout(): void
	{
		$request = $this->getController()->getRequest();
		$request->getSession()->delete($this->getConfig('sessionKey'));
	}
}

