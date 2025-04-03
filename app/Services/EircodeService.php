<?
namespace App\Services;

use Illuminate\Support\Facades\File;

class EircodeService
{
    public function getEircodeArea($eircode)
    {
        if (empty($eircode) || strlen($eircode) < 3) {
            throw new \InvalidArgumentException('Invalid Eircode');
        }

        $routingKey = strtoupper(substr($eircode, 0, 3));
        $filePath = __DIR__ . '/storage/Eircodes/ireland-eircode-routing-keys.json';

        if (!file_exists($filePath)) {
            throw new \RuntimeException('Eircode data file not found');
        }

        $data = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Error decoding Eircode data file');
        }

        foreach ($data as $entry) {
            if ($entry['name'] === $routingKey) {
                return $entry['description'];
            }
        }

        return 'Unknown Area';
    }
    public function getEircodes()
    {
        $filePath = storage_path(env('EIRCODE_JSON_PATH'));

        if (!File::exists($filePath)) {
            throw new \Exception("Eircode file not found.");
        }

        $content = File::get($filePath);
        return json_decode($content, true);
    }
}
