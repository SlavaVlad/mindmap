<?php

declare(strict_types=1);

namespace OCA\MindMap\AppInfo;

use OCA\MindMap\Service\MindMapService;
use OCA\MindMap\Service\WebSocketService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Psr\Container\ContainerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'mindmap';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		// Register services for Dependency Injection
		$context->registerService(MindMapService::class, function (ContainerInterface $c) {
			return new MindMapService(
				$c->get(\OCP\IUserSession::class),
				$c->get(\OCP\Files\IRootFolder::class),
				$c->get(\OCP\IConfig::class),
				$c->get(\Psr\Log\LoggerInterface::class)
			);
		});

		$context->registerService(WebSocketService::class, function (ContainerInterface $c) {
			return new WebSocketService(
				$c->get(\OCP\IConfig::class),
				$c->get(\OCP\IUserSession::class),
				$c->get(\OCP\IURLGenerator::class),
				$c->get(\Psr\Log\LoggerInterface::class)
			);
		});
	}

	public function boot(IBootContext $context): void {
		// Boot code here (runs after registration)
	}
}
