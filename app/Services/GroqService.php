<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;

class GroqService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.groq.key');
        $this->model  = config('services.groq.model', 'llama-3.3-70b-versatile');
    }

    public function generateSequence(array $data): array
    {
        $prompt = $this->buildPrompt($data);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'       => $this->model,
            'max_tokens'  => 2000,
            'temperature' => 0.7,
            'messages'    => [
                ['role' => 'user', 'content' => $prompt]
            ],
        ]);

        $content = $response->json('choices.0.message.content', '');
        $content = preg_replace('/```json|```/', '', $content);

        return json_decode(trim($content), true) ?? [];
    }

    private function buildPrompt(array $d): string
    {
        return <<<PROMPT
You are an expert cold email copywriter with a 40%+ reply rate.

PROSPECT:
- Name: {$d['name']}
- Company: {$d['company']}
- Role: {$d['role']}
- Industry: {$d['industry']}
- Pain point: {$d['pain_point']}
- Personal note: {$d['personal_note']}

SENDER:
- Offer: {$d['offer']}
- Value proposition: {$d['value_prop']}
- CTA: {$d['cta']}
- Style: {$d['style']}

Write a 3-email cold outreach sequence. Style guide:
- direct: Problem → Solution → CTA, bold and concise
- friendly: Warm, conversational, empathetic tone
- formal: Professional enterprise tone
- witty: Clever hook, light humor, memorable

Return ONLY this JSON (no markdown, no explanation):
{
  "subject1": "subject line option A",
  "subject2": "subject line option B",
  "email1": "opener email body",
  "email2": "follow-up #1 body (day 3, new angle)",
  "email3": "follow-up #2 body (day 7, breakup style)"
}
PROMPT;
    }
}