<?php
/**
 * ai_moderation.php — AI Prompt Security, Moderation & 3-Strike Warning Engine
 *
 * Rules:
 *  - Filters inappropriate, abusive, profane, or vulgar language (English & Filipino/Tagalog).
 *  - Enforces 3-strike warning policy per user account.
 *  - 3rd strike triggers an automatic 5-day suspension from AI chat.
 *  - Violations are logged to security_logs and backend/ai_violations.json.
 *  - Conversations are NOT deleted (persisted for audit and user history).
 */

require_once __DIR__ . '/db_helper.php';

function getAiViolationsFile(): string {
    $dir = dirname(__DIR__) . '/backend';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir . '/ai_violations.json';
}

function loadAiViolations(): array {
    $file = getAiViolationsFile();
    if (!file_exists($file)) return [];
    $data = json_decode(@file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveAiViolations(array $data): bool {
    $file = getAiViolationsFile();
    return (bool)@file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Check if a text contains offensive, vulgar, profane, or abusive content.
 */
function isOffensiveContent(string $text): bool {
    $t = mb_strtolower(trim($text));
    if ($t === '') return false;

    // Normalizing common letter leetspeak substitutions
    $normalized = str_replace(['@', '$', '1', '0', '3', '!', '*'], ['a', 's', 'i', 'o', 'e', 'i', ''], $t);

    // English & Tagalog Profanity / Vulgar / Harassment keywords & regexes
    $badPatterns = [
        // English
        '/\b(fuck|fucking|fucked|fucker|fuckin|fck|fuk|stfu)\b/i',
        '/\b(shit|bitch|cunt|asshole|bastard|whore|slut|dickhead|motherfucker)\b/i',
        '/\b(dick|tits|boobs|pussy|vagina|penis|cock|dildo)\b/i',
        '/\b(kill\s+yourself|go\s+die|kys)\b/i',

        // Filipino / Tagalog
        '/\b(tite|titi|burat|bayag|tamod|jakol|chupa)\b/i',
        '/\b(puke|puki|kiki|pekpek|kantot|hindot|iyot)\b/i',
        '/\b(putang\s*ina|tangina|tang\s*ina|taena|ina\s*mo|pakyu|pakyow)\b/i',
        '/\b(gago|gaga|tarantado|tarantada|ulol|ogag|kupal|hinayupak)\b/i',
        '/\b(bobo|tangae|leche|letse|punyeta|pucha|pota|puta)\b/i',
        '/\b(hayop\s+ka|lintek|bwisit)\b/i',
    ];

    foreach ($badPatterns as $pattern) {
        if (preg_match($pattern, $t) || preg_match($pattern, $normalized)) {
            return true;
        }
    }

    return false;
}

/**
 * Main AI Prompt Safety Verification
 * Returns [ 'allowed' => bool, 'message' => string, 'strike' => int, 'banned' => bool, 'banned_until' => int ]
 */
function verifyAiPromptSafety(string $prompt, array $identity): array {
    $email = strtolower(trim($identity['email'] ?? 'guest'));
    $name = trim($identity['name'] ?? 'User');
    $role = trim($identity['role'] ?? 'student');

    $allViolations = loadAiViolations();
    $userRecord = $allViolations[$email] ?? [
        'email' => $email,
        'name' => $name,
        'role' => $role,
        'strikes' => 0,
        'banned_until' => 0,
        'history' => []
    ];

    $now = time();

    // 1. Check existing 5-day ban
    if (!empty($userRecord['banned_until']) && $userRecord['banned_until'] > $now) {
        $remainingSeconds = $userRecord['banned_until'] - $now;
        $remainingDays = ceil($remainingSeconds / 86400);
        $unbanDate = date('F j, Y, g:i A', $userRecord['banned_until']);

        logSecurityEvent("AI_ACCESS_BLOCKED: Suspended user $email attempted AI query ($remainingDays days left)", $email, 'Low');

        return [
            'allowed' => false,
            'banned' => true,
            'strike' => $userRecord['strikes'],
            'max_strikes' => 3,
            'banned_until' => $userRecord['banned_until'],
            'message' => "🚫 **AI Assistant Suspended (Account Locked)**\n\nYour account is currently under a **5-day suspension** from the Campus AI Assistant due to 3 repeated policy violations.\n\n- **Suspension Ends**: **{$unbanDate}** (~{$remainingDays} day(s) remaining)\n- **Status**: Read-only history permitted; new inquiries blocked\n- **Appeals**: Contact your Department Chair or Campus IT Administrator if you believe this suspension is in error."
        ];
    }

    // If ban has expired, reset strikes and clear ban
    if (!empty($userRecord['banned_until']) && $userRecord['banned_until'] <= $now && $userRecord['strikes'] >= 3) {
        $userRecord['strikes'] = 0;
        $userRecord['banned_until'] = 0;
        $allViolations[$email] = $userRecord;
        saveAiViolations($allViolations);
        logSecurityEvent("AI_BAN_EXPIRED: $email ban completed. Strikes reset to 0.", $email, 'Low');
    }

    // 2. Content Moderation Check
    if (!isOffensiveContent($prompt)) {
        return [
            'allowed' => true,
            'banned' => false,
            'strike' => $userRecord['strikes'],
            'message' => ''
        ];
    }

    // Infraction detected! Increment strike
    $userRecord['strikes'] = ($userRecord['strikes'] ?? 0) + 1;
    $currentStrike = $userRecord['strikes'];
    $snippet = mb_substr(trim($prompt), 0, 100);

    $userRecord['history'][] = [
        'timestamp' => date('c'),
        'strike' => $currentStrike,
        'prompt_snippet' => $snippet
    ];
    $userRecord['name'] = $name ?: $userRecord['name'];
    $userRecord['role'] = $role ?: $userRecord['role'];

    if ($currentStrike === 1) {
        // Strike 1
        $allViolations[$email] = $userRecord;
        saveAiViolations($allViolations);
        logSecurityEvent("AI_STRIKE_1: Inappropriate prompt by $email: \"$snippet\"", $email, 'Medium');

        return [
            'allowed' => false,
            'warning' => true,
            'banned' => false,
            'strike' => 1,
            'max_strikes' => 3,
            'message' => "⚠️ **Campus AI Policy Warning (Strike 1 of 3)**\n\nInappropriate, profane, or abusive language is strictly prohibited under the **Navotas Polytechnic College Code of Conduct**.\n\n- **Infraction**: Policy violation detected.\n- **Remaining Warnings**: 2 warnings left.\n- **Consequence**: Reaching 3 warnings will result in an immediate **5-day suspension** from using the Campus AI.\n\nPlease rephrase your question respectfully and adhere to academic standards."
        ];
    } elseif ($currentStrike === 2) {
        // Strike 2
        $allViolations[$email] = $userRecord;
        saveAiViolations($allViolations);
        logSecurityEvent("AI_STRIKE_2: Repeated inappropriate prompt by $email: \"$snippet\"", $email, 'Medium');

        return [
            'allowed' => false,
            'warning' => true,
            'banned' => false,
            'strike' => 2,
            'max_strikes' => 3,
            'message' => "⚠️ **Campus AI Policy Warning (Strike 2 of 3) — FINAL WARNING**\n\nRepeated violation of campus AI conduct guidelines detected.\n\n- **Infraction**: Second policy breach recorded.\n- **Remaining Warnings**: **1 warning remaining**.\n- **Caution**: One more infraction will **immediately lock your account for 5 days**.\n\nPlease maintain professional academic inquiry."
        ];
    } else {
        // Strike 3+: 5-Day Ban!
        $bannedUntil = $now + (5 * 86400); // 5 days lockout
        $userRecord['banned_until'] = $bannedUntil;
        $allViolations[$email] = $userRecord;
        saveAiViolations($allViolations);

        $unbanDate = date('F j, Y, g:i A', $bannedUntil);
        logSecurityEvent("AI_STRIKE_3_BANNED: $email suspended for 5 days until $unbanDate for prompt: \"$snippet\"", $email, 'High');

        return [
            'allowed' => false,
            'warning' => true,
            'banned' => true,
            'strike' => 3,
            'max_strikes' => 3,
            'banned_until' => $bannedUntil,
            'message' => "🚫 **AI Assistant Suspended (Strike 3 of 3 Exceeded)**\n\nYou have accumulated 3 warnings for inappropriate language or conduct violations.\n\nIn accordance with NPC Academic Regulations, your access to the Campus AI Assistant is **suspended for 5 days** until **{$unbanDate}**.\n\nThis incident has been logged in the security audit trail. Conversations are preserved for institutional accountability."
        ];
    }
}

/**
 * Retrieve all active violations and bans for Admin Dashboard
 */
function getAiViolationsList(): array {
    $all = loadAiViolations();
    $now = time();
    $list = [];

    foreach ($all as $email => $r) {
        $strikes = $r['strikes'] ?? 0;
        $bannedUntil = $r['banned_until'] ?? 0;
        $isBanned = $bannedUntil > $now;

        if ($strikes > 0 || $isBanned) {
            $latest = !empty($r['history']) ? end($r['history']) : null;
            $list[] = [
                'email' => $email,
                'name' => $r['name'] ?? 'User',
                'role' => $r['role'] ?? 'student',
                'strikes' => $strikes,
                'is_banned' => $isBanned,
                'banned_until' => $bannedUntil ? date('c', $bannedUntil) : null,
                'banned_until_formatted' => $bannedUntil ? date('M j, Y g:i A', $bannedUntil) : null,
                'latest_prompt' => $latest['prompt_snippet'] ?? '—',
                'latest_time' => $latest['timestamp'] ?? '—'
            ];
        }
    }

    usort($list, fn($a, $b) => ($b['is_banned'] ? 1 : 0) <=> ($a['is_banned'] ? 1 : 0) ?: ($b['strikes'] <=> $a['strikes']));
    return $list;
}

/**
 * Admin action: Reset user strikes and lift ban
 */
function resetUserAiViolations(string $email, string $adminEmail = 'admin'): bool {
    $email = strtolower(trim($email));
    $all = loadAiViolations();
    if (isset($all[$email])) {
        $prevStrikes = $all[$email]['strikes'] ?? 0;
        unset($all[$email]);
        saveAiViolations($all);
        logSecurityEvent("AI_STRIKES_RESET: $adminEmail cleared $prevStrikes strikes and lifted ban for $email", $adminEmail, 'Medium');
        return true;
    }
    return false;
}
