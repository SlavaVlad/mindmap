<?php

declare(strict_types=1);

namespace OCA\MindMap\Model;

use JsonSerializable;

/**
 * Class MindMap
 * 
 * Represents a mind map entity with its content and metadata.
 */
class MindMap implements JsonSerializable {
    /** @var int */
    private $id;
    
    /** @var string */
    private $name;
    
    /** @var string */
    private $content;
    
    /** @var string */
    private $userId;
    
    /** @var string */
    private $filePath;
    
    /** @var \DateTime */
    private $createdAt;
    
    /** @var \DateTime */
    private $updatedAt;

    /**
     * MindMap constructor.
     * 
     * @param string $name
     * @param string $content
     * @param string $userId
     * @param string|null $filePath
     * @param int|null $id
     * @param \DateTime|null $createdAt
     * @param \DateTime|null $updatedAt
     */
    public function __construct(
        string $name,
        string $content,
        string $userId,
        ?string $filePath = null,
        ?int $id = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->id = $id === null ? 0 : $id;
        $this->name = $name;
        $this->content = $content;
        $this->userId = $userId;
        $this->filePath = $filePath ?? '';
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }

    public function getId(): int {
        return $this->id;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getContent(): string {
        return $this->content;
    }

    public function setContent(string $content): void {
        $this->content = $content;
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function getFilePath(): string {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): void {
        $this->filePath = $filePath;
    }

    public function getCreatedAt(): \DateTime {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'content' => $this->content,
            'userId' => $this->userId,
            'filePath' => $this->filePath,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c')
        ];
    }
} 