<?php

namespace App\Services\Select;

use App\Models\Client\Client;

class ClientSelectService
{
    public function getAllClients()
    {
        return Client::all(['id as value', 'ragione_sociale as label']);
    }
}
