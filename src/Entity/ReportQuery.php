<?php

namespace Kematjaya\ReportBuilderBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Kematjaya\ReportBuilderBundle\Repository\ReportQueryRepository;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReportQueryRepository::class)]
#[ORM\Table(name: 'report_query')]
#[ORM\HasLifecycleCallbacks]
class ReportQuery
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $sqlQuery = null;

    /**
     * Params dinamis dalam format JSON:
     * [{"name":"tahun","label":"Tahun Anggaran","type":"integer","default":2024}]
     * Tipe yang didukung: integer, string, date
     * Digunakan dengan placeholder {{nama_param}} dalam SQL query
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $queryParams = null;

    /**
     * Tipe output: table, bar, line, pie, scatter
     */
    #[ORM\Column(length: 50, options: ['default' => 'table'])]
    private string $outputType = 'table';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $roles = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $chartXColumn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $chartYColumn = null;

    #[ORM\Column(length: 100, options: ['default' => 'fa fa-bar-chart'])]
    private string $icon = 'fa fa-bar-chart';

    #[ORM\Column(options: ['default' => true])]
    private bool $isEnabled = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSqlQuery(): ?string
    {
        return $this->sqlQuery;
    }

    public function setSqlQuery(string $sqlQuery): static
    {
        $this->sqlQuery = $sqlQuery;
        return $this;
    }

    public function getQueryParams(): ?array
    {
        return $this->queryParams;
    }

    public function setQueryParams(?array $queryParams): static
    {
        $this->queryParams = $queryParams;
        return $this;
    }

    public function getOutputType(): string
    {
        return $this->outputType;
    }

    public function setOutputType(string $outputType): static
    {
        $this->outputType = $outputType;
        return $this;
    }

    public function getChartXColumn(): ?string
    {
        return $this->chartXColumn;
    }

    public function setChartXColumn(?string $chartXColumn): static
    {
        $this->chartXColumn = $chartXColumn;
        return $this;
    }

    public function getChartYColumn(): ?string
    {
        return $this->chartYColumn;
    }

    public function setChartYColumn(?string $chartYColumn): static
    {
        $this->chartYColumn = $chartYColumn;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): static
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }
}
