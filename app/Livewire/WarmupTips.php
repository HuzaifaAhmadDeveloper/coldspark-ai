<?php
namespace App\Livewire;
use Livewire\Component;

class WarmupTips extends Component
{
    public int $activeCategory = 0;

    public function setCategory(int $index): void
    {
        $this->activeCategory = $index;
    }

    public function render()
    {
        $categories = [
            [
                'icon'  => '🔧',
                'title' => 'Technical Setup',
                'color' => 'blue',
                'tips'  => [
                    [
                        'title' => 'Set Up SPF Record',
                        'priority' => 'Critical',
                        'desc'  => 'Add a Sender Policy Framework (SPF) record to your DNS. This tells email servers which IPs are allowed to send emails from your domain.',
                        'code'  => 'v=spf1 include:_spf.google.com ~all',
                        'impact'=> '🔴 Without this, 40% of your emails go to spam.'
                    ],
                    [
                        'title' => 'Configure DKIM',
                        'priority' => 'Critical',
                        'desc'  => 'DomainKeys Identified Mail (DKIM) adds a digital signature to your emails. It proves the email was not tampered with in transit.',
                        'code'  => 'Add DKIM TXT record provided by your email provider to DNS',
                        'impact'=> '🔴 Required for Gmail and Outlook deliverability.'
                    ],
                    [
                        'title' => 'Add DMARC Policy',
                        'priority' => 'Important',
                        'desc'  => 'DMARC tells receiving servers what to do with emails that fail SPF/DKIM checks. Start with "none" policy and monitor reports.',
                        'code'  => 'v=DMARC1; p=none; rua=mailto:dmarc@yourdomain.com',
                        'impact'=> '🟡 Protects domain reputation and improves trust.'
                    ],
                    [
                        'title' => 'Use Custom Domain Email',
                        'priority' => 'Critical',
                        'desc'  => 'Never cold email from @gmail.com, @yahoo.com or @hotmail.com. Use yourname@yourdomain.com — it builds trust and improves deliverability dramatically.',
                        'code'  => 'Use: huzaifa@nimblewebsolutions.com NOT huzaifa@gmail.com',
                        'impact'=> '🔴 Free email domains get 80% lower reply rates.'
                    ],
                ]
            ],
            [
                'icon'  => '📈',
                'title' => 'Volume Strategy',
                'color' => 'green',
                'tips'  => [
                    [
                        'title' => 'Start Slow — Week 1',
                        'priority' => 'Critical',
                        'desc'  => 'Send only 10-20 emails per day in your first week. Jumping straight to 200/day from a new domain triggers spam filters immediately.',
                        'code'  => 'Week 1: 10-20/day → Week 2: 30-40/day → Week 3: 50-70/day',
                        'impact'=> '🔴 Sending too fast = permanent domain blacklist.'
                    ],
                    [
                        'title' => 'Increase Volume Gradually',
                        'priority' => 'Important',
                        'desc'  => 'Increase your daily sending volume by 20-30% each week. This gradual increase signals to email providers that you are a legitimate sender.',
                        'code'  => 'Target: 100/day by week 4, 200/day by week 6',
                        'impact'=> '🟡 Steady growth = better sender reputation.'
                    ],
                    [
                        'title' => 'Warmup Tools',
                        'priority' => 'Recommended',
                        'desc'  => 'Use email warmup tools to automatically build your sender reputation before you start cold outreach. They simulate real human email activity.',
                        'code'  => 'Tools: Mailwarm, Lemwarm, Instantly Warmup, Warmbox',
                        'impact'=> '🟢 2-3 weeks of warmup = 3x better inbox placement.'
                    ],
                    [
                        'title' => 'Send During Business Hours',
                        'priority' => 'Important',
                        'desc'  => 'Schedule emails to arrive Tuesday through Thursday between 8 AM and 10 AM in your recipient timezone. Avoid Monday mornings and Friday afternoons.',
                        'code'  => 'Best: Tue-Thu 8-10 AM recipient timezone',
                        'impact'=> '🟡 Correct timing improves open rates by 25%.'
                    ],
                ]
            ],
            [
                'icon'  => '✍️',
                'title' => 'Content Quality',
                'color' => 'purple',
                'tips'  => [
                    [
                        'title' => 'Avoid Spam Trigger Words',
                        'priority' => 'Critical',
                        'desc'  => 'Certain words in subject lines or body text automatically trigger spam filters. Keep your language natural, professional and specific.',
                        'code'  => 'Avoid: "Free", "Guaranteed", "No obligation", "Click here", "Act now", "Limited time"',
                        'impact'=> '🔴 Even one spam word can kill your deliverability.'
                    ],
                    [
                        'title' => 'Personalize First Line',
                        'priority' => 'Critical',
                        'desc'  => 'The first sentence is what appears in Gmail preview. Make it 100% specific to the prospect — mention their company, recent news, or a genuine observation.',
                        'code'  => 'Bad: "I help companies like yours..." Good: "Saw TechFlow\'s Series A last week — congrats."',
                        'impact'=> '🔴 Personalized first lines get 3x more replies.'
                    ],
                    [
                        'title' => 'Keep Emails Short',
                        'priority' => 'Important',
                        'desc'  => 'Cold emails should be 50-150 words maximum. Busy decision-makers do not read long emails. Get to the point fast and have one clear CTA.',
                        'code'  => 'Ideal length: 3-5 sentences + 1 CTA',
                        'impact'=> '🟡 Short emails get 80% higher reply rates.'
                    ],
                    [
                        'title' => 'One CTA Only',
                        'priority' => 'Important',
                        'desc'  => 'Your email should have exactly ONE call to action. Multiple CTAs confuse the reader and reduce response rates significantly.',
                        'code'  => 'Bad: "Reply, visit our site, or book a call" Good: "Worth a 15-min call this week?"',
                        'impact'=> '🟡 Single CTA increases click rates by 42%.'
                    ],
                ]
            ],
            [
                'icon'  => '📊',
                'title' => 'List Hygiene',
                'color' => 'yellow',
                'tips'  => [
                    [
                        'title' => 'Verify Emails Before Sending',
                        'priority' => 'Critical',
                        'desc'  => 'Always verify your email list before sending. Bounced emails damage your sender reputation severely. Keep bounce rate below 2%.',
                        'code'  => 'Tools: Hunter.io, ZeroBounce, NeverBounce, Millionverifier',
                        'impact'=> '🔴 Over 3% bounce rate = domain blacklist risk.'
                    ],
                    [
                        'title' => 'Remove Unsubscribers Instantly',
                        'priority' => 'Critical',
                        'desc'  => 'Remove anyone who unsubscribes or asks to stop receiving emails immediately. This is legally required in most countries (GDPR, CAN-SPAM).',
                        'code'  => 'Process unsubscribe requests within 10 business days (CAN-SPAM law)',
                        'impact'=> '🔴 Ignoring unsubscribes = legal liability.'
                    ],
                    [
                        'title' => 'Clean List Monthly',
                        'priority' => 'Important',
                        'desc'  => 'Remove contacts who have not opened any of your last 5 emails. Sending to unengaged contacts hurts your overall deliverability score.',
                        'code'  => 'Remove: No opens after 5 emails, Role-based emails (info@, support@)',
                        'impact'=> '🟡 Clean lists improve inbox placement by 40%.'
                    ],
                    [
                        'title' => 'Avoid Role-Based Emails',
                        'priority' => 'Recommended',
                        'desc'  => 'Do not send to generic role-based email addresses like info@, support@, admin@, or sales@. These are monitored by multiple people and rarely convert.',
                        'code'  => 'Target: firstname@company.com NOT info@company.com',
                        'impact'=> '🟢 Personal emails get 5x higher reply rates.'
                    ],
                ]
            ],
            [
                'icon'  => '🔄',
                'title' => 'Follow-Up Strategy',
                'color' => 'red',
                'tips'  => [
                    [
                        'title' => 'Always Send Follow-Ups',
                        'priority' => 'Critical',
                        'desc'  => 'Over 70% of replies come from follow-up emails, not the first email. Most people need 3-5 touchpoints before they respond.',
                        'code'  => 'Email 1: Day 0 → Follow-up 1: Day 3 → Follow-up 2: Day 7',
                        'impact'=> '🔴 No follow-up = leaving 70% of replies on the table.'
                    ],
                    [
                        'title' => 'Change Angle Each Follow-Up',
                        'priority' => 'Important',
                        'desc'  => 'Each follow-up should offer a new angle, new value, or new information — not just "just following up on my last email." That never works.',
                        'code'  => 'Email 1: Pain point → Follow-up 1: Case study → Follow-up 2: Breakup email',
                        'impact'=> '🟡 New angle follow-ups get 40% more replies.'
                    ],
                    [
                        'title' => 'Use Breakup Email',
                        'priority' => 'Important',
                        'desc'  => 'Your final email should be a "breakup" — tell them this is your last email and make it easy for them to say yes or no. This often triggers a response.',
                        'code'  => '"This\'ll be my last email. Worth connecting? Yes/No works fine."',
                        'impact'=> '🟡 Breakup emails get the highest reply rates of all.'
                    ],
                    [
                        'title' => 'Wait Enough Between Emails',
                        'priority' => 'Important',
                        'desc'  => 'Space your follow-ups properly. Too frequent = annoying and marked as spam. Too slow = they forget who you are.',
                        'code'  => 'Gaps: 3 days → 4 days → 7 days → 14 days',
                        'impact'=> '🟡 Proper spacing increases positive sentiment by 30%.'
                    ],
                ]
            ],
        ];

        return view('livewire.warmup-tips', compact('categories'));
    }
}