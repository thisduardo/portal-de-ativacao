<?php
require __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/json; charset=utf-8');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$supabaseUrl = rtrim($_ENV['SUPABASE_URL'], '/');
$anonKey     = $_ENV['SUPABASE_ANON_KEY'];
$schema      = $_ENV['SUPABASE_SCHEMA'] ?? 'backoffice_tks';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed. Use GET."]);
    exit;
}

$cpfRaw = trim($_GET['cpf'] ?? '');
if ($cpfRaw === '') {
    http_response_code(400);
    echo json_encode(["error" => "Parâmetro 'cpf' é obrigatório."]);
    exit;
}

function onlyDigits(string $v): string {
    return preg_replace('/\D+/', '', $v) ?? '';
}
function formatCpf(string $digits): string {
    if (strlen($digits) !== 11) return $digits;
    return substr($digits, 0, 3) . "." . substr($digits, 3, 3) . "." . substr($digits, 6, 3) . "-" . substr($digits, 9, 2);
}

$cpfDigits = onlyDigits($cpfRaw);
$cpfMask   = formatCpf($cpfDigits);
$or = "or=(cpf.eq." . rawurlencode($cpfDigits) . ",cpf.eq." . rawurlencode($cpfMask) . ")";

$readHeaders = [
    "apikey: {$anonKey}",
    "Authorization: Bearer {$anonKey}",
    "Accept-Profile: {$schema}",
    "Content-Type: application/json",
];

function supabaseGet(string $url, array $headers): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 20,
    ]);

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return ["ok" => false, "code" => 500, "error" => $err, "raw" => null, "data" => null];

    $json = json_decode($body ?? '', true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ["ok" => false, "code" => 500, "error" => "Resposta não é JSON válido", "raw" => $body, "data" => null];
    }

    return ["ok" => ($code >= 200 && $code < 300), "code" => $code, "error" => null, "raw" => $body, "data" => $json];
}

/* 1) profiles */
$profilesUrl = "{$supabaseUrl}/rest/v1/profiles?{$or}&select=id,full_name,cpf,phone,birth_date,email_customer&limit=1";
$pRes = supabaseGet($profilesUrl, $readHeaders);

if (!$pRes["ok"]) {
    http_response_code($pRes["code"]);
    echo json_encode(["found" => false, "error" => "Erro ao buscar profile", "details" => $pRes["data"] ?? $pRes["raw"] ?? $pRes["error"]]);
    exit;
}

$profile = $pRes["data"][0] ?? null;
if (!$profile) {
    echo json_encode(["found" => false, "cpf" => $cpfRaw, "message" => "CPF não encontrado"]);
    exit;
}

$userId = $profile["id"];

/* 1.5) company_members (debug + join seguro) */
$companyUrl =
    "{$supabaseUrl}/rest/v1/company_members" .
    "?user_id=eq." . rawurlencode($userId) .
    "&status=eq.active" .
    "&select=company_id,roles,created_at,company:companies(id,name)" . // ✅ seguro: só id,name
    "&order=created_at.desc" .
    "&limit=1";

$cRes = supabaseGet($companyUrl, $readHeaders);

$companyMembership = null;
$companyDebug = null;

if ($cRes["ok"]) {
    $companyMembership = $cRes["data"][0] ?? null;
} else {
    // ✅ Agora você vai enxergar se é RLS/permissão/campo inexistente etc.
    $companyDebug = [
        "code" => $cRes["code"],
        "details" => $cRes["data"] ?? $cRes["raw"] ?? $cRes["error"]
    ];
}

/* 2) entitlements + products */
$entUrl =
    "{$supabaseUrl}/rest/v1/entitlements" .
    "?user_id=eq." . rawurlencode($userId) .
    "&status=eq.active" .
    "&select=id,status,expires_at,created_at,source_type,source_id,product:products(id,name,is_active)" .
    "&order=created_at.desc";

$eRes = supabaseGet($entUrl, $readHeaders);

if (!$eRes["ok"]) {
    http_response_code($eRes["code"]);
    echo json_encode([
        "found" => true,
        "profile" => $profile,
        "user_id" => $userId,
        "company_membership" => $companyMembership,
        "company_debug" => $companyDebug,
        "error" => "Erro ao buscar entitlements",
        "details" => $eRes["data"] ?? $eRes["raw"] ?? $eRes["error"]
    ]);
    exit;
}

$entitlements = $eRes["data"] ?? [];
$entitlements = array_values(array_filter($entitlements, function($e) {
    return isset($e["product"]["is_active"]) ? (bool)$e["product"]["is_active"] : true;
}));

echo json_encode([
    "found" => true,
    "profile" => $profile,
    "user_id" => $userId,
    "company_membership" => $companyMembership,
    "company_debug" => $companyDebug, // ✅ se estiver null, ok. se tiver algo, mostra o motivo.
    "entitlements" => $entitlements
]);
