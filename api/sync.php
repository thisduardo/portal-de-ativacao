<?php

/**
 * ARQUIVO: /api/sync.php
 * DESCRIÇÃO:
 *  - Verifica se já existe synchronization ACTIVE para o user_id
 *  - Se já existe, NÃO chama Lecupon / Doutor ao Vivo
 *  - Se não existe, busca profile + company (com credenciais sensíveis)
 *  - Chama integrações somente para produtos que o usuário tem
 *  - UPSERT em backoffice_tks.synchronization
 */

require __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/json; charset=utf-8');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

/* =========================
   ENV / SUPABASE
========================= */
$supabaseUrl = rtrim($_ENV['SUPABASE_URL'] ?? '', '/');
$serviceKey  = $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '';
$schema      = $_ENV['SUPABASE_SCHEMA'] ?? 'backoffice_tks';

/* =========================
   ENV / DOUTOR AO VIVO
========================= */
$doutorUrl = rtrim($_ENV['DOUTOR_AO_VIVO_URL'] ?? 'https://api.v2.doutoraovivo.com.br', '/');
$doutorKey = $_ENV['DOUTOR_AO_VIVO_API_KEY'] ?? '';

/* =========================
   CONSTANTES PRODUTOS (mesmos ids do seu JS)
========================= */


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed. Use POST.']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw ?? '', true);

$userId = trim((string)($body['user_id'] ?? ''));
$companyId = trim((string)($body['company']['id'] ?? ''));
$activeProducts = $body['active_products'] ?? [];
if (!is_array($activeProducts)) $activeProducts = [];

$activeProducts = array_values(array_filter(array_map(function($v){
    return trim((string)$v);
}, $activeProducts)));


if ($userId === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'user_id é obrigatório']);
    exit;
}

if ($supabaseUrl === '' || $serviceKey === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'SUPABASE_URL / SERVICE_ROLE_KEY não configurados']);
    exit;
}

/* =========================
   1) Se já tem sync active -> pula tudo
========================= */
$existing = getSyncActiveByUserId($supabaseUrl, $serviceKey, $schema, $userId);
if ($existing['ok'] && $existing['active'] === true) {
    echo json_encode([
        'ok' => true,
        'skipped' => true,
        'reason' => 'already_active',
        'synchronization' => $existing['row'],
    ]);
    exit;
}

/* =========================
   2) Buscar profile (garante email_customer etc)
========================= */
$profile = getProfileById($supabaseUrl, $serviceKey, $schema, $userId);
if (!$profile) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Profile não encontrado para user_id', 'user_id' => $userId]);
    exit;
}

/* =========================
   3) Buscar company (pegar bussiness_alloyal_id + tokens Lecupon)
========================= */
$company = null;
if ($companyId !== '') {
    $company = getCompanyById($supabaseUrl, $serviceKey, $schema, $companyId);
}

/* =========================
   4) Decide quais integrações chamar com base nos produtos
========================= */
$hasTele  = hasProductLike($activeProducts, 'tele');
$hasClube = hasProductLike($activeProducts, 'clube');


$results = [
    'doutor_ao_vivo' => ['called' => false, 'ok' => null, 'http_code' => null, 'error' => null],
    'lecupon'        => ['called' => false, 'ok' => null, 'http_code' => null, 'error' => null],
];

/* =========================
   4.1) Doutor ao Vivo (só se tiver tele)
========================= */
if ($hasTele) {
    $results['doutor_ao_vivo']['called'] = true;

    if ($doutorKey === '') {
        $results['doutor_ao_vivo']['ok'] = false;
        $results['doutor_ao_vivo']['error'] = 'DOUTOR_AO_VIVO_API_KEY não configurado';
    } else {
        $cpfDigits = onlyDigits((string)($profile['cpf'] ?? ''));
        $phoneDigits = onlyDigits((string)($profile['phone'] ?? ''));

        $payloadDoutor = [
            "name"           => (string)($profile['full_name'] ?? ''),
            "birth_date"     => (string)($profile['birth_date'] ?? ''),
            "email"          => (string)($profile['email_customer'] ?? ''), // seu campo real
            "cpf"            => $cpfDigits,
            "cell_phone"     => $phoneDigits,  // espera ddd+numero
            "cell_phone_ddi" => "+55",
            "plan_id"        => "assinatura_tks",
            "plan_status"    => "ACTIVE"
        ];

        $resp = callExternalApi(
            'POST',
            $doutorUrl . '/person',
            $payloadDoutor,
            [
                "x-api-key: {$doutorKey}",
                "Content-Type: application/json",
                "Accept: application/json",
            ]
        );

        $results['doutor_ao_vivo']['ok'] = $resp['ok'];
        $results['doutor_ao_vivo']['http_code'] = $resp['http_code'];
        $results['doutor_ao_vivo']['error'] = $resp['ok'] ? null : ($resp['data'] ?? $resp['error'] ?? 'Erro desconhecido');
    }
}

