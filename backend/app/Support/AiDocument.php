<?php
namespace App\Support;

/** Rules for AI-written documents: what the model may never invent, and how gaps are marked so the user can fill them in. */
final class AiDocument
{
    /** Keys (the UI translates them). The same rules are spelled out in the prompt. */
    public const PROHIBITED = ['prices', 'discounts', 'deadlines', 'warranty', 'legal_terms', 'availability', 'binding_offer'];

    private const LANG = ['uz' => 'Uzbek (Latin script)', 'ru' => 'Russian', 'en' => 'English'];

    public static function languageName(string $lang): string
    {
        return self::LANG[$lang] ?? self::LANG['uz'];
    }

    public static function rulesForPrompt(): string
    {
        return "STRICT RULES: use only the facts in VERIFIED_FACTS. Never invent or estimate prices, discounts, delivery or payment deadlines, warranties, "
            . "legal clauses, product availability or any commitment. Where a needed fact is absent write exactly [MISSING: short description of what is needed] "
            . "(one placeholder per missing fact, the description in the document language, no square brackets inside). The text is a DRAFT, not a binding offer.";
    }

    /** Unique gap descriptions, in order of appearance. @return list<string> */
    public static function missing(?string $content): array
    {
        if (!$content || !preg_match_all('/\[MISSING:\s*([^\]]{1,200})\]/u', $content, $m)) return [];
        return array_values(array_unique(array_map('trim', $m[1])));
    }
}
