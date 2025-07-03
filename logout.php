<?php
// filepath: c:\xampp\htdocs\rental_mobil\logout.php

session_start();
session_destroy();
header('Location: index.php');
exit;