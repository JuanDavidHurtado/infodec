<?php

namespace Tests\Feature;

use Tests\TestCase;

final class CountryTest extends TestCase
{
    private const ENDPOINT = '/api/country';

    public function test_endpoint_ok_and_expected_structure(): void
    {
        $res = $this->getJson(self::ENDPOINT);

        $res->assertOk()
            ->assertJsonPath('statusCode', 200)
            ->assertJsonStructure([
                'statusCode',
                'message',
                'countries' => [
                    '*' => [
                        'idCountry',
                        'nameSpa',
                        'nameGer',
                        'cities' => [
                            '*' => ['idCity', 'nameSpa', 'nameGer']
                        ]
                    ]
                ]
            ]);

        $json = $res->json();
        $this->assertIsArray($json['countries'] ?? null, '`countries` debe ser un array');
    }
}