<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Dav;

use OCA\EcloudAccounts\AppInfo\Application;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;

use Sabre\HTTP\ResponseInterface;

class CheckPlugin extends ServerPlugin {
	/** @var Server */
	protected $server;

	/** @var array */
	protected $blockedUserAgents = [
		'/^Mozilla\/5\.0 \(.*\) mirall\/.*$/', // Nextcloud desktop clients
		'/^Mozilla\/5\.0 \(Android\) Nextcloud\-android\/.*$/', // Nextcloud Android clients
		'/^Mozilla\/5\.0 \(iOS\) Nextcloud\-iOS\/.*$/', // Nextcloud iOS clients
	];

	/**
	 * Initializes the plugin and registers event handlers
	 *
	 * @param Server $server
	 * @return void
	 */
	public function initialize(Server $server): void {
		$this->server = $server;

		$server->on('method:PROPFIND', [$this, 'blockNextcloudClients']);
		$server->on('method:PROPPATCH', [$this, 'blockNextcloudClients']);
		$server->on('method:GET', [$this, 'blockNextcloudClients']);
		$server->on('method:POST', [$this, 'blockNextcloudClients']);
		$server->on('method:PUT', [$this, 'blockNextcloudClients']);
		$server->on('method:DELETE', [$this, 'blockNextcloudClients']);
		$server->on('method:MKCOL', [$this, 'blockNextcloudClients']);
		$server->on('method:MOVE', [$this, 'blockNextcloudClients']);
		$server->on('method:COPY', [$this, 'blockNextcloudClients']);
		$server->on('method:REPORT', [$this, 'blockNextcloudClients']);
	}

	/**
	 * Block requests from Nextcloud clients based on User-Agent
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return bool
	 * @throws Forbidden
	 */
	public function blockNextcloudClients(RequestInterface $request, ResponseInterface $response): bool {
		$userAgent = $request->getHeader('User-Agent');

		if (!$userAgent) {
			throw new Forbidden('Missing User-Agent header.');
		}

		// Check if the User-Agent matches any of the blocked patterns
		foreach ($this->blockedUserAgents as $pattern) {
			if (preg_match($pattern, $userAgent)) {
				throw new Forbidden('Access from Nextcloud clients is blocked.');
			}
		}

		return true; // Allow other clients
	}

	/**
	 * Returns a plugin name.
	 *
	 * @return string
	 */
	public function getPluginName(): string {
		return Application::APP_ID;
	}

	/**
	 * Returns a bunch of meta-data about the plugin.
	 *
	 * @return array
	 */
	public function getPluginInfo(): array {
		return [
			'name' => $this->getPluginName(),
			'description' => 'Block requests from Nextcloud clients based on User-Agent headers.',
		];
	}
}
