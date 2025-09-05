<?php

namespace App\Contracts;

interface ThemeInterface
{
    public function getName(): string;
    
    public function getSlug(): string;
    
    public function getVersion(): string;
    
    public function getDescription(): string;
    
    public function getAuthor(): string;
    
    public function getApplicationType(): string;
    
    public function getTableSchema(): array;
    
    public function getRequiredPlugins(): array;
    
    public function getCompatiblePlugins(): array;
    
    public function getDefaultSettings(): array;
    
    public function getCustomizerConfig(): array;
    
    public function install(): bool;
    
    public function uninstall(): bool;
    
    public function activate(): bool;
    
    public function deactivate(): bool;
    
    public function update(string $fromVersion): bool;
    
    public function boot(): void;
    
    public function isCompatible(): bool;
    
    public function createBusinessTables(): bool;
    
    public function dropBusinessTables(): bool;
    
    public function getRoutes(): array;
    
    public function getMiddleware(): array;
    
    public function getViewPaths(): array;
    
    public function getAssetPaths(): array;
}