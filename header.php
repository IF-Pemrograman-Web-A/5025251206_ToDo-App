<?php
// header.php - Komponen Header Modular & Navigasi Atas
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citadel Todo App - Personal & Shared Organizer</title>
    <link rel="stylesheet" href="style.css">
    <!-- Google Fonts untuk tipografi modern -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- A11y Announcer (Screen Reader Accessibility) -->
    <div id="a11y-announcer" class="sr-only" aria-live="polite"></div>

    <!-- Header / Banner Atas -->
    <header role="banner" class="app-header">
        <div class="header-content">
            <div class="brand">
                <span class="brand-icon"></span>
                <div class="brand-text">
                    <h1>Todo list</h1>
                    <p class="brand-subtitle">Organizational & Personal Task Manager</p>
                </div>
            </div>
            <div class="header-actions">
                <button id="theme-toggle" class="btn-theme" aria-label="Ganti mode Gelap atau Terang">
                    <span class="theme-icon">🌙</span> <span class="theme-text">Toggle Theme</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Flash Message Notification -->
    <?php 
    $flash = get_flash(); 
    if ($flash): 
    ?>
        <div class="flash-alert flash-<?= htmlspecialchars($flash['type']); ?>" role="alert">
            <div class="flash-content">
                <span class="flash-icon">
                    <?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '⚠️' : 'ℹ️'); ?>
                </span>
                <span><?= htmlspecialchars($flash['message']); ?></span>
            </div>
            <button type="button" class="flash-close" onclick="this.parentElement.remove();" aria-label="Tutup notifikasi">&times;</button>
        </div>
    <?php endif; ?>