/* =========================
   4.2) Lecupon (só se tiver clube)
   URL usa bussiness_alloyal_id como {codigo da empresa}
   Headers vêm do banco (companies)
========================= */
if ($hasClube) {
    $results['lecupon']['called'] = true;

    if (!$company) {
        $results['lecupon']['ok'] = false;
        $results['lecupon']['error'] = 'Empresa não encontrada (company.id não veio ou não existe)';
    } else {
        $businessCode = $company['bussiness_alloyal_id'] ?? null; // <- seu campo!
        $empEmail = $company['lecupon_employee_email'] ?? null;
        $empToken = $company['lecupon_employee_token'] ?? null;

        if (!$businessCode) {
            $results['lecupon']['ok'] = false;
            $results['lecupon']['error'] = 'companies.bussiness_alloyal_id está vazio (codigo da empresa no Lecupon)';
        } elseif (!$empEmail || !$empToken) {
            $results['lecupon']['ok'] = false;
            $results['lecupon']['error'] = 'Credenciais Lecupon ausentes na companies (lecupon_employee_email/token)';
        } else {
            $cpfDigits = onlyDigits((string)($profile['cpf'] ?? ''));

            $payloadLecupon = [
                "authorized_users" => [
                    [
                        "cpf" => $cpfDigits,
                        "name" => (string)($profile['full_name'] ?? ''),
                        "active" => true
                    ]
                ]
            ];

            $resp = callExternalApi(
                'POST',
                "https://api.lecupon.com/client/v2/businesses/{$businessCode}/authorized_users/sync",
                $payloadLecupon,
                [
                    "X-ClientEmployee-Email: {$empEmail}",
                    "X-ClientEmployee-Token: {$empToken}",
                    "Accept: application/json",
                    "Content-Type: application/json",
                ]
            );


            $results['lecupon']['ok'] = $resp['ok'];
            $results['lecupon']['http_code'] = $resp['http_code'];
            $results['lecupon']['error'] = $resp['ok'] ? null : ($resp['data'] ?? $resp['error'] ?? 'Erro desconhecido');
        }
    }
}

/* =========================
   5) Define status final e UPSERT em synchronization
   - Se foi preciso chamar algo e falhou -> inactive
   - Se não precisou chamar nada -> active (só registra)
========================= */
$neededCalls = 0;
$failedCalls = 0;

foreach (['doutor_ao_vivo', 'lecupon'] as $k) {
    if ($results[$k]['called']) {
        $neededCalls++;
        if ($results[$k]['ok'] !== true) $failedCalls++;
    }
}

$finalStatus = ($failedCalls === 0) ? 'active' : 'inactive';

$syncLog = [
    'timestamp' => gmdate('c'),
    'user_id' => $userId,
    'company_id' => $companyId,
    'products' => $activeProducts,
    'results' => $results,
];

$syncRow = [
    'user_id' => $userId,
    'status' => $finalStatus,
    'active_products' => $activeProducts,
    'log' => $syncLog,
    'updated_at' => gmdate('c'),
];




$upsert = upsertSynchronization($supabaseUrl, $serviceKey, $schema, $syncRow);

echo json_encode([
    'ok' => true,
    'skipped' => false,
    'status' => $finalStatus,
    'needed_calls' => $neededCalls,
    'failed_calls' => $failedCalls,
    'results' => $results,
    'upsert' => [
        'ok' => $upsert['ok'],
        'http_code' => $upsert['http_code'],
    ],
]);

/* =========================
   HELPERS
========================= */

function onlyDigits(string $v): string
{
    return preg_replace('/\D+/', '', $v) ?? '';
}

