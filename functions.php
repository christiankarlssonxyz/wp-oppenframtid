<?php
/**
 * functions.php
 *
 * Laddar in alla temafiler i ordning.
 * Varje fil har ett tydligt ansvarsområde.
 * Lägg INTE till kod direkt här – använd rätt fil i inc/.
 */

// ── Grundinställningar (nav, bild-storlekar, stöd för featurd bild osv.) ──────
require_once get_template_directory() . '/inc/setup.php';

// ── CSS och JS laddas in ───────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/enqueue.php';

// ── Taxonomi: Ämnen ────────────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/topics.php';

// ── Gilla-knapp på inlägg ──────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/likes.php';

// ── Följ ämnen ─────────────────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/follow.php';

// ── Kommentarer (svar-system, nästling) ────────────────────────────────────────
require_once get_template_directory() . '/inc/comments.php';

// ── Community: insändare (inloggade kan skriva inlägg) ─────────────────────────
require_once get_template_directory() . '/inc/community.php';

// ── Lokala avatarer (ersätter Gravatar) ───────────────────────────────────────
require_once get_template_directory() . '/inc/avatars.php';

// ── Underhållsläge ─────────────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/maintenance.php';

// ── Integritetsgranskning ───────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/integrity.php';

// ── CSV-import av inlägg ────────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/import.php';

// ── JSON-export/import av taggar och kategorier ─────────────────────────────────
require_once get_template_directory() . '/inc/terms.php';

// ── Linktree-sida ───────────────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/linktree.php';

// ── Autentisering (login/register/logout-redirects) ────────────────────────────
require_once get_template_directory() . '/inc/auth.php';

// ── Roller och behörigheter ─────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/roles.php';

// ── Spara inlägg ───────────────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/saved.php';

// ── Kommentarsrapportering ──────────────────────────────────────────────────────
require_once get_template_directory() . '/inc/reports.php';
