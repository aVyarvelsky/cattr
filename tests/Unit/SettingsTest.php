<?php

namespace Tests\Unit;

use App\Services\SettingsProviderService;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    private SettingsProviderService $service;

    private string $settingKey;
    private string $settingValue;

    final protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(SettingsProviderService::class);

        $this->settingKey = fake()->word();
        $this->settingValue = fake()->word();

        $this->service->set($this->settingKey, $this->settingValue);
    }

    final public function test_get_setting(): void
    {
        $this->assertEquals($this->settingValue, $this->service->get($this->settingKey));
    }

    final public function test_get_all_settings(): void
    {
        $this->assertEquals([
            $this->settingKey => $this->settingValue
        ], $this->service->all());
    }

    final public function test_set_one_setting(): void
    {
        $key = fake()->word();
        $value = fake()->word();

        $this->assertNull($this->service->get($key));

        $this->service->set($key, $value);

        $this->assertEquals($value, $this->service->get($key));
    }

    final public function test_set_multiple_settings(): void
    {
        $data = [fake()->word() => fake()->word(), fake()->word() => fake()->word()];

        $this->service->set($data);

        $this->assertEquals(array_merge(
            $data,
            [
                $this->settingKey => $this->settingValue,
            ]
        ), $this->service->all());
    }
}