function executeCurl(string $url, string $method, ?string $body, array $headers): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = null;
    if ($response !== false && $response !== null) {
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) $decoded = $response;
    }

    return ['http_code' => $http, 'error' => $err ?: null, 'data' => $decoded, 'raw' => $response];
}

function callExternalApi(string $method, string $url, array $payload, array $headers): array
{
    $resp = executeCurl($url, $method, json_encode($payload), $headers);
    $ok = ($resp['http_code'] >= 200 && $resp['http_code'] < 300);
    return ['ok' => $ok, 'http_code' => $resp['http_code'], 'data' => $resp['data'], 'error' => $resp['error']];
}

function getSyncActiveByUserId(string $supabaseUrl, string $serviceKey, string $schema, string $userId): array
{
    $url = $supabaseUrl . "/rest/v1/synchronization"
        . "?user_id=eq." . rawurlencode($userId)
        . "&status=eq.active"
        . "&select=id,user_id,status,active_products,activation_date,created_at,updated_at"
        . "&limit=1";

    $headers = [
        "apikey: {$serviceKey}",
        "Authorization: Bearer {$serviceKey}",
        "Accept-Profile: {$schema}",
        "Content-Profile: {$schema}",
        "Accept: application/json",
    ];

    $resp = executeCurl($url, 'GET', null, $headers);
    if ($resp['http_code'] >= 400) return ['ok' => false, 'active' => false, 'error' => $resp];

    $rows = is_array($resp['data']) ? $resp['data'] : [];
    if (count($rows) > 0) return ['ok' => true, 'active' => true, 'row' => $rows[0]];
    return ['ok' => true, 'active' => false, 'row' => null];
}

function getProfileById(string $supabaseUrl, string $serviceKey, string $schema, string $userId): ?array
{
    $url = $supabaseUrl . "/rest/v1/profiles"
        . "?id=eq." . rawurlencode($userId)
        . "&select=id,full_name,cpf,phone,birth_date,email_customer"
        . "&limit=1";

    $headers = [
        "apikey: {$serviceKey}",
        "Authorization: Bearer {$serviceKey}",
        "Accept-Profile: {$schema}",
        "Content-Profile: {$schema}",
        "Accept: application/json",
    ];

    $resp = executeCurl($url, 'GET', null, $headers);
    if ($resp['http_code'] >= 400) return null;

    $rows = is_array($resp['data']) ? $resp['data'] : [];
    return $rows[0] ?? null;
}

function getCompanyById(string $supabaseUrl, string $serviceKey, string $schema, string $companyId): ?array
{
    $url = $supabaseUrl . "/rest/v1/companies"
        . "?id=eq." . rawurlencode($companyId)
        . "&select=id,name,bussiness_alloyal_id,lecupon_employee_email,lecupon_employee_token"
        . "&limit=1";

    $headers = [
        "apikey: {$serviceKey}",
        "Authorization: Bearer {$serviceKey}",
        "Accept-Profile: {$schema}",
        "Content-Profile: {$schema}",
        "Accept: application/json",
    ];

    $resp = executeCurl($url, 'GET', null, $headers);
    if ($resp['http_code'] >= 400) return null;

    $rows = is_array($resp['data']) ? $resp['data'] : [];
    return $rows[0] ?? null;
}


function upsertSynchronization(string $supabaseUrl, string $serviceKey, string $schema, array $row): array
{
    // Requer UNIQUE(user_id) para on_conflict funcionar direito
    $url = $supabaseUrl . "/rest/v1/synchronization?on_conflict=user_id";

    $headers = [
        "apikey: {$serviceKey}",
        "Authorization: Bearer {$serviceKey}",
        "Accept-Profile: {$schema}",
        "Content-Profile: {$schema}",
        "Content-Type: application/json",
        "Prefer: resolution=merge-duplicates,return=representation",
    ];

    $resp = executeCurl($url, 'POST', json_encode([$row]), $headers);
    $ok = ($resp['http_code'] >= 200 && $resp['http_code'] < 300);

    return ['ok' => $ok, 'http_code' => $resp['http_code'], 'data' => $resp['data'], 'error' => $resp['error']];
}

function hasProductLike(array $products, string $needle): bool {
  $needle = mb_strtolower($needle);

  foreach ($products as $p) {
    if (mb_stripos(mb_strtolower((string)$p), $needle) !== false) {
      return true;
    }
  }
  return false;
}
