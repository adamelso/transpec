<?php

namespace Transpec;

class Manifest
{
    private array $publicProperties = [];
    private array $subjectInputs = [];
    private array $local = [];
    private array $global = [];

    public function addPublicPropertyCollaborator(string $collaborator): void
    {
        $this->publicProperties[$collaborator] = true;
    }

    public function isPublicPropertyCollaborator(string $collaborator): bool
    {
        return isset($this->publicProperties[$collaborator]);
    }

    public function addSubjectInputCollaborator(string $collaborator): void
    {
        $this->subjectInputs[$collaborator] = true;
    }

    public function isSubjectInputCollaborator(string $collaborator): bool
    {
        return isset($this->subjectInputs[$collaborator]);
    }

    public function addLocalCollaborator(string $collaborator): void
    {
        $this->local[$collaborator] = true;
    }

    public function isLocalCollaborator(string $collaborator): bool
    {
        return isset($this->local[$collaborator]);
    }

    public function addGlobalCollaborator(string $collaborator): void
    {
        $this->global[$collaborator] = true;
    }

    public function isGlobalCollaborator(string $collaborator): bool
    {
        return isset($this->global[$collaborator]);
    }

    public function isCollaborator(string $collaborator): bool
    {
        if ($this->isLocalCollaborator($collaborator)) {
            return true;
        }
        if ($this->isGlobalCollaborator($collaborator)) {
            return true;
        }
        if ($this->isSubjectInputCollaborator($collaborator)) {
            return true;
        }
        if ($this->isPublicPropertyCollaborator($collaborator)) {
            return true;
        }

        return false;
    }
}
