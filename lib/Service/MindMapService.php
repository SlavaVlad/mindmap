<?php

declare(strict_types=1);

namespace OCA\MindMap\Service;

use Exception;
use OCA\MindMap\Model\MindMap;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use OCP\Files\FileInfo;

class MindMapService {
    /** @var string */
    private const APP_NAME = 'mindmap';
    
    /** @var string */
    private const MINDMAP_FOLDER = 'mindmaps';

    /** @var IUserSession */
    private $userSession;

    /** @var IRootFolder */
    private $rootFolder;
    
    /** @var IConfig */
    private $config;
    
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        IUserSession $userSession,
        IRootFolder $rootFolder,
        IConfig $config,
        LoggerInterface $logger
    ) {
        $this->userSession = $userSession;
        $this->rootFolder = $rootFolder;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Get a mind map by filename
     *
     * @param string $name
     * @return MindMap|null
     */
    public function getMindMap(string $name): ?MindMap {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new Exception('User not logged in');
            }
            
            $userId = $user->getUID();
            $userFolder = $this->rootFolder->getUserFolder($userId);
            
            // Create mindmap folder if it doesn't exist
            if (!$userFolder->nodeExists(self::MINDMAP_FOLDER)) {
                $userFolder->newFolder(self::MINDMAP_FOLDER);
            }
            
            $mindmapFolder = $userFolder->get(self::MINDMAP_FOLDER);
            $fileName = $name . '.json';
            
            if (!$mindmapFolder->nodeExists($fileName)) {
                return null;
            }
            
            $file = $mindmapFolder->get($fileName);
            $content = $file->getContent();
            
            $data = json_decode($content, true);
            return new MindMap(
                $data['name'] ?? $name,
                $data['content'] ?? '{}',
                $userId,
                $file->getPath(),
                null,
                isset($data['createdAt']) ? new \DateTime($data['createdAt']) : null,
                isset($data['updatedAt']) ? new \DateTime($data['updatedAt']) : null
            );
        } catch (Exception $e) {
            $this->logger->error('Error getting mind map: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save a mind map
     *
     * @param string $name
     * @param string $content
     * @return MindMap
     * @throws Exception
     */
    public function saveMindMap(string $name, string $content): MindMap {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new Exception('User not logged in');
            }
            
            $userId = $user->getUID();
            $userFolder = $this->rootFolder->getUserFolder($userId);
            
            // Create mindmap folder if it doesn't exist
            if (!$userFolder->nodeExists(self::MINDMAP_FOLDER)) {
                $userFolder->newFolder(self::MINDMAP_FOLDER);
            }
            
            $mindmapFolder = $userFolder->get(self::MINDMAP_FOLDER);
            $fileName = $name . '.json';
            
            $now = new \DateTime();
            $mindMap = new MindMap($name, $content, $userId, null, null, null, $now);
            
            // If file exists, update it
            if ($mindmapFolder->nodeExists($fileName)) {
                $file = $mindmapFolder->get($fileName);
                $oldContent = $file->getContent();
                $oldData = json_decode($oldContent, true);
                
                // Keep creation date if it exists
                if (isset($oldData['createdAt'])) {
                    $createdAt = new \DateTime($oldData['createdAt']);
                    $mindMap = new MindMap($name, $content, $userId, null, null, $createdAt, $now);
                }
            }
            
            $jsonContent = json_encode($mindMap);
            
            // Create or update file
            if ($mindmapFolder->nodeExists($fileName)) {
                $file = $mindmapFolder->get($fileName);
                $file->putContent($jsonContent);
            } else {
                $file = $mindmapFolder->newFile($fileName, $jsonContent);
            }
            
            $mindMap->setFilePath($file->getPath());
            return $mindMap;
        } catch (Exception $e) {
            $this->logger->error('Error saving mind map: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all mind maps
     *
     * @return MindMap[]
     */
    public function getAllMindMaps(): array {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new Exception('User not logged in');
            }
            
            $userId = $user->getUID();
            $userFolder = $this->rootFolder->getUserFolder($userId);
            
            // Create mindmap folder if it doesn't exist
            if (!$userFolder->nodeExists(self::MINDMAP_FOLDER)) {
                $userFolder->newFolder(self::MINDMAP_FOLDER);
                return [];
            }
            
            $mindmapFolder = $userFolder->get(self::MINDMAP_FOLDER);
            $nodes = $mindmapFolder->getDirectoryListing();
            
            $mindMaps = [];
            foreach ($nodes as $node) {
                if ($node->getType() === FileInfo::TYPE_FILE && $node->getExtension() === 'json') {
                    $content = $node->getContent();
                    $data = json_decode($content, true);
                    $name = pathinfo($node->getName(), PATHINFO_FILENAME);
                    
                    $mindMap = new MindMap(
                        $name,
                        $data['content'] ?? '{}',
                        $userId,
                        $node->getPath(),
                        null,
                        isset($data['createdAt']) ? new \DateTime($data['createdAt']) : null,
                        isset($data['updatedAt']) ? new \DateTime($data['updatedAt']) : null
                    );
                    
                    $mindMaps[] = $mindMap;
                }
            }
            
            return $mindMaps;
        } catch (Exception $e) {
            $this->logger->error('Error getting all mind maps: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a mind map
     *
     * @param string $name
     * @return bool
     */
    public function deleteMindMap(string $name): bool {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new Exception('User not logged in');
            }
            
            $userId = $user->getUID();
            $userFolder = $this->rootFolder->getUserFolder($userId);
            
            if (!$userFolder->nodeExists(self::MINDMAP_FOLDER)) {
                return false;
            }
            
            $mindmapFolder = $userFolder->get(self::MINDMAP_FOLDER);
            $fileName = $name . '.json';
            
            if (!$mindmapFolder->nodeExists($fileName)) {
                return false;
            }
            
            $file = $mindmapFolder->get($fileName);
            $file->delete();
            
            return true;
        } catch (Exception $e) {
            $this->logger->error('Error deleting mind map: ' . $e->getMessage());
            return false;
        }
    }
} 