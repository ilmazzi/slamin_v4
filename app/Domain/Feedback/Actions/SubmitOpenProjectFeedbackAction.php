<?php

namespace Domain\Feedback\Actions;

use Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SubmitOpenProjectFeedbackAction
{
    public function execute(User $user, string $subject, string $description, ?string $url, ?UploadedFile $screenshot): int
    {
        $baseUrl = rtrim((string) config('services.openproject.base_url'), '/');
        $token = (string) config('services.openproject.api_token');
        $project = trim((string) config('services.openproject.project'));
        $typeId = (int) config('services.openproject.type_id');

        if ($baseUrl === '' || $token === '' || $project === '' || $typeId < 1) {
            throw new RuntimeException('openproject_not_configured');
        }

        $response = Http::withBasicAuth('apikey', $token)
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($baseUrl.'/api/v3/projects/'.rawurlencode($project).'/work_packages', [
                'subject' => $subject,
                'description' => [
                    'format' => 'markdown',
                    'raw' => $this->description($user, $description, $url),
                ],
                '_links' => [
                    'type' => [
                        'href' => '/api/v3/types/'.$typeId,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->failureMessage($response->json('message')));
        }

        $workPackageId = (int) $response->json('id');

        if ($workPackageId < 1) {
            throw new RuntimeException('OpenProject non ha restituito il task creato.');
        }

        if ($screenshot instanceof UploadedFile) {
            $this->attachScreenshot($baseUrl, $token, $workPackageId, $screenshot);
        }

        return $workPackageId;
    }

    private function description(User $user, string $description, ?string $url): string
    {
        $lines = [trim($description), ''];

        if (filled($url)) {
            $lines[] = '**Pagina:** '.$url;
        }

        $name = $user->profile?->display_name ?: $user->profile?->nickname ?: $user->email;
        $lines[] = '**Utente:** '.$name.' <'.$user->email.'>';

        return implode("\n", $lines);
    }

    private function attachScreenshot(string $baseUrl, string $token, int $workPackageId, UploadedFile $screenshot): void
    {
        $filename = $screenshot->getClientOriginalName() ?: 'screenshot.png';

        $response = Http::withBasicAuth('apikey', $token)
            ->timeout(30)
            ->attach('file', fopen($screenshot->getRealPath(), 'r'), $filename)
            ->post($baseUrl.'/api/v3/work_packages/'.$workPackageId.'/attachments', [
                'metadata' => json_encode(['fileName' => $filename]),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->failureMessage($response->json('message')));
        }
    }

    private function failureMessage(mixed $message): string
    {
        if (is_string($message) && trim($message) !== '') {
            return $message;
        }

        return 'OpenProject ha rifiutato il feedback.';
    }
}
