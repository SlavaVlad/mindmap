<?php

declare(strict_types=1);

namespace OCA\MindMap\Controller;

use OCA\MindMap\AppInfo\Application;
use OCA\MindMap\Service\MindMapService;
use OCA\MindMap\Service\WebSocketService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use Psr\Log\LoggerInterface;

/**
 * API Controller for MindMap operations
 */
class ApiController extends OCSController {
	/** @var MindMapService */
	private $mindMapService;
	
	/** @var WebSocketService */
	private $webSocketService;
	
	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		string $appName,
		MindMapService $mindMapService,
		WebSocketService $webSocketService,
		LoggerInterface $logger
	) {
		parent::__construct($appName, null);
		$this->mindMapService = $mindMapService;
		$this->webSocketService = $webSocketService;
		$this->logger = $logger;
	}

	/**
	 * Get all mind maps
	 *
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/mindmaps')]
	public function getMindMaps(): DataResponse {
		try {
			$mindMaps = $this->mindMapService->getAllMindMaps();
			return new DataResponse($mindMaps);
		} catch (\Exception $e) {
			$this->logger->error('Error getting mind maps: ' . $e->getMessage());
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get a mind map by name
	 *
	 * @param string $name
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/mindmaps/{name}')]
	public function getMindMap(string $name): DataResponse {
		try {
			$mindMap = $this->mindMapService->getMindMap($name);
			if (!$mindMap) {
				return new DataResponse(['error' => 'Mind map not found'], Http::STATUS_NOT_FOUND);
			}
			return new DataResponse($mindMap);
		} catch (\Exception $e) {
			$this->logger->error('Error getting mind map: ' . $e->getMessage());
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Create or update a mind map
	 *
	 * @param string $name
	 * @param string $content
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/mindmaps/{name}')]
	public function saveMindMap(string $name, string $content): DataResponse {
		try {
			$mindMap = $this->mindMapService->saveMindMap($name, $content);
			return new DataResponse($mindMap);
		} catch (\Exception $e) {
			$this->logger->error('Error saving mind map: ' . $e->getMessage());
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Delete a mind map
	 *
	 * @param string $name
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/mindmaps/{name}')]
	public function deleteMindMap(string $name): DataResponse {
		try {
			$success = $this->mindMapService->deleteMindMap($name);
			if (!$success) {
				return new DataResponse(['error' => 'Mind map not found'], Http::STATUS_NOT_FOUND);
			}
			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
			$this->logger->error('Error deleting mind map: ' . $e->getMessage());
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get WebSocket connection info
	 *
	 * @param string $name
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/mindmaps/{name}/socket')]
	public function getSocketInfo(string $name): DataResponse {
		try {
			$socketInfo = $this->webSocketService->generateSocketInfo($name);
			if (isset($socketInfo['error'])) {
				return new DataResponse(['error' => $socketInfo['error']], Http::STATUS_INTERNAL_SERVER_ERROR);
			}
			return new DataResponse($socketInfo);
		} catch (\Exception $e) {
			$this->logger->error('Error getting socket info: ' . $e->getMessage());
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
