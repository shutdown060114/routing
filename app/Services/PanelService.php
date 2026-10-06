<?php
namespace App\Services;

use App\Models\Panel;

final class PanelService
{
    public function __construct(private Panel $panels) {}

    public function definition(string $slug): ?array
    {
        return $this->panels->findBySlug($slug);
    }

    public function rows(array $panel): array
    {
        return $this->panels->rows($panel);
    }

    public function create(array $panel, array $input): int
    {
        return $this->panels->insert($panel, $input);
    }
}
