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
        // Build signature block
        $signature = '';
        if (!empty($d['sig_name'])) {
            $signature .= "\n\nBest regards,\n";
            $signature .= $d['sig_name'] . "\n";
            if (!empty($d['sig_role']))    $signature .= $d['sig_role'] . "\n";
            if (!empty($d['sig_company'])) $signature .= $d['sig_company'] . "\n";
            if (!empty($d['sig_link']))    $signature .= $d['sig_link'] . "\n";
        }

        $sigInstruction = !empty($signature)
            ? "End every email with this exact professional signature:\n{$signature}\n\nDo NOT add any extra closing after the signature."
            : "End emails with a simple professional closing like 'Best regards,' followed by the sender's first name.";

        return <<<PROMPT
You are an expert cold email copywriter with a 40%+ reply rate. Write a cold outreach email sequence.

PROSPECT DETAILS:
- Name: {$d['name']}
- Company: {$d['company']}
- Role/Title: {$d['role']}
- Industry: {$d['industry']}
- Pain point / goal: {$d['pain_point']}
- Personal note: {$d['personal_note']}

SENDER DETAILS:
- Offer: {$d['offer']}
- Value proposition: {$d['value_prop']}
- CTA: {$d['cta']}

EMAIL STYLE: {$d['style']}
- direct: Problem → Solution → CTA, bold and concise
- friendly: Warm, conversational, empathetic tone
- formal: Professional enterprise tone
- witty: Clever hook, light humor, memorable

SIGNATURE INSTRUCTION:
{$sigInstruction}

QUALITY RULES:
- Make the opening line 100% specific to the prospect
- Keep emails under 150 words (except formal style)
- One clear CTA per email
- Sound human, not like AI wrote it
- Follow-up 1: New angle, not just "following up"
- Follow-up 2: Breakup email style

Return ONLY this JSON (no markdown, no explanation):
{
  "subject1": "subject line option A",
  "subject2": "subject line option B",
  "email1": "opener email body with signature",
  "email2": "follow-up #1 body with signature",
  "email3": "follow-up #2 body with signature"
}
PROMPT;
    }
}