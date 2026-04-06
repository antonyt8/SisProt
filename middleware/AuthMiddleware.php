<?php

declare(strict_types=1);

function requireAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}
