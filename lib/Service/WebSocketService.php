<?php

declare(strict_types=1);

namespace OCA\MindMap\Service;

use Exception;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class WebSocketService {
    /** @var string */
    private const APP_NAME = 'mindmap';
    
    /** @var IConfig */
    private $config;
    
    /** @var IUserSession */
    private $userSession;
    
    /** @var IURLGenerator */
    private $urlGenerator;
    
    /** @var LoggerInterface */
    private $logger;
    
    public function __construct(
        IConfig $config,
        IUserSession $userSession,
        IURLGenerator $urlGenerator,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->userSession = $userSession;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }
    
    /**
     * Generate a token for WebSocket authentication
     *
     * @param string $mindMapName
     * @return array<string, mixed>
     */
    public function generateSocketInfo(string $mindMapName): array {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new Exception('User not logged in');
            }
            
            $userId = $user->getUID();
            $displayName = $user->getDisplayName();
            
            // Generate a token with userId, mindMapName, and timestamp
            $timestamp = time();
            $token = hash('sha256', $userId . $mindMapName . $timestamp . $this->config->getSystemValue('secret'));
            
            // WebSocket URL - For production use your own WebSocket server
            // This is just a placeholder and should be configured in the admin settings
            $wsUrl = $this->config->getAppValue(
                self::APP_NAME, 
                'websocket_url', 
                'wss://' . $_SERVER['HTTP_HOST'] . '/mindmap-ws'
            );
            
            return [
                'token' => $token,
                'userId' => $userId,
                'displayName' => $displayName,
                'mindMapName' => $mindMapName,
                'timestamp' => $timestamp,
                'wsUrl' => $wsUrl
            ];
        } catch (Exception $e) {
            $this->logger->error('Error generating socket info: ' . $e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }
} 