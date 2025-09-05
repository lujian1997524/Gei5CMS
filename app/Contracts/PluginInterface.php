<?php

namespace App\Contracts;

interface PluginInterface
{
    public function getName(): string;
    
    public function getSlug(): string;
    
    public function getVersion(): string;
    
    public function getDescription(): string;
    
    public function getAuthor(): string;
    
    public function getDependencies(): array;
    
    public function getRequiredHooks(): array;
    
    public function getProvidedHooks(): array;
    
    public function getServiceType(): string;
    
    public function getConfigSchema(): array;
    
    public function install(): bool;
    
    public function uninstall(): bool;
    
    public function activate(): bool;
    
    public function deactivate(): bool;
    
    public function update(string $fromVersion): bool;
    
    public function boot(): void;
    
    public function isCompatible(): bool;
    
    public function getPermissions(): array;
}