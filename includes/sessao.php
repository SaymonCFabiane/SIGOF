<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function usuarioAtualId()
{
    return $_SESSION['usuario_atual_id'] ?? null;
}
