<?php

namespace Database\Factories;

use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PlatformSetting>
 */
class PlatformSettingFactory extends Factory
{
    protected $model = PlatformSetting::class;

    public function definition(): array
    {
        $key = 'setting.'.Str::slug(fake()->unique()->words(2, true), '.');

        return [
            'key' => $key,
            'value' => ['value' => fake()->word()],
        ];
    }
}
