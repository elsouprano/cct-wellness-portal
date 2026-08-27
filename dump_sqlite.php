<?php
$pdo = new PDO('sqlite:database/database.sqlite');
$stmt = $pdo->query("SELECT * FROM departments");
if ($stmt) {
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo "No departments table.\n";
}
$stmt = $pdo->query("SELECT * FROM programs");
if ($stmt) {
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo "No programs table.\n";
}
