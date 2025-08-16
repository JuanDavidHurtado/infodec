<?php

namespace Tests\Feature;

use Tests\TestCase;

final class HistoryTest extends TestCase
{
    private const ENDPOINT = '/api/history';

    public function test_history_endpoint_ok_and_expected_structure(): void
    {
        $res = $this->getJson(self::ENDPOINT);

        $res->assertOk()
            ->assertJsonPath('statusCode', 200)
            ->assertJsonStructure([
                'statusCode',
                'message',
                'history' => [
                    '*' => [
                        'when',
                        'country',
                        'city',
                        'budget_cop',
                        'converted',
                        'converted_fmt',
                        'currency',
                        'symbol',
                        'rate',
                        'temp_c',
                        'weather_desc',
                    ]
                ]
            ]);

        $this->assertIsArray($res->json('history'));
    }
}
