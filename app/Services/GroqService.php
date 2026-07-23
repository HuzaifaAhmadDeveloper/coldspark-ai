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
    $signature = '';
    if (!empty($d['sig_name'])) {
        $signature .= "\n\nBest regards,\n";
        $signature .= $d['sig_name'] . "\n";
        if (!empty($d['sig_role']))    $signature .= $d['sig_role'] . "\n";
        if (!empty($d['sig_company'])) $signature .= $d['sig_company'] . "\n";
        if (!empty($d['sig_link']))    $signature .= $d['sig_link'] . "\n";
    }

    $sigInstruction = !empty($signature)
        ? "End every email with this exact professional signature:\n{$signature}"
        : "End emails with:\nBest regards,\n[Your Name]";

    return <<<PROMPT
You are an expert cold email copywriter. Write a professional cold outreach sequence.

PROSPECT DETAILS:
- Name: {$d['name']}
- Company: {$d['company']}
- Role/Title: {$d['role']}
- Industry: {$d['industry']}
- Pain point: {$d['pain_point']}
- Personal note: {$d['personal_note']}

SENDER DETAILS:
- Offer: {$d['offer']}
- Value proposition: {$d['value_prop']}
- CTA: {$d['cta']}
- Style: {$d['style']}

CRITICAL FORMATTING RULES — YOU MUST FOLLOW THESE:
1. Each paragraph MUST be separated by a blank line (double newline \\n\\n)
2. The greeting line must be on its own line: "Dear [Name]," or "Hi [Name],"
3. After greeting — blank line
4. Each paragraph — separated by blank line
5. CTA must be on its own line
6. Signature must be on its own line after blank line
7. NEVER write the entire email as one block of text
8. Maximum 2-3 sentences per paragraph

EXAMPLE FORMAT:
Hi [Name],

[Opening paragraph - 1-2 sentences specific to prospect.]

[Value paragraph - 1-2 sentences about what you offer.]

[CTA sentence.]

{$sigInstruction}

STYLE GUIDE:
- direct: Problem → Solution → CTA, concise
- friendly: Warm, conversational
- formal: Professional enterprise tone
- witty: Clever hook, memorable

Return ONLY this JSON (no markdown):
{
  "subject1": "subject line A",
  "subject2": "subject line B",
  "email1": "Hi [Name],\\n\\n[paragraph1]\\n\\n[paragraph2]\\n\\n[CTA]\\n\\n[signature]",
  "email2": "Hi [Name],\\n\\n[paragraph1]\\n\\n[paragraph2]\\n\\n[CTA]\\n\\n[signature]",
  "email3": "Hi [Name],\\n\\n[paragraph1]\\n\\n[paragraph2]\\n\\n[CTA]\\n\\n[signature]"
}
PROMPT;
}
}