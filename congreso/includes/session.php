<?php
// Funciones de sesión y helpers globales

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Precios ───────────────────────────────────────────────
const PRECIO_PARTICIPANTE_PRESENCIAL = 1500.00; // MXN
const PRECIO_PARTICIPANTE_VIRTUAL    =  800.00;
const PRECIO_PONENTE_PRESENCIAL      = 2000.00;
const PRECIO_PONENTE_VIRTUAL         = 1200.00;

// PayPal (sandbox por defecto – cambia a live en producción)
const PAYPAL_CLIENT_ID = 'AX2m7-YlFcStj51Bm_JWGT59iN1mRCuhFYCqSEovrXp5hdLRpxPTEmB27k9CxVNMN6gX0jqTzL5VWTpY';
const PAYPAL_MODE      = 'sandbox'; // 'sandbox' | 'live'

// ── Helpers ───────────────────────────────────────────────
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        redirect('../admin/login.php');
    }
}

function flash(string $key, string $msg = ''): string {
    if ($msg !== '') {
        $_SESSION['flash'][$key] = $msg;
        return '';
    }
    $out = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $out;
}
